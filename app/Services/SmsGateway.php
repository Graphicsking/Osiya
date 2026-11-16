<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmsGateway
{
    public function configured(): bool
    {
        return filled(config('services.twilio.sid')) && filled(config('services.twilio.token')) && filled(config('services.twilio.from'));
    }

    public function send(string $phone, string $body): string
    {
        if (! $this->configured()) throw new RuntimeException('SMS sending is not connected.');

        // Do not retry an ambiguous request: the provider may already have accepted it.
        $sid = config('services.twilio.sid');
        $response = Http::asForm()->withBasicAuth($sid, config('services.twilio.token'))
            ->connectTimeout(5)->timeout(20)
            ->post('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode($sid).'/Messages.json', [
                'To' => $phone, 'From' => config('services.twilio.from'), 'Body' => $body,
            ]);

        if (! $response->successful() || ! is_string($response->json('sid')) || ! filled($response->json('sid')) || in_array($response->json('status'), ['failed', 'undelivered', 'canceled'])) {
            throw new RuntimeException('SMS submission was not confirmed. Check the provider before trying again.');
        }

        return $response->json('sid');
    }
}
