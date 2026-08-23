<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edu Scholar OTP</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { background: #F8F9FF; font-family: 'Segoe UI', Arial, sans-serif; }
  .wrapper { max-width: 520px; margin: 40px auto; }
  .card { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 24px rgba(79,70,229,0.10); }
  .header { background: linear-gradient(135deg, #4F46E5, #7C3AED); padding: 36px 40px 28px; text-align: center; }
  .header h1 { color: #fff; font-size: 26px; font-weight: 700; letter-spacing: -0.5px; }
  .header p  { color: rgba(255,255,255,0.8); font-size: 14px; margin-top: 4px; }
  .body { padding: 36px 40px; }
  .greeting { color: #1A1833; font-size: 16px; margin-bottom: 16px; }
  .msg { color: #5A576E; font-size: 14px; line-height: 1.7; margin-bottom: 28px; }
  .otp-box { background: #F0EFFF; border: 2px dashed #4F46E5; border-radius: 12px; text-align: center; padding: 20px; margin-bottom: 24px; }
  .otp-label { color: #5A576E; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
  .otp-code { color: #4F46E5; font-size: 40px; font-weight: 800; letter-spacing: 10px; }
  .expire { color: #AAABBE; font-size: 12px; margin-top: 8px; }
  .warning { background: #FFF7ED; border-left: 3px solid #F59E0B; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; }
  .warning p { color: #92400E; font-size: 13px; }
  .footer { background: #F8F9FF; padding: 20px 40px; text-align: center; border-top: 1px solid #EEF0F6; }
  .footer p { color: #AAABBE; font-size: 12px; line-height: 1.6; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="card">
    <div class="header">
      <h1>🎓 Edu Scholar</h1>
      <p>{{ $type === 'registration' ? 'Account Verification' : 'Password Reset' }}</p>
    </div>
    <div class="body">
      @if($userName)
      <p class="greeting">Hello, {{ $userName }}!</p>
      @endif
      <p class="msg">
        @if($type === 'registration')
          Thank you for joining Edu Scholar! Use the OTP below to verify your email address and complete your registration.
        @else
          We received a request to reset your Edu Scholar password. Use the OTP below to proceed. If you didn't request this, please ignore this email.
        @endif
      </p>
      <div class="otp-box">
        <p class="otp-label">Your One-Time Password</p>
        <p class="otp-code">{{ $otp }}</p>
        <p class="expire">⏱ Expires in <strong>10 minutes</strong></p>
      </div>
      <div class="warning">
        <p>⚠️ Never share this OTP with anyone. Edu Scholar staff will never ask for your OTP.</p>
      </div>
    </div>
    <div class="footer">
      <p>© {{ date('Y') }} Edu Scholar. All rights reserved.<br>
      This is an automated email. Please do not reply.</p>
    </div>
  </div>
</div>
</body>
</html>
