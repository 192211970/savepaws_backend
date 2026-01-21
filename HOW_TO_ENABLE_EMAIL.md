# How to Enable Email Sending in XAMPP (Windows)

To allow your PHP scripts to send emails using your Gmail account, follow these steps:

## Step 1: Configure `php.ini`
1. Open `C:\xampp\php\php.ini` (or wherever your XAMPP is installed).
2. Search for `[mail function]`.
3. Comment out (add `;` before) `SMTP=localhost` and `smtp_port=25`.
4. Uncomment and set `sendmail_path`:
   ```ini
   sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
   ```
5. Save the file.

## Step 2: Configure `sendmail.ini`
1. Open `C:\xampp\sendmail\sendmail.ini`.
2. Update the following settings (if you use Gmail):
   ```ini
   [sendmail]
   smtp_server=smtp.gmail.com
   smtp_port=587
   error_logfile=error.log
   debug_logfile=debug.log
   auth_username=YOUR_GMAIL@gmail.com
   auth_password=YOUR_APP_PASSWORD
   force_sender=YOUR_GMAIL@gmail.com
   ```
   **Important**: `auth_password` is NOT your normal Gmail password. You must generate an **App Password** from your Google Account > Security > 2-Step Verification > App passwords.

## Step 3: Restart Apache
1. Open XAMPP Control Panel.
2. Stop and Start Apache.

## Troubleshooting
- Check `C:\xampp\sendmail\error.log` for any errors.
- If email is still failing, the app will return the OTP in the response message for testing purposes (as implemented in `forgot_password.php`).
