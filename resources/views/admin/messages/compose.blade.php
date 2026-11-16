@extends('admin.workspace')
@section('title', $message->exists ? 'Edit message' : 'New message')
@section('breadcrumb', 'Messages / New message')
@section('workspace-content')
<a class="back-link message-back" href="{{ route('admin.messages.index') }}">← All messages</a>
<div class="page-heading"><div><p class="overline">A LITTLE CONNECTION GOES A LONG WAY</p><h1>{{ $message->exists ? 'Edit message' : 'Send a message' }}<span>.</span></h1><p class="muted">Choose your people. Write your message. Give it one last look.</p></div><span class="channel-badge">✉ &nbsp; SMS message</span></div>
<ol class="message-steps" aria-label="Message steps"><li class="current"><span>01</span> Choose audience</li><li class="current"><span>02</span> Write message</li><li><span>03</span> Review &amp; send</li></ol>
@if($errors->any())<div class="notice error" role="alert"><strong>Please check your message.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="message-composer" method="POST" action="{{ $message->exists ? route('admin.messages.update', $message) : route('admin.messages.store') }}" data-message-composer data-audience-url="{{ route('admin.messages.audience') }}">
    @csrf @if($message->exists) @method('PUT') @endif
    <div class="composer-main">
        <section class="composer-section" aria-labelledby="audience-title">
            <div class="composer-section-heading"><span class="step-number">01</span><div><h2 id="audience-title">Who is this for?</h2><p class="muted">Reach everyone or choose a specific involvement group.</p></div></div>
            <div class="audience-fields">
                <label>Involvement<select name="interest" data-audience-filter><option value="">Everyone</option>@foreach($options as $option)<option value="{{ $option }}" @selected(old('interest', $message->filters['interest'] ?? '') === $option)>{{ $option === 'Volunteer' ? 'Volunteers' : $option }}</option>@endforeach</select></label>
                <label>Contact status<select name="status" data-audience-filter><option value="">All statuses</option><option value="new" @selected(old('status', $message->filters['status'] ?? '') === 'new')>Awaiting contact</option><option value="contacted" @selected(old('status', $message->filters['status'] ?? '') === 'contacted')>Contacted</option></select></label>
            </div>
            <label class="audience-search">Narrow by name or phone <span class="field-optional">(optional)</span><input type="search" name="q" value="{{ old('q', $message->filters['q'] ?? '') }}" maxlength="100" placeholder="Search your submissions" data-audience-filter></label>
            <div class="audience-summary" role="status" aria-live="polite"><span class="audience-summary-icon" aria-hidden="true">◎</span><div><strong data-audience-count>{{ number_format($audience['recipients']->count()) }} reachable {{ $audience['recipients']->count() === 1 ? 'contact' : 'contacts' }}</strong><p data-audience-note>{{ $audience['excluded'] }} duplicate or incomplete phone records excluded. Final audience is saved at review.</p></div></div>
            <p class="field-help">Only contacts who agreed to be contacted are included. Each phone number receives the message once. Local mobile numbers use Kenya’s +254 code.</p>
        </section>
        <section class="composer-section" aria-labelledby="compose-title">
            <div class="composer-section-heading"><span class="step-number">02</span><div><h2 id="compose-title">What would you like to say?</h2><p class="muted">Keep it clear, personal and easy to read.</p></div></div>
            <label>Message name<input name="title" value="{{ old('title', $message->title) }}" maxlength="100" required placeholder="e.g. Community meeting reminder" aria-describedby="title-help"></label><p id="title-help" class="field-help">For your records. Your audience only sees the message below.</p>
            <label class="message-body-label">Your message<textarea name="body" rows="8" maxlength="1000" required placeholder="Write the message you want your audience to receive…" data-message-body aria-describedby="body-help">{{ old('body', $message->body) }}</textarea></label>
            <div class="message-text-meta"><span id="body-help">Long messages may use multiple SMS. Provider charges apply.</span><span data-character-count>{{ mb_strlen(old('body', $message->body) ?? '') }} / 1,000</span></div>
        </section>
        <div class="composer-actions"><button class="secondary-button" type="submit" name="intent" value="draft">Save draft</button><button class="admin-button" type="submit" name="intent" value="review">Review message <span aria-hidden="true">→</span></button></div>
        <p class="composer-reassurance">Nothing is sent until you review and confirm.</p>
    </div>
    <aside class="message-preview" aria-labelledby="preview-title"><div class="preview-heading"><p class="overline">A VIEW FROM THEIR SIDE</p><h2 id="preview-title">Message preview</h2></div><div class="phone-preview"><div class="phone-speaker" aria-hidden="true"></div><div class="phone-header"><span aria-hidden="true">‹</span><span class="phone-contact">✳<small>Campaign message</small></span><span aria-hidden="true">ⓘ</span></div><p class="phone-timestamp">SMS · Preview</p><div class="sms-bubble" data-message-preview>{{ old('body', $message->body) ?: 'Your message will appear here as you write.' }}</div></div><p class="preview-caption">A simple preview of your text. The sender shown on the recipient’s phone depends on your SMS service.</p></aside>
</form>
@endsection
@push('scripts')<script src="{{ asset('admin-messages.js') }}" defer></script>@endpush
