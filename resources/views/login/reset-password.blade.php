<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | Bubog NHS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; font-family: 'Inter', sans-serif; }

    body {
      background: linear-gradient(180deg, #1E88E5 0%, #80DEEA 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
    }

    .back-link {
      width: 100%;
      max-width: 460px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 14px;
      font-weight: 600;
      color: rgba(255,255,255,0.92);
      text-decoration: none;
      margin-bottom: 16px;
      transition: color .2s, transform .2s;
    }
    .back-link:hover { color: #fff; transform: translateX(-3px); }

    .card {
      width: 100%;
      max-width: 460px;
      background: #fff;
      border-radius: 22px;
      box-shadow: 0 24px 64px rgba(0,0,0,0.22);
      padding: 44px 40px;
    }

    .icon-circle {
      width: 56px; height: 56px; border-radius: 50%;
      background: #EBF5FF; color: #1E88E5;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; margin-bottom: 18px;
    }

    h2 { font-size: 24px; font-weight: 700; color: #1a1a1a; margin-bottom: 6px; }
    .sub { font-size: 13px; color: #888; margin-bottom: 24px; line-height: 1.5; }

    .ig { margin-bottom: 18px; }
    .ig label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 5px; }
    .ig .iw { position: relative; display: flex; align-items: center; }
    .ig .iw i.ico { position: absolute; left: 13px; font-size: 15px; color: #aaa; pointer-events: none; }
    .ig input {
      width: 100%; padding: 13px 13px 13px 40px;
      background: #F1F5F9; border: 1.5px solid transparent;
      border-radius: 11px; font-size: 14px; color: #333;
      outline: none; transition: .2s;
    }
    .ig input:focus { background: #fff; border-color: #1E88E5; box-shadow: 0 0 0 3px rgba(30,136,229,0.12); }
    .eye { position: absolute; right: 13px; color: #aaa; font-size: 16px; cursor: pointer; }

    .btn-main {
      width: 100%; padding: 15px; background: #1E88E5; color: #fff;
      border: none; border-radius: 11px; font-size: 16px; font-weight: 700;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      cursor: pointer; transition: background .2s, transform .1s;
    }
    .btn-main:hover { background: #1565C0; }
    .btn-main:active { transform: scale(0.99); }
  </style>
</head>
<body>

<a href="{{ route(($portalType ?? 'student').'.login') }}" class="back-link">
  <i class="fa-solid fa-arrow-left"></i> Back to Sign In
</a>

<div class="card">
  <div class="icon-circle"><i class="fa-solid fa-lock-open"></i></div>
  <h2>Set a new password</h2>
  <p class="sub">Choose a new password for your {{ $portalType ?? 'student' }} account.</p>

  <form method="POST" action="{{ route(($portalType ?? 'student').'.password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="ig">
      <label>Email address</label>
      <div class="iw">
        <i class="fa-solid fa-envelope ico"></i>
        <input type="email" name="email" placeholder="Enter your email" autocomplete="off" readonly required value="{{ old('email', $email ?? '') }}" style="background:#e5e7eb;cursor:not-allowed">
      </div>
      @error('email')<p style="color: red; font-size: 12px; margin-top: 5px;">{{ $message }}</p>@enderror
    </div>

    <div class="ig">
      <label>New password</label>
      <div class="iw">
        <i class="fa-solid fa-lock ico"></i>
        <input type="password" name="password" id="pw" placeholder="••••••••" autocomplete="new-password" required minlength="8">
        <i class="fa-solid fa-eye eye" onclick="togglePw('pw', this)"></i>
      </div>
      @error('password')<p style="color: red; font-size: 12px; margin-top: 5px;">{{ $message }}</p>@enderror
    </div>

    <div class="ig">
      <label>Confirm new password</label>
      <div class="iw">
        <i class="fa-solid fa-lock ico"></i>
        <input type="password" name="password_confirmation" id="pw2" placeholder="••••••••" autocomplete="new-password" required minlength="8">
        <i class="fa-solid fa-eye eye" onclick="togglePw('pw2', this)"></i>
      </div>
    </div>

    <button type="submit" class="btn-main">
      <i class="fa-solid fa-check"></i> Reset Password
    </button>
  </form>
</div>

<script>
function togglePw(id, icon) {
  const p = document.getElementById(id);
  p.type = p.type === 'password' ? 'text' : 'password';
  icon.className = p.type === 'password' ? 'fa-solid fa-eye eye' : 'fa-solid fa-eye-slash eye';
}
</script>
</body>
</html>
