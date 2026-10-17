<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Services\Payments\DonationService;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\TestGateway;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonationController extends Controller
{
    public function __construct(private readonly PaymentManager $payments, private readonly DonationService $donations) {}

    private function currencies(): array
    {
        $configured = array_filter(array_map('trim', explode(',', (string) setting('donation_currencies', 'NGN'))));
        $supported = rescue(fn () => $this->payments->gateway()->supportedCurrencies(), [], false);

        return array_values(array_intersect($configured, $supported)) ?: array_slice($supported, 0, 1);
    }

    public function index(Request $request): View
    {
        return view('donations.index', [
            'campaigns' => DonationCampaign::query()->open()->get(),
            'currencies' => $this->currencies(),
            'presets' => array_filter(array_map('trim', explode(',', (string) setting('donation_presets')))),
            'selectedCampaign' => $request->query('campaign'),
            'gatewayAvailable' => rescue(fn () => (bool) $this->payments->gateway(), false, false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $currencies = $this->currencies();
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d{1,12}(\.\d{1,2})?$/'],
            'currency' => ['required', Rule::in($currencies)],
            'campaign' => ['nullable', Rule::exists('donation_campaigns', 'slug')->where('status', 'active')],
            'is_anonymous' => ['nullable', 'boolean'],
            'donor_name' => ['nullable', 'string', 'max:120'],
            'donor_email' => [$request->user() ? 'nullable' : 'required_unless:is_anonymous,1', 'nullable', 'email:rfc', 'max:190'],
            'note' => ['nullable', 'string', 'max:500'],
            'website' => ['prohibited'],
        ]);

        $minor = Money::toMinor($data['amount'], $data['currency']);
        $min = config('fco.payments.min_amount_minor');
        if ($minor < $min || $minor > config('fco.payments.max_amount_minor')) {
            return back()->withInput()->withErrors(['amount' => __('Please enter an amount of at least :min.', ['min' => Money::format($min, $data['currency'])])]);
        }

        try {
            $result = $this->donations->start([
                'amount_minor' => $minor,
                'currency' => $data['currency'],
                'campaign_id' => isset($data['campaign']) ? DonationCampaign::where('slug', $data['campaign'])->value('id') : null,
                'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
                'donor_name' => $data['donor_name'] ?? null,
                'donor_email' => $data['donor_email'] ?? null,
                'note' => $data['note'] ?? null,
            ], $request->user());
        } catch (PaymentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        session()->push('donations.mine', $result['donation']->reference);

        return redirect()->away($result['checkout_url']);
    }

    /**
     * Return URL from the hosted checkout. The query string is ignored; the
     * donation is verified server-to-server before any status is shown.
     */
    public function callback(Donation $donation): RedirectResponse
    {
        if ($donation->status === 'pending') {
            $this->donations->confirm($donation, 'callback');
        }

        return redirect()->to(lroute('give.status', $donation));
    }

    public function status(Request $request, Donation $donation): View
    {
        // Only the browser that started the donation (or its signed-in owner) can view it.
        $mine = in_array($donation->reference, session('donations.mine', []), true)
            || ($request->user() && $donation->user_id === $request->user()->id);
        abort_unless($mine, 404);

        return view('donations.status', ['donation' => $donation->load('campaign')]);
    }

    public function testCheckout(string $reference): View
    {
        abort_unless($this->payments->has('test'), 404);

        return view('donations.test-checkout', ['reference' => $reference]);
    }

    public function testSettle(Request $request, string $reference): RedirectResponse
    {
        abort_unless($this->payments->has('test'), 404);
        /** @var TestGateway $gateway */
        $gateway = $this->payments->gateway('test');
        $gateway->settle($reference, $request->input('outcome') === 'success' ? 'success' : 'failed');

        return redirect()->away($gateway->callbackFor($reference) ?? url('/give'));
    }
}
