# Usage examples - LibreMailApi with SMTP

This guide provides practical examples of how to use LibreMailApi with SMTP support for real email sending.

## Starting the server

```bash
# Install dependencies
composer install

# Start development server
php -S localhost:8081 index.php

# Or use composer command
composer start
```

## Examples with cURL

### 1. Simple email sending

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='Sender <sender@example.com>' \
    -F to='recipient@example.com' \
    -F subject='Hello World' \
    -F text='This is a simple test email.'
```

### 2. Email with HTML and text

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='sender@example.com' \
    -F to='recipient@example.com' \
    -F subject='HTML Email Test' \
    -F text='This is the text version.' \
    -F html='<h1>HTML Version</h1><p>This is the <strong>HTML</strong> version.</p>'
```

### 3. Email with multiple recipients

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='sender@example.com' \
    -F to='user1@example.com,user2@example.com' \
    -F cc='manager@example.com' \
    -F bcc='admin@example.com' \
    -F subject='Multiple Recipients' \
    -F text='This email goes to multiple people.'
```

### 4. Email with tags and tracking

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='sender@example.com' \
    -F to='recipient@example.com' \
    -F subject='Tagged Email' \
    -F text='This email has tags and tracking.' \
    -F 'o:tag=newsletter,marketing' \
    -F 'o:tracking=yes' \
    -F 'o:tracking-clicks=yes' \
    -F 'o:tracking-opens=yes'
```

### 5. Email with custom headers

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='sender@example.com' \
    -F to='recipient@example.com' \
    -F subject='Custom Headers' \
    -F text='Email with custom headers.' \
    -F 'h:X-Campaign-ID=12345' \
    -F 'h:X-User-ID=67890' \
    -F 'v:custom-data={"user_type":"premium","source":"website"}'
```

### 6. Message retrieval

```bash
# First get the storage ID from log or sending response
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/domains/sandbox.libremailapi.org/messages/msg_STORAGE_KEY_HERE
```

### 7. Queue status

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/domains/sandbox.libremailapi.org/sending_queues
```

### 8. Delete scheduled messages

```bash
curl -X DELETE --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/envelopes
```

## SMTP examples (real sending)

### 9. Check SMTP status

```bash
# Check if SMTP is enabled and configured
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/smtp
```

Example response:
```json
{
    "smtp_enabled": true,
    "smtp_configured": true,
    "smtp_host": "smtp.gmail.com",
    "smtp_port": 587,
    "smtp_encryption": "tls",
    "smtp_username": "your-email@gmail.com"
}
```

### 10. Test SMTP connection

```bash
# Test connection to SMTP server
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/smtp/test
```

Success response:
```json
{
    "message": "SMTP connection successful",
    "smtp_enabled": true,
    "smtp_host": "smtp.gmail.com",
    "smtp_port": 587,
    "smtp_encryption": "tls",
    "success": true
}
```

Error response:
```json
{
    "message": "SMTP connection failed",
    "smtp_enabled": true,
    "smtp_host": "smtp.gmail.com",
    "smtp_port": 587,
    "smtp_encryption": "tls",
    "success": false,
    "error": "Authentication failed"
}
```

### 11. Real email sending with SMTP response

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='Your Name <your-email@gmail.com>' \
    -F to='recipient@example.com' \
    -F subject='Real Email Test' \
    -F text='This email will be sent via SMTP!'
```

Response with SMTP enabled:
```json
{
    "id": "<uuid@sandbox.libremailapi.org>",
    "message": "Queued. Thank you.",
    "smtp_status": "sent"
}
```

Response with SMTP error:
```json
{
    "id": "<uuid@sandbox.libremailapi.org>",
    "message": "Queued. Thank you.",
    "smtp_status": "failed",
    "smtp_error": "Invalid credentials"
}
```

## Examples with PHP and Guzzle

### Basic setup

```php
<?php
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'http://localhost:8081',
    'auth' => ['api', 'key-test123456789']
]);
```

### Simple email sending

```php
$response = $client->post('/v3/sandbox.libremailapi.org/messages', [
    'form_params' => [
        'from' => 'sender@example.com',
        'to' => 'recipient@example.com',
        'subject' => 'Test Email',
        'text' => 'Hello from PHP!'
    ]
]);

$result = json_decode($response->getBody(), true);
echo "Message ID: " . $result['id'] . "\n";
```

### Email with attachment

```php
$response = $client->post('/v3/sandbox.libremailapi.org/messages', [
    'multipart' => [
        ['name' => 'from', 'contents' => 'sender@example.com'],
        ['name' => 'to', 'contents' => 'recipient@example.com'],
        ['name' => 'subject', 'contents' => 'Email with Attachment'],
        ['name' => 'text', 'contents' => 'Please find the attachment.'],
        [
            'name' => 'attachment',
            'contents' => fopen('/path/to/file.pdf', 'r'),
            'filename' => 'document.pdf'
        ]
    ]
]);
```

### SMTP testing with PHP

```php
// Test SMTP status
$response = $client->get('/v3/sandbox.libremailapi.org/smtp');
$smtpStatus = json_decode($response->getBody(), true);

