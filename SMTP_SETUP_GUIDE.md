# SMTP setup guide - LibreMailApi

This guide will help you configure real email sending via SMTP in LibreMailApi.

## Quick setup

### 1. Copy configuration file

```bash
cp config/config.example.php config/config.php
```

### 2. Configure SMTP

Edit `config/config.php` and set the SMTP section:

```php
'smtp' => [
    'enabled' => true, // IMPORTANT: Enable SMTP
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'your-email@gmail.com',
    'password' => 'your-app-password', // NOT your regular password!
    'from_name' => 'Your Name',
    'from_email' => 'your-email@gmail.com',
]
```

### 3. Test configuration

```bash
# Start the server
php -S localhost:8081 index.php

# In another terminal, test SMTP
php examples/test_smtp.php your-test-email@example.com
```

## Provider-specific configurations

### Gmail

**Prerequisites:**
1. Enable 2-factor authentication
2. Generate an App Password (16 characters)

**Configuration:**
```php
'smtp' => [
    'enabled' => true,
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'your-email@gmail.com',
    'password' => 'abcd efgh ijkl mnop', // App Password
    'from_name' => 'Your Name',
    'from_email' => 'your-email@gmail.com',
]
```

**How to get Gmail App Password:**
1. Go to https://myaccount.google.com/security
2. Enable "2-Step Verification"
3. Go to "App passwords"
4. Generate a new password for "Mail"
5. Use the generated 16-character password

### Outlook/Hotmail

```php
'smtp' => [
    'enabled' => true,
    'host' => 'smtp-mail.outlook.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'your-email@outlook.com',
    'password' => 'your-password',
    'from_name' => 'Your Name',
    'from_email' => 'your-email@outlook.com',
]
```

### Yahoo Mail

```php
'smtp' => [
    'enabled' => true,
    'host' => 'smtp.mail.yahoo.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'your-email@yahoo.com',
    'password' => 'your-app-password', // Requires App Password
    'from_name' => 'Your Name',
    'from_email' => 'your-email@yahoo.com',
]
```

### Custom SMTP server

```php
'smtp' => [
    'enabled' => true,
    'host' => 'mail.yourdomain.com',
    'port' => 587, // or 25, 465
    'encryption' => 'tls', // or 'ssl', null
    'auth' => true, // or false if not required
    'username' => 'noreply@yourdomain.com',
    'password' => 'your-password',
    'from_name' => 'Your Company',
    'from_email' => 'noreply@yourdomain.com',
]
```

## Testing and verification

### Automatic testing

```bash
# Complete test with included script
php examples/test_smtp.php your-email@example.com

# Test with custom server
php examples/test_smtp.php your-email@example.com http://localhost:8081
```

### Manual testing with cURL

```bash
# 1. Check SMTP status
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/smtp

# 2. Test connection
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/smtp/test

# 3. Send test email
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='Test <your-email@gmail.com>' \
    -F to='recipient@example.com' \
    -F subject='Test SMTP' \
    -F text='Test email sent via SMTP!'
```

## Troubleshooting

### Common errors

**"SMTP connection failed"**
- Check host and port
- Verify username/password
- Check internet connection

**"Authentication failed"**
- For Gmail: use App Password, not regular password
- Verify that 2-factor authentication is enabled
- Check username (must be complete email)

**"Connection timeout"**
- Increase timeout in configuration
- Check firewall/proxy
- Try alternative port (465 for SSL)

**Emails not arriving**
- Check spam folder
- Verify valid sender address
- Check logs for errors: `tail -f logs/libre-mail-api.log`

### Advanced debugging

Enable SMTP debug in configuration:

```php
'smtp' => [
    // ... other configurations
    'debug' => true, // Enable debug output
    'verify_peer' => false, // For testing only, disable SSL verification
]
```

### Logging and monitoring

```bash
# Monitor logs in real time
tail -f logs/libre-mail-api.log

# Search for SMTP errors
grep "SMTP" logs/libre-mail-api.log

# Check saved messages
ls -la storage/messages/
```

## Dual mode

The system works in dual mode:

1. **Simulation**: Always active, saves messages in JSON
2. **SMTP**: If enabled, sends real emails

This allows to:
- Maintain complete logs even with SMTP
- Continue working if SMTP fails
- Test without sending real emails (SMTP disabled)

## Security

**Recommendations:**
- Always use App Password for Gmail/Yahoo
- Don't commit passwords in code
- Use environment variables for sensitive credentials
- Enable SSL/TLS when possible
- Limit access to configuration file

**Example with environment variables:**
```php
'smtp' => [
    'enabled' => getenv('SMTP_ENABLED') === 'true',
    'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'username' => getenv('SMTP_USERNAME'),
    'password' => getenv('SMTP_PASSWORD'),
]
```

## Support

For issues or questions:
1. Check logs: `logs/libre-mail-api.log`
2. Test connection: `php examples/test_smtp.php`
3. Check email provider configuration
4. Consult SMTP provider documentation
