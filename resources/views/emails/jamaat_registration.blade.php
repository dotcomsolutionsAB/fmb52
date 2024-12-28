<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to FMB 52!</h1>
        </div>
        <div class="content">
            <p>Welcome aboard, {{ $admin_name }}!</p>
            <p>We're excited to have you with us. Your 1-month free trial starts today and lasts until {{ $validity }}.</p>
            <p>Here are your login credentials:</p>
            <ul>
                <li><strong>Email:</strong> {{ $admin_name }}</li>
                <li><strong>Password:</strong> {{ $password }}</li>
            </ul>
            <p>Feel free to explore all our features and enjoy the experience, completely risk-free!</p>
        </div>
        <div class="footer">
            <p>Thank you,<br>FMB 52 Team</p>
        </div>
    </div>
</body>
</html>
