<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            padding: 20px;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background-color: #ffffff;
            padding: 30px 40px;
            border-bottom: 1px solid #e5e7eb;
            position: relative;
        }

        .logo-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            width: 100%;
        }

        .logo-left {
            max-height: 50px;
            width: auto;
        }

        .logo-right {
            max-height: 50px;
            width: auto;
        }

        .content {
            padding: 40px;
            text-align: center;
        }

        .greeting {
            color: #1f2937;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .greeting-name {
            font-weight: 600;
            color: #E30613;
        }

        .title {
            color: #1f2937;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .message {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .button-container {
            margin: 30px 0;
        }

        .reset-button {
            display: inline-block;
            background-color: #E30613;
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 48px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }

        .reset-button:hover {
            background-color: #c20511;
        }

        .warning-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px 20px;
            margin: 30px 0;
            text-align: left;
        }

        .warning-text {
            color: #92400e;
            font-size: 14px;
            line-height: 1.6;
        }

        .alternative-text {
            color: #9ca3af;
            font-size: 14px;
            margin-top: 30px;
            margin-bottom: 10px;
        }

        .url-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 12px;
            word-break: break-all;
            font-size: 13px;
            color: #3b82f6;
            margin: 0 20px;
        }

        .footer {
            background-color: #f9fafb;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer-text {
            color: #9ca3af;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .footer-credit {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }

        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 20px 0;
        }

        .expiry-notice {
            color: #9ca3af;
            font-size: 13px;
            font-style: italic;
            margin-top: 20px;
        }

        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }

            .header {
                padding: 20px;
            }

            .footer {
                padding: 20px;
            }

            .title {
                font-size: 24px;
            }

            .reset-button {
                padding: 14px 32px;
                font-size: 15px;
            }

            .logo-left, .logo-right {
                max-height: 40px;
            }

            .warning-box {
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header with Logos -->
        <div class="header">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="left" valign="top" width="50%">
                        {{-- Logo Telkom di kiri --}}
                        <img src="{{ $message->embed(public_path('img/logo-telkom.png')) }}" alt="Telkom Indonesia" class="logo-left" style="max-height: 50px; width: auto; display: block;">
                    </td>
                    <td align="right" valign="top" width="50%">
                        {{-- Logo TR3 di kanan --}}
                        <img src="{{ $message->embed(public_path('img/logo-treg3.png')) }}" alt="TREG 3" class="logo-right" style="max-height: 50px; width: auto; display: block;">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Main Content -->
        <div class="content">
            <p class="greeting">
                Halo, <span class="greeting-name">{{ $user->name }}</span>
            </p>

            <h1 class="title">Reset Password Akun Anda</h1>

            <p class="message">
                Kami menerima permintaan untuk mereset password akun RLEGS Anda. Klik tombol di bawah ini untuk membuat password baru.
            </p>

            <div class="button-container">
                <a href="{{ $resetUrl }}" class="reset-button">
                    Reset Password
                </a>
            </div>

            <div class="warning-box">
                <p class="warning-text">
                    <strong>Perhatian:</strong> Link reset password ini hanya berlaku selama 60 menit. Jika sudah kadaluarsa, silakan ajukan permintaan reset password baru.
                </p>
            </div>

            <p class="alternative-text">
                Atau salin dan tempel link ini ke browser Anda:
            </p>

            <div class="url-box">
                {{ $resetUrl }}
            </div>

            <p class="expiry-notice">
                Link ini akan kadaluarsa dalam 60 menit setelah email dikirim.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                Jika Anda tidak meminta reset password, abaikan email ini dan password Anda tidak akan berubah.
            </p>
            <div class="divider"></div>
            <p class="footer-credit">
                Tim IT Intern RLEGS TR 3
            </p>
        </div>
    </div>
</body>
</html>