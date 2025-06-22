# Esempi di utilizzo - LibreMailApi con SMTP

Questa guida fornisce esempi pratici di come utilizzare LibreMailApi con supporto SMTP per l'invio reale di email.

## Avvio del server

```bash
# Installa le dipendenze
composer install

# Avvia il server di sviluppo
php -S localhost:8081 index.php

# Oppure usa il comando composer
composer start
```

## Esempi con cURL

### 1. Invio email semplice

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='Sender <sender@example.com>' \
    -F to='recipient@example.com' \
    -F subject='Hello World' \
    -F text='This is a simple test email.'
```

### 2. Email con HTML e testo

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='sender@example.com' \
    -F to='recipient@example.com' \
    -F subject='HTML Email Test' \
    -F text='This is the text version.' \
    -F html='<h1>HTML Version</h1><p>This is the <strong>HTML</strong> version.</p>'
```

### 3. Email con destinatari multipli (ognuno riceve un'email individuale)

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='Newsletter <newsletter@example.com>' \
    -F to='utente1@example.com,utente2@example.com,utente3@example.com' \
    -F cc='manager@example.com' \
    -F bcc='admin@example.com' \
    -F subject='Newsletter settimanale' \
    -F text='Questa settimana nella nostra newsletter...' \
    -F html='<h1>Newsletter settimanale</h1><p>Questa settimana nella nostra newsletter...</p>'
```

**Nota**: ogni destinatario nel campo `to` riceve un'email individuale. I destinatari CC e BCC vengono inclusi in ogni singola email inviata.

### 4. Email con tag e tracking

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

### 5. Email con header personalizzati

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

### 6. Recupero messaggio

```bash
# Prima ottieni l'ID storage dal log o dalla risposta di invio
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/domains/sandbox.libremailapi.org/messages/msg_STORAGE_KEY_HERE
```

### 7. Stato delle code

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/domains/sandbox.libremailapi.org/sending_queues
```

### 8. Eliminazione messaggi programmati

```bash
curl -X DELETE --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/envelopes
```

## Esempi SMTP (invio reale)

### 9. Verifica stato SMTP

```bash
# Controlla se SMTP è abilitato e configurato
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/smtp
```

Risposta esempio:
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

### 10. Test connessione SMTP

```bash
# Testa la connessione al server SMTP
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/smtp/test
```

Risposta successo:
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

Risposta errore:
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

### 11. Invio email reale con risposta SMTP

```bash
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='Your Name <your-email@gmail.com>' \
    -F to='recipient@example.com' \
    -F subject='Real Email Test' \
    -F text='This email will be sent via SMTP!'
```

Risposta con SMTP abilitato:
```json
{
    "id": "<uuid@sandbox.libremailapi.org>",
    "message": "Queued. Thank you.",
    "smtp_status": "sent"
}
```

Risposta con errore SMTP:
```json
{
    "id": "<uuid@sandbox.libremailapi.org>",
    "message": "Queued. Thank you.",
    "smtp_status": "failed",
    "smtp_error": "Invalid credentials"
}
```

## Esempi con PHP e Guzzle

### Setup base

```php
<?php
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'http://localhost:8081',
    'auth' => ['api', 'key-test123456789']
]);
```

### Invio email semplice

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

### Email a destinatari multipli (ognuno riceve un'email individuale)

```php
$response = $client->post('/v3/sandbox.libremailapi.org/messages', [
    'form_params' => [
        'from' => 'Newsletter <newsletter@example.com>',
        'to' => 'utente1@example.com,utente2@example.com,utente3@example.com',
        'cc' => 'manager@example.com',
        'subject' => 'Newsletter settimanale',
        'text' => 'Questa settimana nella nostra newsletter...',
        'html' => '<h1>Newsletter settimanale</h1><p>Questa settimana nella nostra newsletter...</p>'
    ]
]);

$result = json_decode($response->getBody(), true);
echo "Message ID: " . $result['id'] . "\n";
echo "Destinatari processati: " . ($result['smtp_total_recipients'] ?? 'N/A') . "\n";
echo "Invii riusciti: " . ($result['smtp_successful_sends'] ?? 'N/A') . "\n";
```

### Email con allegato

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

### Test SMTP con PHP

```php
// Test stato SMTP
$response = $client->get('/v3/sandbox.libremailapi.org/smtp');
$smtpStatus = json_decode($response->getBody(), true);

if ($smtpStatus['smtp_enabled']) {
    echo "SMTP è abilitato\n";

    // Test connessione SMTP
    $response = $client->get('/v3/sandbox.libremailapi.org/smtp/test');
    $connectionTest = json_decode($response->getBody(), true);

    if ($connectionTest['success']) {
        echo "Connessione SMTP riuscita\n";

        // Invia email reale
        $response = $client->post('/v3/sandbox.libremailapi.org/messages', [
            'form_params' => [
                'from' => 'Your Name <your-email@gmail.com>',
                'to' => 'recipient@example.com',
                'subject' => 'Email reale da PHP',
                'text' => 'Questa email sarà inviata via SMTP!',
                'html' => '<h1>Email reale</h1><p>Questa email sarà inviata via <strong>SMTP</strong>!</p>'
            ]
        ]);

        $result = json_decode($response->getBody(), true);
        echo "Message ID: " . $result['id'] . "\n";
        echo "SMTP Status: " . ($result['smtp_status'] ?? 'unknown') . "\n";

        if (isset($result['smtp_error'])) {
            echo "SMTP Error: " . $result['smtp_error'] . "\n";
        }
    } else {
        echo "Connessione SMTP fallita: " . $connectionTest['error'] . "\n";
    }
} else {
    echo "SMTP non è abilitato - modalità simulazione\n";
}
```

