<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="60">
  <title>Under Maintenance | MathLearn</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; font-family: 'Inter', -apple-system, sans-serif; }

    body {
      background: linear-gradient(135deg, #1e4e7f 0%, #197a86 52%, #16906e 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      overflow: hidden;
      position: relative;
    }

    /* Floating math symbols drifting in the background */
    .symbol {
      position: absolute;
      color: rgba(255,255,255,0.18);
      font-weight: 800;
      user-select: none;
      animation: drift 14s ease-in-out infinite;
    }
    .symbol:nth-child(1) { top: 12%;  left: 8%;  font-size: 54px; animation-delay: 0s;   }
    .symbol:nth-child(2) { top: 68%;  left: 12%; font-size: 38px; animation-delay: 2.5s; }
    .symbol:nth-child(3) { top: 22%;  right: 10%; font-size: 46px; animation-delay: 1s;   }
    .symbol:nth-child(4) { top: 74%;  right: 14%; font-size: 60px; animation-delay: 3.5s; }
    .symbol:nth-child(5) { top: 45%;  left: 4%;  font-size: 30px; animation-delay: 5s;   }
    .symbol:nth-child(6) { top: 5%;   right: 30%; font-size: 32px; animation-delay: 4s;   }

    @keyframes drift {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50%      { transform: translateY(-22px) rotate(8deg); }
    }

    .card {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 460px;
      background: white;
      border-radius: 22px;
      box-shadow: 0 24px 64px rgba(0,0,0,0.22);
      padding: 48px 40px;
      text-align: center;
    }

    /* Spinning gear + pulsing ring */
    .gear-wrap {
      position: relative;
      width: 96px;
      height: 96px;
      margin: 0 auto 22px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .gear-ring {
      position: absolute;
      inset: 0;
      border-radius: 50%;
      background: #e3f2f0;
      animation: pulse-ring 2s ease-in-out infinite;
    }
    @keyframes pulse-ring {
      0%, 100% { transform: scale(1);   opacity: 1;   }
      50%      { transform: scale(1.12); opacity: 0.7; }
    }
    .gear-icon {
      position: relative;
      font-size: 40px;
      color: #197a86;
      animation: spin 3.5s linear infinite;
    }
    @keyframes spin {
      from { transform: rotate(0deg); }
      to   { transform: rotate(360deg); }
    }

    h1 {
      font-size: 24px;
      font-weight: 800;
      color: #0e2e4c;
      margin-bottom: 8px;
      letter-spacing: -0.3px;
    }

    p.sub {
      font-size: 14px;
      color: #666;
      line-height: 1.6;
      margin-bottom: 22px;
    }

    .dots { display: flex; align-items: center; justify-content: center; gap: 7px; margin-bottom: 6px; }
    .dots span {
      width: 9px; height: 9px; border-radius: 50%;
      background: #16906e;
      animation: bounce 1.4s ease-in-out infinite;
    }
    .dots span:nth-child(2) { animation-delay: 0.15s; background: #1e4e7f; }
    .dots span:nth-child(3) { animation-delay: 0.3s;  background: #16906e; }
    @keyframes bounce {
      0%, 80%, 100% { transform: translateY(0);    opacity: 0.5; }
      40%           { transform: translateY(-10px); opacity: 1;   }
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 20px;
      padding: 6px 14px;
      border-radius: 99px;
      background: #eaf1f7;
      font-size: 12px;
      font-weight: 600;
      color: #0f7355;
    }
  </style>
</head>
<body>

  <span class="symbol">∑</span>
  <span class="symbol">π</span>
  <span class="symbol">√</span>
  <span class="symbol">∞</span>
  <span class="symbol">÷</span>
  <span class="symbol">Δ</span>

  <div class="card">
    <div class="gear-wrap">
      <div class="gear-ring"></div>
      <i class="fa-solid fa-gear gear-icon"></i>
    </div>

    <h1>We'll be right back</h1>
    <p class="sub">
      MathLearn is under scheduled maintenance so we can make it
      faster for you. This won't take long.
    </p>

    <div class="dots"><span></span><span></span><span></span></div>

    <div class="badge">
      <i class="fa-solid fa-arrows-rotate"></i>
      This page refreshes automatically
    </div>
  </div>

</body>
</html>
