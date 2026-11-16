@extends('admin.layout')
@section('title', 'Admin sign in')
@section('body-class', 'login-page')
@section('content')
<main class="login-shell">
    <section class="login-story" aria-label="Busia 2027">
        <a class="admin-brand light" href="/" aria-label="Busia 2027 website"><span class="brand-symbol">b<span>✳</span></span><span>ARTHUR<small>AMUG OSIYA · 2027</small></span></a>
        <div><p class="overline">PEOPLE. PURPOSE. CONNECTION.</p><h1>A shared future.<br><em>Built together.</em></h1><p class="story-copy">Every conversation starts with a person.<br>Keep the connections moving forward.</p></div>
        <span class="login-flower" aria-hidden="true">✳</span>
        <p class="login-foot">BUSIA COUNTY, KENYA <span>2027</span></p>
    </section>
    <section class="login-panel">
        <a class="back-link" href="/">← Back to the website</a>
        <div class="login-form-wrap">
            <p class="overline">CAMPAIGN ADMINISTRATION</p>
            <h2>Welcome back.</h2><p class="muted">Sign in to manage community submissions.</p>
            @if ($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
            <form action="{{ route('admin.login') }}" method="POST" class="login-form">
                @csrf
                <label>Email address<input name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus maxlength="254" placeholder="Your admin email"></label>
                <label>Password<input name="password" type="password" autocomplete="current-password" required placeholder="Your password"></label>
                <button class="admin-button" type="submit">Sign in</button>
            </form>
            <p class="login-help">Access is limited to campaign administrators.</p>
        </div>
        <p class="muted credit">Built by ncreatives</p>
    </section>
</main>
@endsection
