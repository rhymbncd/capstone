<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | Bubog NHS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/auth.js'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-neutral-50 p-4 font-sans sm:p-6">

  <a href="{{ route(($portalType ?? 'student').'.login') }}" class="mb-4 inline-flex w-full max-w-[460px] items-center gap-1.5 text-[13px] font-semibold text-neutral-500 transition-colors duration-150 hover:text-neutral-900">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to Sign In
  </a>

  <div class="w-full max-w-[460px] rounded-lg border border-neutral-200 bg-white p-8 shadow-overlay sm:p-10">
    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary-tint text-primary">
      <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
    </div>

    <h1 class="text-[24px] font-bold tracking-tight text-neutral-900">Set a new password</h1>
    <p class="mb-6 mt-1 text-[13px] leading-relaxed text-neutral-500">Choose a new password for your {{ $portalType ?? 'student' }} account.</p>

    <form method="POST" action="{{ route(($portalType ?? 'student').'.password.update') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="token" value="{{ $token }}">

      <x-input
          label="Email address" name="email" type="email"
          autocomplete="off" readonly required
          value="{{ old('email', $email ?? '') }}"
          :error="$errors->first('email')"
      />

      <x-input
          label="New password" name="password" type="password" id="pw"
          autocomplete="new-password" required minlength="8" placeholder="••••••••"
          :error="$errors->first('password')"
      />

      <x-input
          label="Confirm new password" name="password_confirmation" type="password" id="pw2"
          autocomplete="new-password" required minlength="8" placeholder="••••••••"
      />

      <x-button type="submit" variant="primary" class="w-full">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
        Reset Password
      </x-button>
    </form>
  </div>
</body>
</html>
