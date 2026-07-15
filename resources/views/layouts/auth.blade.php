<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ChainGuard - @yield('title', 'Login')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background-color: #0F2027; display: flex; align-items: center; justify-content: center; min-height: 100vh;">

    <div style="width: 100%; max-width: 420px; padding: 20px;">

        {{-- Logo --}}
        <div class="text-center mb-4">
            <h2 style="color: #3a8a52; font-weight: 700; letter-spacing: 3px;">⛓ CHAINGUARD</h2>
            <p style="color: #a8c5b5; font-size: 13px; letter-spacing: 1px;">Global Supply Chain Risk Monitor</p>
        </div>

        {{-- Card Login --}}
        <div class="card-custom">
            @yield('content')
        </div>

        <div class="text-center mt-3">
            <small style="color: #a8c5b5;">© 2026 ChainGuard. All rights reserved.</small>
        </div>

    </div>

</body>
</html>