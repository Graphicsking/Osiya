<?php

namespace App\Http\Controllers;

use App\Models\Interest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InterestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'submission_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['bail', 'required', 'string', 'max:32', 'regex:/^\+?[0-9][0-9\s().-]*$/', function ($attribute, $value, $fail) {
                $digits = preg_replace('/\D/', '', $value);
                if (strlen($digits) < 7 || strlen($digits) > 15) {
                    $fail('Please enter a valid phone number with 7 to 15 digits.');
                }
            }],
            'interest' => ['required', Rule::in(Interest::OPTIONS)],
            'consent' => ['required', 'accepted'],
        ]);

        // Retries of the same form submission must not create another record.
        Interest::firstOrCreate(['submission_id' => $data['submission_id']], [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'interest' => $data['interest'],
            'consented_at' => now(),
        ]);

        return response()->json(['message' => 'Thank you. Your details have been received.'], 201);
    }
}
