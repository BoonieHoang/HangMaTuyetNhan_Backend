<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Xác thực tài khoản Tuyết Nhàn</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f7f7f7; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #6F2A2A 0%, #8B1A1A 100%); color: #ffffff; text-align: center; padding: 30px 20px; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 1px; }
        .content { padding: 40px 30px; line-height: 1.6; }
        .code-box { background: #fdf8f2; border: 1px dashed #c49a45; border-radius: 6px; padding: 20px; text-align: center; margin: 30px 0; }
        .code { font-size: 32px; font-weight: bold; color: #6F2A2A; letter-spacing: 5px; }
        .footer { background: #f1f1f1; text-align: center; padding: 20px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CỬA HÀNG ĐỒ LỄ TUYẾT NHÀN</h1>
        </div>
        <div class="content">
            <p>Xin chào,</p>
            <p>Cảm ơn bạn đã đăng ký tài khoản tại Đồ Lễ Tuyết Nhàn. Để hoàn tất quá trình xác thực người dùng, vui lòng nhập mã OTP dưới đây vào trang xác thực:</p>
            <div class="code-box">
                <div class="code">{{ $code }}</div>
                <p style="margin: 10px 0 0 0; font-size: 14px; color: #666;">Mã này có hiệu lực trong vòng 15 phút.</p>
            </div>
            <p>Nếu bạn không thực hiện đăng ký tài khoản này, vui lòng bỏ qua email này.</p>
            <p>Trân trọng,<br>Cửa hàng Đồ Lễ Tuyết Nhàn</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Cửa hàng Đồ Lễ Tuyết Nhàn. All rights reserved.
        </div>
    </div>
</body>
</html>
