<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Xác thực tài khoản Tuyết Nhàn</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f5;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        .header {
            background: linear-gradient(135deg, #6F2A2A 0%, #8B1A1A 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #c49a45;
        }
        .content {
            padding: 40px 30px;
            line-height: 1.6;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #111111;
        }
        .code-container {
            background: #fafafb;
            border: 2px dashed #c49a45;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .code-title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #666666;
            margin-bottom: 10px;
        }
        .code {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 6px;
            color: #6F2A2A;
            margin: 0;
        }
        .expiry {
            font-size: 13px;
            color: #ef4444;
            text-align: center;
            margin-top: 10px;
            font-weight: 500;
        }
        .footer {
            background-color: #fafafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #888888;
            border-top: 1px solid #f3f4f6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CỬA HÀNG ĐỒ LỄ TUYẾT NHÀN</h1>
        </div>
        <div class="content">
            <div class="greeting">Xin chào, {{ $userName }}!</div>
            <p>Cảm ơn bạn đã đăng ký tài khoản tại <strong>Đồ lễ Tuyết Nhàn</strong>. Để hoàn tất quá trình xác thực và kích hoạt tài khoản, vui lòng sử dụng mã xác nhận dưới đây:</p>
            
            <div class="code-container">
                <div class="code-title">Mã xác nhận của bạn</div>
                <div class="code">{{ $code }}</div>
                <div class="expiry">* Mã này có hiệu lực trong vòng 15 phút.</div>
            </div>

            <p>Nếu bạn không yêu cầu đăng ký tài khoản này, vui lòng bỏ qua email này.</p>
            <p>Trân trọng,<br><strong>Ban quản trị Đồ lễ Tuyết Nhàn</strong></p>
        </div>
        <div class="footer">
            Đây là email tự động, vui lòng không phản hồi email này.<br>
            © {{ date('Y') }} Đồ lễ Tuyết Nhàn. All rights reserved.
        </div>
    </div>
</body>
</html>
