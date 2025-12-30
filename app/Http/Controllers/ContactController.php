<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function demo(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:2000',
        ]);

        try {
            // Log the demo request
            Log::info('Demo Request Received', [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'company' => $validated['company'],
                'phone' => $validated['phone'] ?? 'N/A',
                'message' => $validated['message'] ?? 'N/A',
                'timestamp' => now(),
            ]);

            // TODO: Send email notification to admin
            // You can uncomment and configure this when email is set up
            /*
            Mail::to(config('mail.admin_email', 'info@andona.de'))->send(new \App\Mail\DemoRequest($validated));
            */

            return back()->with('success', 'Vielen Dank für Ihre Anfrage! Wir melden uns in Kürze bei Ihnen.');
        } catch (\Exception $e) {
            Log::error('Demo Request Error', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return back()->withErrors(['error' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.']);
        }
    }
}

