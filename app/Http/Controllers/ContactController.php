<?php

namespace App\Http\Controllers;

use App\Models\ContactEnquiry;
use App\Notifications\StaffAlert;
use App\Support\Reference;
use App\Support\StaffNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('pages.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['prohibited'],
        ]);
        unset($data['website']);

        $enquiry = ContactEnquiry::create($data + ['reference' => Reference::make('ENQ'), 'status' => 'new']);

        StaffNotifier::notify('enquiries.manage', new StaffAlert(
            __('New contact enquiry :ref', ['ref' => $enquiry->reference]),
            __('A new enquiry has been submitted through the website.'),
            url('/admin'),
        ));

        return back()->with('status', __('Thank you — your message has been received. Reference: :ref', ['ref' => $enquiry->reference]));
    }
}
