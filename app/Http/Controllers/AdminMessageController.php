<?php

namespace App\Http\Controllers;

use App\Jobs\SendAudienceSms;
use App\Models\AudienceMessage;
use App\Models\Interest;
use App\Services\MessageAudience;
use App\Services\SmsGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminMessageController extends Controller
{
    private function filters(Request $request): array
    {
        return $request->validate([
            'interest' => ['nullable', Rule::in(Interest::OPTIONS)],
            'status' => ['nullable', Rule::in(['new', 'contacted'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
    }

    public function index()
    {
        return view('admin.messages.index', [
            'messages' => AudienceMessage::withCount('recipients')->latest('id')->paginate(20),
        ]);
    }

    public function create(Request $request, MessageAudience $audience)
    {
        $filters = $this->filters($request);
        return view('admin.messages.compose', [
            'message' => new AudienceMessage(['filters' => $filters]),
            'audience' => $audience->resolve($filters), 'options' => Interest::OPTIONS,
        ]);
    }

    public function audience(Request $request, MessageAudience $audience)
    {
        $result = $audience->resolve($this->filters($request));
        return response()->json(['count' => $result['recipients']->count(), 'excluded' => $result['excluded']]);
    }

    public function edit(AudienceMessage $message, MessageAudience $audience)
    {
        abort_unless(in_array($message->status, ['draft', 'review']), 409, 'Queued messages cannot be edited.');
        return view('admin.messages.compose', [
            'message' => $message, 'audience' => $audience->resolve($message->filters), 'options' => Interest::OPTIONS,
        ]);
    }

    public function store(Request $request, MessageAudience $audience)
    {
        return $this->save($request, new AudienceMessage(['user_id' => $request->user()->id]), $audience);
    }

    public function update(Request $request, AudienceMessage $message, MessageAudience $audience)
    {
        return $this->save($request, $message, $audience);
    }

    private function save(Request $request, AudienceMessage $message, MessageAudience $audience)
    {
        $filters = $this->filters($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:1000'],
            'intent' => ['required', Rule::in(['draft', 'review'])],
        ]);
        $result = $audience->resolve($filters);
        if ($data['intent'] === 'review' && $result['recipients']->isEmpty()) {
            throw ValidationException::withMessages(['interest' => 'No reachable contacts match this audience. Choose another audience or save a draft.']);
        }

        DB::transaction(function () use (&$message, $data, $filters, $result) {
            if ($message->exists) {
                $message = AudienceMessage::lockForUpdate()->findOrFail($message->id);
                abort_unless(in_array($message->status, ['draft', 'review']), 409, 'Queued messages cannot be edited.');
            }
            $message->fill([
                'title' => $data['title'], 'body' => $data['body'], 'filters' => $filters,
                'status' => $data['intent'], 'review_token' => (string) Str::uuid(), 'excluded_count' => $result['excluded'],
            ])->save();
            $message->recipients()->delete();
            if ($data['intent'] === 'review') $message->recipients()->createMany($result['recipients']->all());
        });

        return redirect()->route('admin.messages.show', $message)
            ->with('status', $data['intent'] === 'draft' ? 'Draft saved. You can come back to it anytime.' : 'Your message is ready to review. Nothing has been sent.');
    }

    public function show(AudienceMessage $message, SmsGateway $gateway)
    {
        return view('admin.messages.show', [
            'message' => $message, 'connected' => $gateway->configured(),
            'recipients' => $message->recipients()->orderBy('id')->paginate(20),
            'counts' => $message->recipients()->select('status')->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function send(Request $request, AudienceMessage $message, SmsGateway $gateway)
    {
        $data = $request->validate(['review_token' => ['required', 'uuid'], 'confirm' => ['accepted']]);
        if (! $gateway->configured()) {
            return back()->withErrors(['sms' => 'SMS sending is not connected yet. Your message is saved; contact your site administrator to enable sending.']);
        }

        DB::transaction(function () use ($message, $data) {
            $message = AudienceMessage::lockForUpdate()->findOrFail($message->id);
            abort_unless($message->status === 'review' && hash_equals((string) $message->review_token, $data['review_token']), 409, 'This review has changed or the message has already been queued. Open the latest review.');
            abort_unless($message->recipients()->exists(), 422, 'Choose at least one reachable contact.');
            $message->update(['status' => 'queued', 'queued_at' => now()]);
            // The dedicated database queue uses the same connection as these records:
            // the message and every recipient job commit atomically.
            foreach ($message->recipients()->pluck('id') as $recipientId) Bus::dispatch(new SendAudienceSms($recipientId));
        });

        return redirect()->route('admin.messages.show', $message)->with('status', 'Message queued. You can follow its progress below.');
    }
}
