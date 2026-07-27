@extends('layouts.auth')

@section('title', 'Registrasi Akun Pengguna')

@section('content')

<h5 style="color: var(--text-primary); margin-bottom: 20px; text-align: center;">
    <i class="fas fa-user-plus" style="color: var(--accent-light);"></i> Registrasi Akun Pengguna
</h5>

@if(Auth::check())
    <div style="background: rgba(255,193,7,0.12); border: 1px solid #ffc107; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 13px; color: var(--text-primary);">
        <i class="fas fa-exclamation-triangle" style="color: #ffc107; margin-right: 5px;"></i>
        Sesi aktif terdeteksi sebagai <strong>{{ Auth::user()->name }}</strong> ({{ Auth::user()->email }}).<br>
        Mendaftar akun baru akan mengakhiri sesi aktif saat ini.
    </div>
@endif

@if($errors->any())
    <div style="background: rgba(220,53,69,0.1); border: 1px solid #dc3545; border-radius: 8px; padding: 10px; margin-bottom: 15px;">
        @foreach($errors->all() as $error)
            <small style="color: #dc3545;">{{ $error }}</small><br>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="mb-3">
        <label style="color: var(--text-secondary); font-size: 13px; margin-bottom: 6px; display: block;">
            Nama Lengkap
        </label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus
            class="form-control-custom" style="width: 100%;"
            placeholder="Masukkan nama lengkap">
    </div>

    <div class="mb-3">
        <label style="color: var(--text-secondary); font-size: 13px; margin-bottom: 6px; display: block;">
            Email Address
        </label>
        <input type="email" name="email" value="{{ old('email') }}" required
            class="form-control-custom" style="width: 100%;"
            placeholder="Masukkan email aktif">
    </div>

    <div class="mb-3">
        <label style="color: var(--text-secondary); font-size: 13px; margin-bottom: 6px; display: block;">
            Password
        </label>
        <input type="password" name="password" required
            class="form-control-custom" style="width: 100%;"
            placeholder="Minimal 8 karakter">
    </div>

    <div class="mb-4">
        <label style="color: var(--text-secondary); font-size: 13px; margin-bottom: 6px; display: block;">
            Konfirmasi Password
        </label>
        <input type="password" name="password_confirmation" required
            class="form-control-custom" style="width: 100%;"
            placeholder="Ulangi password">
    </div>

    <button type="submit" class="btn-accent" style="width: 100%;">
        <i class="fas fa-user-check"></i> Daftar Akun Pengguna
    </button>

    <div class="text-center mt-3" style="font-size: 13px; color: var(--text-secondary);">
        Sudah memiliki akun? <a href="{{ route('login') }}" style="color: var(--accent-light); font-weight: 500; text-decoration: none;">Login di sini</a>
    </div>

</form>

@endsection
