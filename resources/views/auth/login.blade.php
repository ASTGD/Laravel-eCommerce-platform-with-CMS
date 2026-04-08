<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} Login</title>
    <link rel="stylesheet" href="{{ asset('assets/platform/admin.css') }}">
</head>
<body class="admin-auth-shell">
    <main class="auth-card">
        <div class="auth-copy">
            <span class="eyebrow">Internal admin</span>
            <h1>Manage the reusable commerce platform.</h1>
            <p>Sign in with an admin account to manage pages, templates, menus, presets, and the first storefront preview flow.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="panel stack-md">
            @csrf

            <label class="field">
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </label>

            <label class="field">
                <span>Password</span>
                <input type="password" name="password" required>
                @error('password')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </label>

            <label class="checkbox-row">
                <input type="checkbox" name="remember" value="1">
                <span>Keep this session signed in</span>
            </label>

            <button type="submit" class="button button-primary">Sign in</button>
        </form>
    </main>
</body>
</html>
