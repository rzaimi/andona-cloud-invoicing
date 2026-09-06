<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Willkommen bei AndoBill</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.7;
            color: #1a1a1a;
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
            background-color: #ffffff;
        }
        .header {
            margin-bottom: 48px;
        }
        .header h1 {
            color: #1a1a1a;
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .company-name {
            font-size: 13px;
            color: #666;
            font-weight: 400;
        }
        .content p {
            margin: 0 0 20px 0;
            color: #1a1a1a;
            font-size: 15px;
        }
        .details {
            margin: 32px 0;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
        }
        .details td {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .details tr:last-child td {
            border-bottom: none;
        }
        .details td:first-child {
            font-weight: 400;
            color: #666;
        }
        .details td:last-child {
            text-align: right;
            color: #1a1a1a;
        }
        .button {
            display: inline-block;
            margin: 8px 0 24px;
            padding: 12px 24px;
            background-color: #111827;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
        }
        .footer {
            margin-top: 64px;
            padding-top: 32px;
            border-top: 1px solid #f0f0f0;
            font-size: 12px;
            color: #999;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Willkommen bei AndoBill</h1>
        <div class="company-name">{{ $company->name }}</div>
    </div>

    <div class="content">
        <p>Hallo {{ $user->name }},</p>

        <p>für Sie wurde ein Administratorkonto bei <strong>{{ $company->name }}</strong> eingerichtet. Legen Sie zunächst Ihr persönliches Passwort fest:</p>

        <div class="details">
            <table>
                <tr>
                    <td>Firma</td>
                    <td>{{ $company->name }}</td>
                </tr>
                <tr>
                    <td>E-Mail</td>
                    <td>{{ $user->email }}</td>
                </tr>
            </table>
        </div>

        <p>
            <a class="button" href="{{ $setupUrl }}">Passwort festlegen</a>
        </p>

        <p>Der Link ist {{ $validDays }} Tage gültig. Danach können Sie sich unter <a href="{{ $loginUrl }}">{{ $loginUrl }}</a> anmelden.</p>

        <p>Falls Sie diesen Link nicht angefordert haben, können Sie diese E-Mail ignorieren.</p>

        <p>Mit freundlichen Grüßen<br>AndoBill</p>
    </div>

    <div class="footer">
        <p>Diese E-Mail wurde automatisch erstellt, weil ein Administratorkonto für {{ $company->name }} angelegt wurde.</p>
    </div>
</body>
</html>
