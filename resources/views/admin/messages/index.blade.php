@extends('admin.workspace')
@section('title', 'Messages')
@section('breadcrumb', 'Messages')
@section('workspace-content')
<div class="page-heading"><div><p class="overline">KEEP THE CONVERSATION GOING</p><h1>Messages<span>.</span></h1><p class="muted">One message. The people who need to hear it.</p></div><a class="admin-button message-cta" href="{{ route('admin.messages.create') }}"><span aria-hidden="true">✉</span> Send message</a></div>
<section class="submissions-panel" aria-labelledby="message-history-title">
    <div class="panel-heading"><div><h2 id="message-history-title">Your messages</h2><p class="muted">Pick up a draft or follow a message you have queued.</p></div><span class="result-count">{{ $messages->total() }} {{ $messages->total() === 1 ? 'message' : 'messages' }}</span></div>
    @forelse ($messages as $message)
        <a class="message-history-row" href="{{ route('admin.messages.show', $message) }}">
            <span class="message-history-icon" aria-hidden="true">✉</span>
            <span class="message-history-copy"><strong>{{ $message->title }}</strong><span>{{ \Illuminate\Support\Str::limit($message->body, 110) }}</span><small>SMS · {{ $message->updated_at->format('j M Y · g:i a') }} EAT @if($message->status !== 'draft') · {{ number_format($message->recipients_count) }} recipients @endif</small></span>
            <span class="message-status {{ $message->status }}">{{ ['draft' => 'Draft', 'review' => 'Ready for review', 'queued' => 'View progress'][$message->status] }}</span><span aria-hidden="true">↗</span>
        </a>
    @empty
        <div class="empty-state message-empty"><span aria-hidden="true">✉</span><h3>Your next conversation starts here.</h3><p>Share an update with everyone, or choose a smaller audience from your submissions.</p><a class="admin-button" href="{{ route('admin.messages.create') }}">Create your first message</a></div>
    @endforelse
    @if($messages->hasPages())<nav class="pagination" aria-label="Message pages"><span>Page {{ $messages->currentPage() }} of {{ $messages->lastPage() }}</span><div>@if($messages->previousPageUrl())<a href="{{ $messages->previousPageUrl() }}">Previous</a>@endif @if($messages->nextPageUrl())<a href="{{ $messages->nextPageUrl() }}">Next</a>@endif</div></nav>@endif
</section>
@endsection
