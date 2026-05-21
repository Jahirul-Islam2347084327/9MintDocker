<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #111827; color: #ffffff; padding: 30px 20px; text-align: center; }
        .header img { max-height: 50px; margin-bottom: 15px; display: block; margin-left: auto; margin-right: auto; }
        .header h2 { margin: 0; font-size: 22px; font-weight: 600; letter-spacing: 0.5px; }
        .content { padding: 40px 30px; color: #374151; line-height: 1.6; font-size: 16px; }
        .notification-box { border-left: 4px solid #3b82f6; background-color: #f9fafb; padding: 15px 20px; margin: 25px 0; font-style: italic; color: #111827; border-radius: 0 6px 6px 0; }
        .button-container { text-align: center; margin-top: 35px; margin-bottom: 10px; }
        .button { background-color: #3b82f6; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; transition: background-color 0.2s; }
        .button:hover { background-color: #2563eb; }
        .footer { background-color: #f9fafb; color: #6b7280; padding: 25px 20px; text-align: center; font-size: 13px; border-top: 1px solid #e5e7eb; line-height: 1.5; }
        .footer a { color: #3b82f6; text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('images/9mint.png')) }}" alt="9Mint Logo">
            <h2>New Notification</h2>
        </div>
        <div class="content">
            <p>Hey there,</p>
            <p>You have a new notification waiting for you on 9Mint:</p>
            
            <div class="notification-box">
                "{{ $notificationData['message'] ?? 'Check your dashboard for updates.' }}"
            </div>

            <div class="button-container">
                <a href="{{ url('/notifications') }}" class="button">View on 9Mint</a>
            </div>
        </div>
        <div class="footer">
            <p>You are receiving this email because you opted into 9Mint notifications.</p>
            <p>To stop receiving these emails, <a href="{{ url('/profile/settings') }}">update your preferences here</a> or reply to this email with "UNSUBSCRIBE".</p>
        </div>
    </div>
</body>
</html>