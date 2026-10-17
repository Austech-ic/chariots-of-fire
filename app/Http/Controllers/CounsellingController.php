<?php

namespace App\Http\Controllers;

use App\Models\CounsellingRequest;
use App\Models\CounsellingService;
use App\Notifications\StaffAlert;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingException;
use App\Services\Booking\BookingService;
use App\Services\Booking\Slot;
use App\Support\Reference;
use App\Support\StaffNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CounsellingController extends Controller
{
    public function index(): View
    {
        return view('counselling.index', ['services' => CounsellingService::query()->active()->get()]);
    }

    /** Path A: confidential request reviewed by a coordinator. */
    public function requestForm(Request $request): View
    {
        return view('counselling.request', [
            'services' => CounsellingService::query()->active()->get()->filter->allowsRequests(),
            'selected' => $request->query('service'),
        ]);
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service' => ['nullable', Rule::exists('counselling_services', 'slug')->where('status', 'active')],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required_without:phone', 'nullable', 'email:rfc', 'max:190'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:32'],
            'preferred_contact' => ['required', 'in:email,phone,whatsapp'],
            'appointment_method' => ['nullable', Rule::in(array_keys(CounsellingService::METHODS))],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'preferred_times' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ]);

        $service = isset($data['service']) ? CounsellingService::where('slug', $data['service'])->first() : null;

        $counsellingRequest = CounsellingRequest::create([
            'reference' => Reference::make('CR'),
            'requester_user_id' => $request->user()?->id,
            'service_id' => $service?->id,
            'contact_data' => array_filter(['name' => $data['name'], 'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null]),
            'preferred_contact' => $data['preferred_contact'],
            'appointment_method' => $data['appointment_method'] ?? null,
            'encrypted_message' => $data['message'],
            'preferred_times' => $data['preferred_times'] ?? null,
            'status' => 'new',
            'response_token' => Str::random(48),
        ]);

        // Generic alert only: the narrative never leaves the secure dashboard.
        StaffNotifier::notify('counselling.coordinate', new StaffAlert(
            __('New counselling request'),
            __('A new confidential counselling request (:ref) is waiting for review.', ['ref' => $counsellingRequest->reference]),
            url('/admin'),
        ));

        return redirect()->to(lroute('counselling.thanks'))->with('counselling_reference', $counsellingRequest->reference)->with('counselling_type', 'request');
    }

    /** Path B: self-service booking with live availability. */
    public function book(Request $request, AvailabilityService $availability): View
    {
        $services = CounsellingService::query()->active()->get()->filter->allowsSelfService()->values();
        $service = $services->firstWhere('slug', $request->query('service')) ?? ($services->count() === 1 ? $services->first() : null);

        $slots = collect();
        $weekStart = null;
        if ($service) {
            $tz = config('app.display_timezone');
            $requested = rescue(fn () => CarbonImmutable::parse((string) $request->query('week'), $tz), null, false);
            $weekStart = ($requested ?? CarbonImmutable::now($tz))->startOfDay();
            if ($weekStart->lt(CarbonImmutable::now($tz)->startOfDay())) {
                $weekStart = CarbonImmutable::now($tz)->startOfDay();
            }
            $slots = $availability->slots($service, $weekStart->utc(), $weekStart->addDays(7)->utc())
                ->groupBy(fn (Slot $s) => $s->start->setTimezone($tz)->format('Y-m-d'));
        }

        return view('counselling.book', [
            'services' => $services,
            'service' => $service,
            'slots' => $slots,
            'weekStart' => $weekStart,
            'counsellors' => $service?->counsellors()->pluck('display_name', 'counsellor_profiles.id') ?? collect(),
        ]);
    }

    public function storeBooking(Request $request, BookingService $booking): RedirectResponse
    {
        $data = $request->validate([
            'service' => ['required', Rule::exists('counselling_services', 'slug')->where('status', 'active')],
            'slot' => ['required', 'string', 'regex:/^\d+\|\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/'],
            'method' => ['required', Rule::in(array_keys(CounsellingService::METHODS))],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ]);

        $service = CounsellingService::where('slug', $data['service'])->firstOrFail();
        [$counsellorId, $start] = explode('|', $data['slot']);

        try {
            $appointment = $booking->book(
                $service,
                (int) $counsellorId,
                CarbonImmutable::parse($start, 'UTC'),
                ['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null],
                $data['method'],
                $request->user(),
            );
        } catch (BookingException $e) {
            return back()->withInput()->withErrors(['slot' => $e->getMessage()]);
        }

        return redirect()->to(lroute('counselling.thanks'))
            ->with('counselling_reference', $appointment->reference)
            ->with('counselling_type', $appointment->status === 'confirmed' ? 'confirmed' : 'pending');
    }

    public function thanks(Request $request): View|RedirectResponse
    {
        if (! session('counselling_reference')) {
            return redirect()->to(lroute('counselling.index'));
        }

        return view('counselling.thanks', ['reference' => session('counselling_reference'), 'type' => session('counselling_type')]);
    }
}
