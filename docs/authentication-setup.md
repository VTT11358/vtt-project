# Google sign-in and email verification setup

## Local configuration

Copy `config/auth.local.example.php` to the ignored file `config/auth.local.php`, then set your credentials. Do not commit OAuth secrets or a Gmail App Password.

- `google_client_id` and `google_client_secret`: credentials for a Google OAuth Web application.
- `google_redirect_uri`: register this exact URL under the OAuth client's authorized redirect URIs:
  `http://localhost/laptop-store/google-callback.php`
- `app_url`: the absolute base URL used to create email verification links.
- `smtp_username`, `smtp_password`, and `mail_from_email`: use the Gmail account that sends verification mail. The password must be a Google App Password, not the account password.
- Keep `smtp_host` as `smtp.gmail.com` and `smtp_port` as `587` for STARTTLS.

For Gmail SMTP, enable 2-Step Verification on the sender account and create an App Password. For Google sign-in, configure the OAuth consent screen and create a Web application OAuth client. Add the local callback above to its authorized redirect URIs.

The email verification migration is `database_migrations/add_email_verification.sql`. Existing accounts remain verified; newly registered password accounts must confirm their email. Google sign-in uses Google's verified email claim and can activate an existing matching account.

Password recovery uses the same Gmail SMTP settings. Apply `database_migrations/add_password_reset_tokens.sql`. Forgot-password and profile password-change links expire after one hour, are single-use, and can be reissued after a 60-second cooldown. The reset form works for Google-created accounts too, allowing them to set a password for email/password sign-in.

Profile edits save the name, phone number, and address immediately. A changed email is kept pending until the new address is confirmed through a link sent by Gmail SMTP; links expire after 24 hours and can be resent after a 60-second cooldown. Apply `database_migrations/add_email_change_tokens.sql`.

For production, use the HTTPS site URL in both `app_url` and `google_redirect_uri`, register that exact callback in Google Cloud, and keep `config/auth.local.php` private.

For VNPay, copy `config/vnpay.example.php` to the ignored file `config/vnpay.php` and set the merchant code and hash secret provided by VNPay. Keep `config/vnpay.php` private.