### Gestione errori

```php
try {
    $response = $client->post('/v3/sandbox.libremailapi.org/messages', [
        'form_params' => [
            'from' => 'invalid-email',  // Email non valida
            'to' => 'recipient@example.com',
            'subject' => 'Test',
            'text' => 'This will fail'
        ]
    ]);
} catch (GuzzleHttp\Exception\ClientException $e) {
    $error = json_decode($e->getResponse()->getBody(), true);
    echo "Errore: " . $error['message'] . "\n";
}
```

## Esempi con JavaScript/Node.js

### Setup con Axios

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

### Invio email

```javascript
const FormData = require('form-data');

// Email semplice
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

// Email a destinatari multipli (ognuno riceve un'email individuale)
const multiForm = new FormData();
multiForm.append('from', 'Newsletter <newsletter@example.com>');
multiForm.append('to', 'utente1@example.com,utente2@example.com,utente3@example.com');
multiForm.append('cc', 'manager@example.com');
multiForm.append('subject', 'Newsletter settimanale');
multiForm.append('text', 'Questa settimana nella nostra newsletter...');
multiForm.append('html', '<h1>Newsletter settimanale</h1><p>Questa settimana nella nostra newsletter...</p>');

client.post('/v3/sandbox.libremailapi.org/messages', multiForm, {
    headers: multiForm.getHeaders()
})
.then(response => {
    console.log('Message ID:', response.data.id);
    console.log('Destinatari processati:', response.data.smtp_total_recipients || 'N/A');
    console.log('Invii riusciti:', response.data.smtp_successful_sends || 'N/A');
})
.catch(error => {
    console.error('Error:', error.response.data);
});
```

## Esempi con Python

### Setup con Requests

```python
import requests

def send_simple_email():
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

def send_multiple_recipients():
    # Email a destinatari multipli (ognuno riceve un'email individuale)
    response = requests.post(
        'http://localhost:8081/v3/sandbox.libremailapi.org/messages',
        auth=('api', 'key-test123456789'),
        data={
            'from': 'Newsletter <newsletter@example.com>',
            'to': 'utente1@example.com,utente2@example.com,utente3@example.com',
            'cc': 'manager@example.com',
            'subject': 'Newsletter settimanale',
            'text': 'Questa settimana nella nostra newsletter...',
            'html': '<h1>Newsletter settimanale</h1><p>Questa settimana nella nostra newsletter...</p>'
        }
    )

    if response.status_code == 200:
        result = response.json()
        print(f"Message ID: {result['id']}")
        print(f"Destinatari processati: {result.get('smtp_total_recipients', 'N/A')}")
        print(f"Invii riusciti: {result.get('smtp_successful_sends', 'N/A')}")
    else:
        print(f"Error: {response.text}")

# Esegui esempi
send_simple_email()
send_multiple_recipients()
```

## Test e debugging

### Esecuzione test

```bash
# Esegui tutti i test
php tests/ApiTest.php

# Test semplice
php test_simple.php
```

### Controllo log

```bash
# Visualizza i log in tempo reale
tail -f logs/libre-mail-api.log

# Visualizza messaggi salvati
ls -la storage/messages/

# Visualizza contenuto messaggio
cat storage/messages/msg_STORAGE_KEY.json | jq .
```

### Monitoraggio server

Il server PHP integrato mostrerà le richieste in arrivo:

```
[Sat Jun  7 21:31:04 2025] PHP 8.3.21 Development Server (http://localhost:8081) started
[Sat Jun  7 21:31:18 2025] 127.0.0.1:43240 Accepted
[Sat Jun  7 21:31:18 2025] 127.0.0.1:43240 Closing
```

## Configurazione avanzata

### Modifica configurazione

Edita `config/config.php` per personalizzare:

- Credenziali di autenticazione
- Limiti di dimensione allegati
- Percorsi di storage
- Livelli di logging

### Personalizzazione domini

L'API supporta qualsiasi dominio nell'URL. Esempi:

```bash
# Dominio personalizzato
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/mydomain.com/messages \
    -F from='sender@mydomain.com' \
    -F to='user@example.com' \
    -F subject='Custom Domain' \
    -F text='Using custom domain'
```

## Troubleshooting

### Problemi comuni

1. **Server non risponde**: verifica che sia avviato su porta 8081
2. **Errore 401**: controlla username/password (api/key-test123456789)
3. **Errore 400**: verifica che tutti i campi obbligatori siano presenti
4. **Allegati non funzionano**: usa multipart/form-data per gli upload

### Problemi SMTP

1. **SMTP non abilitato**:
   - Verifica `'enabled' => true` in config/config.php
   - Controlla che host, username e password siano configurati

2. **Errore di autenticazione Gmail**:
   - Abilita autenticazione a 2 fattori
   - Genera una App Password (non usare la password normale)
   - Usa la App Password di 16 caratteri nella configurazione

3. **Errore di connessione**:
   - Verifica host e porta SMTP
   - Controlla impostazioni firewall
   - Prova con `'verify_peer' => false` per test

4. **Email non arrivano**:
   - Controlla cartella spam/junk
   - Verifica che l'indirizzo mittente sia valido
   - Controlla i log per errori SMTP

5. **Timeout di connessione**:
   - Aumenta `'timeout' => 60` nella configurazione
   - Verifica connessione internet
   - Prova con porta alternativa (465 per SSL)

### Debug

Abilita il debug aggiungendo parametri verbose a curl:

```bash
curl -v --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='test@example.com' \
    -F to='recipient@example.com' \
    -F subject='Debug Test' \
    -F text='Debug message'
```
