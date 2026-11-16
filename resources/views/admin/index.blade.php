@extends('admin.workspace')
@section('title', 'Community submissions')
@section('workspace-content')
            <div class="page-heading"><div><p class="overline">THE NEXT CHAPTER STARTS WITH PEOPLE</p><h1>Community submissions<span>.</span></h1><p class="muted">The people ready to be part of the Busia 2027 journey.</p></div><a class="admin-button message-cta" href="{{ route('admin.messages.create', request()->only(['interest', 'status', 'q'])) }}"><span aria-hidden="true">✉</span> Send message</a></div>
            @if (session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
            <section class="stat-grid" aria-label="Submission overview">
                <article class="stat-card featured"><span>Total submissions</span><strong>{{ number_format($total) }}</strong><small>Every voice matters <span aria-hidden="true">✳</span></small></article>
                <article class="stat-card"><span>Awaiting contact</span><strong>{{ number_format($newCount) }}</strong><small>New connections to follow up</small></article>
                <article class="stat-card"><span>Received today</span><strong>{{ number_format($today) }}</strong><small>{{ now()->format('j F Y') }}</small></article>
                <article class="stat-card"><span>Volunteers</span><strong>{{ number_format($volunteers) }}</strong><small>Ready to lend a hand</small></article>
            </section>
            <section id="submissions" class="submissions-panel" aria-labelledby="submissions-title">
                <div class="panel-heading"><div><h2 id="submissions-title">{{ request('interest') === 'Volunteer' ? 'Volunteers' : (request('interest') ?: 'All people') }}</h2><p class="muted">Names, phone numbers and ways to contribute.</p></div><div class="panel-tools"><span class="result-count">{{ number_format($interests->total()) }} {{ $interests->total() === 1 ? 'result' : 'results' }}</span><nav class="view-toggle" aria-label="Submission display"><a href="{{ route('admin.index', array_merge(request()->except(['page', 'view']), ['view' => 'cards'])) }}#submissions" class="{{ $displayView === 'cards' ? 'active' : '' }}" @if($displayView === 'cards') aria-current="page" @endif>Cards</a><a href="{{ route('admin.index', array_merge(request()->except(['page', 'view']), ['view' => 'table'])) }}#submissions" class="{{ $displayView === 'table' ? 'active' : '' }}" @if($displayView === 'table') aria-current="page" @endif>Table</a></nav></div></div>
                <form method="GET" action="{{ route('admin.index') }}" class="filter-form" role="search" aria-label="Filter submissions">
                    <input type="hidden" name="view" value="{{ $displayView }}">
                    <label class="search-field"><span>Search submissions</span><input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Search name or phone number"></label>
                    <label><span>Involvement</span><select name="interest"><option value="">All involvement</option>@foreach ($options as $option)<option value="{{ $option }}" @selected(request('interest') === $option)>{{ $option }}</option>@endforeach</select></label>
                    <label><span>Status</span><select name="status"><option value="">All statuses</option><option value="new" @selected(request('status') === 'new')>New</option><option value="contacted" @selected(request('status') === 'contacted')>Contacted</option></select></label>
                    <button class="admin-button" type="submit">Filter</button>
                    @if (request()->filled('q') || request()->filled('interest') || request()->filled('status'))<a class="reset-link" href="{{ route('admin.index', ['view' => $displayView]) }}#submissions">Clear</a>@endif
                </form>
                @if ($displayView === 'cards')
                    @if ($interests->isNotEmpty())
                        <div class="people-grid">
                            @foreach ($interests as $entry)
                                @include('admin.person-card', ['entry' => $entry])
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state"><span aria-hidden="true">✳</span><h3>{{ $total ? 'No matching submissions' : 'Your next connection starts here.' }}</h3><p>{{ $total ? 'Try another name, phone number or filter.' : 'When someone submits the Get involved form, their details will appear here.' }}</p></div>
                    @endif
                @else
                <div class="table-scroll">
                    <table><caption class="sr-only">Get involved form submissions, most recent first</caption><thead><tr><th scope="col">Name</th><th scope="col">Phone number</th><th scope="col">Involvement</th><th scope="col">Received</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead><tbody>
                    @forelse ($interests as $entry)
                        <tr><td><div class="person"><span class="person-avatar" aria-hidden="true">{{ mb_substr($entry->name, 0, 1) }}</span><div><strong>{{ $entry->name }}</strong><small>#{{ str_pad($entry->id, 4, '0', STR_PAD_LEFT) }}</small></div></div></td><td class="phone">{{ $entry->phone }}</td><td><span class="interest-tag">{{ $entry->interest }}</span></td><td class="date-cell">{{ $entry->created_at->format('j M Y') }}<small>{{ $entry->created_at->format('g:i a') }} EAT</small></td><td><span class="status-badge {{ $entry->status }}"><i></i>{{ ucfirst($entry->status) }}</span></td><td><form action="{{ route('admin.interests.update', $entry) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $entry->status === 'new' ? 'contacted' : 'new' }}"><button class="row-action" type="submit" aria-label="{{ $entry->status === 'new' ? 'Mark contacted' : 'Mark new' }}: {{ $entry->name }}">{{ $entry->status === 'new' ? 'Mark contacted' : 'Mark new' }}</button></form></td></tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><span aria-hidden="true">✳</span><h3>{{ $total ? 'No matching submissions' : 'Your next connection starts here.' }}</h3><p>{{ $total ? 'Try another name, phone number or filter.' : 'When someone submits the Get involved form, their details will appear here.' }}</p></div></td></tr>
                    @endforelse
                    </tbody></table>
                </div>
                @endif
                @if ($interests->hasPages())<nav class="pagination" aria-label="Submission pages"><span>Showing {{ $interests->firstItem() }}–{{ $interests->lastItem() }} of {{ $interests->total() }}</span><div>@if ($interests->onFirstPage())<span aria-disabled="true">Previous</span>@else<a href="{{ $interests->previousPageUrl() }}" rel="prev">Previous</a>@endif<span>Page {{ $interests->currentPage() }} of {{ $interests->lastPage() }}</span>@if ($interests->hasMorePages())<a href="{{ $interests->nextPageUrl() }}" rel="next">Next</a>@else<span aria-disabled="true">Next</span>@endif</div></nav>@endif
            </section>
@endsection
