<?php

namespace App\Http\Controllers;

use App\Services\Payments\DonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function payments(Request $request, string $provider, DonationService $donations): JsonResponse
    {
        $status = $donations->handleWebhook($provider, $request);

        return response()->json(['received' => $status === 200], $status);
    }
}
