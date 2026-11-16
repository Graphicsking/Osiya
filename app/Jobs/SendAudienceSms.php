<?php

namespace App\Jobs;

use App\Models\Interest;
use App\Models\MessageRecipient;
use App\Services\MessageAudience;
use App\Services\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendAudienceSms implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 30;
    public bool $failOnTimeout = true;

    public function __construct(public int $recipientId)
    {
        $this->onConnection('messages')->onQueue('messages')->beforeCommit();
    }

    public function handle(SmsGateway $gateway): void
    {
        $recipient = MessageRecipient::with('message')->findOrFail($this->recipientId);
        if ($recipient->message->status !== 'queued') return;
        // Claim once, including on manual retries, so a duplicate job cannot send twice.
        if (! MessageRecipient::whereKey($recipient->id)->where('status', 'pending')->update(['status' => 'processing'])) return;

        $person = Interest::find($recipient->interest_id);
        if (! $person?->consented_at || MessageAudience::normalizePhone($person->phone) !== $recipient->phone) {
            $recipient->update(['status' => 'skipped', 'error' => 'Contact details or consent changed after review.']);
            return;
        }

        try {
            $providerId = $gateway->send($recipient->phone, $recipient->message->body);
        } catch (Throwable) {
            $recipient->update(['status' => 'failed', 'error' => 'Submission could not be confirmed. Check the SMS provider before resending.']);
            return;
        }

        $recipient->update(['status' => 'submitted', 'provider_id' => $providerId, 'error' => null]);
    }

    public function failed(?Throwable $exception): void
    {
        MessageRecipient::whereKey($this->recipientId)->whereIn('status', ['pending', 'processing'])
            ->update(['status' => 'failed', 'error' => 'Submission could not be confirmed. Check the SMS provider before resending.']);
    }
}