if ($smtpStatus['smtp_enabled']) {
    echo "SMTP is enabled\n";

    // Test SMTP connection
    $response = $client->get('/v3/sandbox.libremailapi.org/smtp/test');
    $connectionTest = json_decode($response->getBody(), true);

    if ($connectionTest['success']) {
        echo "SMTP connection successful\n";

        // Send real email
        $response = $client->post('/v3/sandbox.libremailapi.org/messages', [
            'form_params' => [
                'from' => 'Your Name <your-email@gmail.com>',
                'to' => 'recipient@example.com',
                'subject' => 'Real Email from PHP',
                'text' => 'This email will be sent via SMTP!',
                'html' => '<h1>Real Email</h1><p>This email will be sent via <strong>SMTP</strong>!</p>'
            ]
        ]);

        $result = json_decode($response->getBody(), true);
        echo "Message ID: " . $result['id'] . "\n";
        echo "SMTP Status: " . ($result['smtp_status'] ?? 'unknown') . "\n";

        if (isset($result['smtp_error'])) {
            echo "SMTP Error: " . $result['smtp_error'] . "\n";
        }
    } else {
        echo "SMTP connection failed: " . $connectionTest['error'] . "\n";
    }
} else {
    echo "SMTP is not enabled - simulation mode\n";
}
```

### Error handling

```php
try {
    $response = $client->post('/v3/sandbox.libremailapi.org/messages', [
        'form_params' => [
            'from' => 'invalid-email',  // Invalid email
            'to' => 'recipient@example.com',
            'subject' => 'Test',
            'text' => 'This will fail'
        ]
    ]);
} catch (GuzzleHttp\Exception\ClientException $e) {
    $error = json_decode($e->getResponse()->getBody(), true);
    echo "Error: " . $error['message'] . "\n";
}
```

## Examples with JavaScript/Node.js

### Setup with Axios

```javascript
const axios = require('axios');

const client = axios.create({
    baseURL: 'http://localhost:8081',
    auth: {
        username: 'api',
        password: 'key-test123456789'
    }
});
```

### Email sending

```javascript
const FormData = require('form-data');

const form = new FormData();
form.append('from', 'sender@example.com');
form.append('to', 'recipient@example.com');
form.append('subject', 'Test from Node.js');
form.append('text', 'Hello from JavaScript!');

client.post('/v3/sandbox.libremailapi.org/messages', form, {
    headers: form.getHeaders()
})
.then(response => {
    console.log('Message ID:', response.data.id);
})
.catch(error => {
    console.error('Error:', error.response.data);
});
```

## Examples with Python

### Setup with Requests

```python
import requests

def send_email():
    response = requests.post(
        'http://localhost:8081/v3/sandbox.libremailapi.org/messages',
        auth=('api', 'key-test123456789'),
        data={
            'from': 'sender@example.com',
            'to': 'recipient@example.com',
            'subject': 'Test from Python',
            'text': 'Hello from Python!'
        }
    )

    if response.status_code == 200:
        result = response.json()
        print(f"Message ID: {result['id']}")
    else:
        print(f"Error: {response.text}")

send_email()
```

## Testing and debugging

### Running tests

```bash
# Run all tests
php tests/ApiTest.php

# Simple test
php test_simple.php
```

### Log checking

```bash
# View logs in real time
tail -f logs/libre-mail-api.log

# View saved messages
ls -la storage/messages/

# View message content
cat storage/messages/msg_STORAGE_KEY.json | jq .
```

### Server monitoring

The built-in PHP server will show incoming requests:

```
[Sat Jun  7 21:31:04 2025] PHP 8.3.21 Development Server (http://localhost:8081) started
[Sat Jun  7 21:31:18 2025] 127.0.0.1:43240 Accepted
[Sat Jun  7 21:31:18 2025] 127.0.0.1:43240 Closing
```

## Advanced configuration

### Configuration modification

Edit `config/config.php` to customize:

- Authentication credentials
- Attachment size limits
- Storage paths
- Logging levels

### Domain customization

The API supports any domain in the URL. Examples:

```bash
# Custom domain
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/mydomain.com/messages \
    -F from='sender@mydomain.com' \
    -F to='user@example.com' \
    -F subject='Custom Domain' \
    -F text='Using custom domain'
```

## Troubleshooting

### Common issues

1. **Server not responding**: Check that it's running on port 8081
2. **Error 401**: Check username/password (api/key-test123456789)
3. **Error 400**: Verify that all required fields are present
4. **Attachments not working**: Use multipart/form-data for uploads

### SMTP issues

1. **SMTP not enabled**:
   - Check `'enabled' => true` in config/config.php
   - Verify that host, username and password are configured

2. **Gmail authentication error**:
   - Enable 2-factor authentication
   - Generate an App Password (don't use regular password)
   - Use the 16-character App Password in configuration

3. **Connection error**:
   - Check SMTP host and port
   - Check firewall settings
   - Try with `'verify_peer' => false` for testing

4. **Emails not arriving**:
   - Check spam/junk folder
   - Verify that sender address is valid
   - Check logs for SMTP errors

5. **Connection timeout**:
   - Increase `'timeout' => 60` in configuration
   - Check internet connection
   - Try alternative port (465 for SSL)

### Debug

Enable debug by adding verbose parameters to curl:

```bash
curl -v --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='test@example.com' \
    -F to='recipient@example.com' \
    -F subject='Debug Test' \
    -F text='Debug message'
```
