@extends('layouts.auth')

@section('title', 'Login')

@section('content')

<h5 style="color: var(--text-primary); margin-bottom: 20px; text-align: center;">
    <i class="fas fa-lock" style="color: var(--accent-light);"></i> Masuk ke Sistem
</h5>

@if(Auth::check())
    <div style="background: rgba(255,193,7,0.12); border: 1px solid #ffc107; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 13px; color: var(--text-primary);">
        <i class="fas fa-exclamation-triangle" style="color: #ffc107; margin-right: 5px;"></i>
        Sesi aktif terdeteksi sebagai <strong>{{ Auth::user()->name }}</strong> ({{ Auth::user()->email }}).<br>
        Mengisi form di bawah ini akan mengakhiri sesi akun sebelumnya dan masuk dengan akun baru.
    </div>
@endif

@if($errors->any())
    <div style="background: rgba(220,53,69,0.1); border: 1px solid #dc3545; border-radius: 8px; padding: 10px; margin-bottom: 15px;">
        @foreach($errors->all() as $error)
            <small style="color: #dc3545;">{{ $error }}</small><br>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="mb-3">
        <label style="color: var(--text-secondary); font-size: 13px; margin-bottom: 6px; display: block;">
            Email Address
        </label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
            class="form-control-custom" style="width: 100%;"
            placeholder="Masukkan email">
    </div>

    <div class="mb-4">
        <label style="color: var(--text-secondary); font-size: 13px; margin-bottom: 6px; display: block;">
            Password
        </label>
        <input type="password" name="password" required
            class="form-control-custom" style="width: 100%;"
            placeholder="Masukkan password">
    </div>

    <div class="mb-3 d-flex align-items-center justify-content-between">
        <label style="color: var(--text-secondary); font-size: 13px; cursor: pointer;">
            <input type="checkbox" name="remember"> Remember Me
        </label>
        @if(Route::has('password.request'))
            <a href="{{ route('password.request') }}" style="color: var(--accent-light); font-size: 13px;">
                Lupa Password?
            </a>
        @endif
    </div>

    <button type="submit" class="btn-accent" style="width: 100%;">
        <i class="fas fa-sign-in-alt"></i> Login
    </button>

    <div class="text-center mt-3" style="font-size: 13px; color: var(--text-secondary);">
        Belum punya akun? <a href="{{ route('register') }}" style="color: var(--accent-light); font-weight: 500; text-decoration: none;">Daftar Akun Baru</a>
    </div>

</form>

@endsection