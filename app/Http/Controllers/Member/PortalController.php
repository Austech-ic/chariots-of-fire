<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AccountRequest;
use App\Models\MemberResource;
use App\Models\NewsletterSubscription;
use App\Models\NotificationConsent;
use App\Notifications\StaffAlert;
use App\Support\Locales;
use App\Support\StaffNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Every query is scoped to the signed-in user, so changing an id in a URL
 * can never expose another member's records.
 */
class PortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        return view('member.dashboard', [
            'appointments' => $user->appointments()->with('service')->where('starts_at', '>=', now())->whereIn('status', ['offered', 'pending', 'confirmed'])->orderBy('starts_at')->limit(3)->get(),
            'registrations' => $user->eventRegistrations()->with('event.translations')->whereHas('event', fn ($q) => $q->where('starts_at', '>=', now()))->limit(3)->get(),
            'resources' => MemberResource::query()->accessibleBy($user)->latest('published_at')->limit(4)->get(),
            'donationCount' => $user->donations()->where('status', 'paid')->count(),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('member.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'locale' => ['required', Rule::in(Locales::enabled())],
        ]);

        $data['email'] = strtolower($data['email']);
        $emailChanged = $data['email'] !== $user->email;
        $user->fill($data);
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice');
        }

        return back()->with('status', __('Profile updated.'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
        Auth::logoutOtherDevices($data['password']);

        return back()->with('status', __('Password updated.'));
    }

    public function preferences(Request $request): View
    {
        $user = $request->user();

        return view('member.preferences', [
            'user' => $user,
            'newsletter' => NewsletterSubscription::where('email_normalized', NewsletterSubscription::normalize($user->email))->first(),
            'consents' => $user->notificationConsents()->where('status', 'granted')->get()->map(fn ($c) => $c->channel.'.'.$c->purpose)->all(),
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'newsletter' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'required_with:channels', 'string', 'max:32'],
            'channels' => ['nullable', 'array'],
            'channels.*' => [Rule::in(collect(NotificationConsent::CHANNELS)->keys()->crossJoin(array_keys(NotificationConsent::PURPOSES))->map(fn ($p) => implode('.', $p))->all())],
        ]);

        // Newsletter (verified account email counts as confirmation).
        $sub = NewsletterSubscription::firstOrNew(['email_normalized' => NewsletterSubscription::normalize($user->email)]);
        if ($request->boolean('newsletter') && $sub->status !== 'active' && $sub->status !== 'suppressed') {
            $sub->fill(['status' => 'active', 'user_id' => $user->id, 'name' => $user->name, 'locale' => $user->locale, 'subscribed_at' => now(), 'unsubscribed_at' => null])->save();
            $sub->consents()->create(['purpose' => 'ministry_newsletter', 'source' => 'member_portal', 'ip_address' => $request->ip(), 'granted_at' => now()]);
            \App\Jobs\SyncNewsletterSubscription::dispatch($sub->id);
        } elseif (! $request->boolean('newsletter') && $sub->exists && $sub->status === 'active') {
            $sub->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);
            $sub->consents()->whereNull('withdrawn_at')->update(['withdrawn_at' => now()]);
            \App\Jobs\SyncNewsletterSubscription::dispatch($sub->id);
        }

        // SMS / WhatsApp consent per channel and purpose. Opt-outs apply immediately.
        $wanted = collect($data['channels'] ?? []);
        $phone = $data['phone'] ?? $user->phone;
        if ($phone) {
            $user->update(['phone' => $phone]);
        }

        foreach (array_keys(NotificationConsent::CHANNELS) as $channel) {
            foreach (array_keys(NotificationConsent::PURPOSES) as $purpose) {
                $key = "{$channel}.{$purpose}";
                $existing = $user->notificationConsents()->where('channel', $channel)->where('purpose', $purpose)->first();
                if ($wanted->contains($key) && $phone) {
                    NotificationConsent::updateOrCreate(
                        ['contact_hash' => NotificationConsent::hashContact($phone), 'channel' => $channel, 'purpose' => $purpose],
                        ['user_id' => $user->id, 'contact_value' => $phone, 'status' => 'granted', 'source' => 'member_portal', 'consented_at' => now(), 'withdrawn_at' => null],
                    );
                } elseif ($existing && $existing->status === 'granted') {
                    $existing->update(['status' => 'withdrawn', 'withdrawn_at' => now()]);
                }
            }
        }

        return back()->with('status', __('Communication preferences saved.'));
    }

    public function appointments(Request $request): View
    {
        return view('member.appointments', [
            'appointments' => $request->user()->appointments()->with('service')->latest('starts_at')->paginate(15),
        ]);
    }

    public function donations(Request $request): View
    {
        // Only verified (paid/refunded) transactions linked to this account are listed.
        return view('member.donations', [
            'donations' => $request->user()->donations()->with('campaign')
                ->whereIn('status', ['paid', 'refunded', 'partially_refunded'])->latest('paid_at')->paginate(15),
        ]);
    }

    public function registrations(Request $request): View
    {
        return view('member.registrations', [
            'registrations' => $request->user()->eventRegistrations()->with('event.translations')->latest()->paginate(15),
        ]);
    }

    public function resources(Request $request): View
    {
        return view('member.resources', [
            'resources' => MemberResource::query()->accessibleBy($request->user())->latest('published_at')->paginate(12),
        ]);
    }

    public function resource(Request $request, MemberResource $resource): View
    {
        abort_unless($resource->isAccessibleBy($request->user()), 404);

        return view('member.resource', ['resource' => $resource->load('asset')]);
    }

    public function downloadResource(Request $request, MemberResource $resource): BinaryFileResponse
    {
        abort_unless($resource->isAccessibleBy($request->user()) && $resource->asset, 404);
        $asset = $resource->asset;

        return response()->download($asset->absolutePath(), Str::slug($resource->title).'.'.pathinfo($asset->path, PATHINFO_EXTENSION), [
            'Content-Type' => $asset->mime_type,
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function account(Request $request): View
    {
        return view('member.account', ['requests' => $request->user()->accountRequests()->latest()->get()]);
    }

    public function storeAccountRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(AccountRequest::TYPES))],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $accountRequest = $request->user()->accountRequests()->create($data + ['status' => 'open']);

        StaffNotifier::notify('members.manage', new StaffAlert(
            __('New member account request'),
            __('A member has submitted an account request (:type).', ['type' => AccountRequest::TYPES[$accountRequest->type]]),
            url('/admin'),
        ));

        return back()->with('status', __('Your request has been received. We will respond by email.'));
    }

    public function setLocale(Request $request, string $locale): RedirectResponse
    {
        abort_unless(Locales::isEnabled($locale), 404);
        $request->session()->put('locale', $locale);
        $request->user()?->forceFill(['locale' => $locale])->save();

        return back();
    }
}
