<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\Booking\BookingException;
use App\Services\Booking\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Visitors manage appointments via an unguessable token emailed to them
 * (guest bookings have no account). Tokens are compared in constant time.
 */
class AppointmentManageController extends Controller
{
    private function resolve(Request $request, string $reference): Appointment
    {
        $appointment = Appointment::query()->where('reference', $reference)->with(['service', 'counsellor'])->first();
        $token = (string) $request->input('token', $request->query('token'));

        $ownsViaAccount = $appointment && $request->user() && $appointment->user_id === $request->user()->id;
        $validToken = $appointment && $token !== '' && hash_equals($appointment->manage_token, $token);

        abort_unless($ownsViaAccount || $validToken, 404);

        return $appointment;
    }

    public function show(Request $request, string $reference): View
    {
        $appointment = $this->resolve($request, $reference);

        return view('counselling.manage', ['appointment' => $appointment, 'token' => $request->query('token')]);
    }

    public function accept(Request $request, string $reference, BookingService $booking): RedirectResponse
    {
        $appointment = $this->resolve($request, $reference);
        try {
            $booking->accept($appointment);
        } catch (BookingException $e) {
            return back()->withErrors(['appointment' => $e->getMessage()]);
        }

        return back()->with('status', __('Thank you — your appointment is confirmed.'));
    }

    public function decline(Request $request, string $reference, BookingService $booking): RedirectResponse
    {
        $appointment = $this->resolve($request, $reference);
        $note = $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;
        try {
            $booking->decline($appointment, $note);
        } catch (BookingException $e) {
            return back()->withErrors(['appointment' => $e->getMessage()]);
        }

        return back()->with('status', __('Thank you. A coordinator will propose another time.'));
    }

    public function cancel(Request $request, string $reference, BookingService $booking): RedirectResponse
    {
        $appointment = $this->resolve($request, $reference);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null;
        try {
            $booking->cancel($appointment, $reason ? 'Visitor: '.$reason : 'Cancelled by visitor');
        } catch (BookingException $e) {
            return back()->withErrors(['appointment' => $e->getMessage()]);
        }

        return back()->with('status', __('Your appointment has been cancelled.'));
    }
}
