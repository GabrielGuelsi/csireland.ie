@extends('adminlte::auth.auth-page', ['type' => 'login'])

@section('adminlte_css_pre')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body.login-page { background-color: #3D1F3D !important; font-family: 'Montserrat', sans-serif; }
        .login-box-msg  { font-family: 'Montserrat', sans-serif; color: #3D1F3D; font-weight: 600; }
        .login-logo     { margin-bottom: 1rem; }
        .login-logo a   { color: #ffffff !important; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 1.8rem; letter-spacing: 1px; }
        .login-logo a span { color: #F26522; }
        .login-card-body { border-top: 4px solid #F26522 !important; }
        .btn-primary    { background-color: #F26522 !important; border-color: #F26522 !important; font-weight: 600; }
        .btn-primary:hover { background-color: #d4541a !important; border-color: #d4541a !important; }
        a               { color: #F26522; }
    </style>
@stop

@section('auth_logo')
    <a href="{{ url('/') }}" class="text-white">
        <span style="color:#F26522">CI</span> Exchange
    </a>
@stop

@section('auth_header')
    Sign in to your account
@stop

@section('auth_body')
    <form method="POST" action="{{ route('login') }}">
        @csrf

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="input-group mb-3">
            <input type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="Email address" required autofocus autocomplete="username">
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-envelope"></span></div>
            </div>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Password" required autocomplete="current-password">
            <div class="input-group-append">
                <div class="input-group-text"><span class="fas fa-lock"></span></div>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-7">
                <div class="icheck-primary">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
            </div>
            <div class="col-5">
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </div>
        </div>
    </form>
@stop

@section('auth_footer')
    @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}" style="color:#F26522">Forgot your password?</a>
    @endif
@stop
