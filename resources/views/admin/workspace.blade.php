@extends('admin.layout')
@section('content')
<div class="admin-shell">
    <aside class="sidebar">
        <a class="admin-brand light" href="/" aria-label="Busia 2027 website"><span class="brand-symbol">b<span>✳</span></span><span>ARTHUR<small>AMUG OSIYA · 2027</small></span></a>
        <p class="sidebar-label">WORKSPACE</p>
        <a class="sidebar-item {{ request()->routeIs('admin.index') ? 'active' : '' }}" href="{{ route('admin.index') }}" @if(request()->routeIs('admin.index')) aria-current="page" @endif><span aria-hidden="true">▦</span> Submissions <span class="sidebar-count">{{ $total ?? \App\Models\Interest::count() }}</span></a>
        <a class="sidebar-item {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}" href="{{ route('admin.messages.index') }}" @if(request()->routeIs('admin.messages.*')) aria-current="page" @endif><span aria-hidden="true">✉</span> Messages</a>
        <a class="sidebar-item website-link" href="/" target="_blank" rel="noopener"><span aria-hidden="true">↗</span> View website</a>
        <div class="sidebar-note"><span aria-hidden="true">✳</span><p>Our home.<br>Our future.<br><em>Our move.</em></p><small>BUSIA, TOGETHER.</small></div>
        <div class="sidebar-user"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><div>{{ auth()->user()->name }}<small>Administrator</small></div><form action="{{ route('admin.logout') }}" method="POST">@csrf<button type="submit" aria-label="Sign out" title="Sign out">↪</button></form></div>
    </aside>
    <main class="dashboard">
        <header class="dashboard-top"><span>Campaign workspace <span class="breadcrumb">/ @yield('breadcrumb', 'Overview')</span></span><span class="today">{{ now()->format('D, j M Y') }}</span></header>
        <div class="dashboard-content">
            @yield('workspace-content')
            <footer class="dashboard-footer"><span>© {{ date('Y') }} Busia 2027</span><span>Built by ncreatives</span></footer>
        </div>
    </main>
</div>
@endsection
