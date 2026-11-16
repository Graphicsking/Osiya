<article class="person-card" aria-labelledby="person-{{ $entry->id }}">
    <div class="person-card-heading">
        <span class="person-avatar" aria-hidden="true">{{ mb_substr($entry->name, 0, 1) }}</span>
        <div><h3 id="person-{{ $entry->id }}">{{ $entry->name }}</h3><span class="person-reference">#{{ str_pad($entry->id, 4, '0', STR_PAD_LEFT) }}</span></div>
        <span class="status-badge {{ $entry->status }}"><i></i>{{ ucfirst($entry->status) }}</span>
    </div>
    <dl class="person-details">
        <div><dt>Phone number</dt><dd><a href="tel:{{ preg_replace('/[^+0-9]/', '', $entry->phone) }}">{{ $entry->phone }}</a></dd></div>
        <div><dt>Involvement</dt><dd><span class="interest-tag">{{ $entry->interest }}</span></dd></div>
        <div><dt>Received</dt><dd><time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('j M Y · g:i a') }} EAT</time></dd></div>
    </dl>
    <form class="person-card-action" action="{{ route('admin.interests.update', $entry) }}" method="POST">
        @csrf @method('PATCH')
        <input type="hidden" name="status" value="{{ $entry->status === 'new' ? 'contacted' : 'new' }}">
        <button class="row-action" type="submit" aria-label="{{ $entry->status === 'new' ? 'Mark contacted' : 'Mark new' }}: {{ $entry->name }}">{{ $entry->status === 'new' ? 'Mark contacted' : 'Mark new' }}</button>
    </form>
</article>
