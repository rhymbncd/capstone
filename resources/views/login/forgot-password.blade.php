<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | Bubog NHS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-neutral-50 p-4 font-sans sm:p-6">

  <a href="{{ route(($portalType ?? 'student').'.login') }}" class="mb-4 inline-flex w-full max-w-[460px] items-center gap-1.5 text-[13px] font-semibold text-neutral-500 transition-colors duration-150 hover:text-neutral-900">
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to Sign In
  </a>

  <div class="w-full max-w-[460px] rounded-lg border border-neutral-200 bg-white p-8 shadow-overlay sm:p-10">
    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary-tint text-primary">
      <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
    </div>

    <h1 class="text-[24px] font-bold tracking-tight text-neutral-900">Forgot your password?</h1>
    <p class="mb-6 mt-1 text-[13px] leading-relaxed text-neutral-500">Enter the email address for your {{ $portalType ?? 'student' }} account and we'll send a password reset link to your inbox.</p>

    @if (session('success'))
      <x-alert variant="success" class="mb-5">{{ session('success') }}</x-alert>
    @endif

    <form method="POST" action="{{ route(($portalType ?? 'student').'.password.email') }}" class="space-y-4">
      @csrf
      <x-input
          label="Email address" name="email" type="email"
          autocomplete="email" required placeholder="Enter your email"
          value="{{ old('email') }}" :error="$errors->first('email')"
      />

      <x-button type="submit" variant="primary" class="w-full">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Send Reset Link
      </x-button>
    </form>

    <p class="mt-5 text-center text-[13px] text-neutral-500">Remembered it? <a href="{{ route(($portalType ?? 'student').'.login') }}" class="font-semibold text-primary hover:underline">Sign in</a></p>
  </div>
</body>
</html>
