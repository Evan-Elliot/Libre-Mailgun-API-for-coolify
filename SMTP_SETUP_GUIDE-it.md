# Guida configurazione SMTP - LibreMailApi

Questa guida ti aiuterà a configurare l'invio reale di email tramite SMTP in LibreMailApi.

## Configurazione rapida

### 1. Copia il file di configurazione

```bash
cp config/config.example.php config/config.php
```

### 2. Configura SMTP

Modifica `config/config.php` e imposta la sezione SMTP:

```php
'smtp' => [
    'enabled' => true, // IMPORTANTE: abilita SMTP
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'your-email@gmail.com',
    'password' => 'your-app-password', // NON la password normale!
    'from_name' => 'Your Name',
    'from_email' => 'your-email@gmail.com',
]
```

### 3. Test della configurazione

```bash
# Avvia il server
php -S localhost:8081 index.php

# In un altro terminale, testa SMTP
php examples/test_smtp.php your-test-email@example.com
```

## Configurazioni provider specifici

### Gmail

**Prerequisiti:**
1. Abilita autenticazione a 2 fattori
2. Genera una App Password (16 caratteri)

**Configurazione:**
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

**Come ottenere App Password Gmail:**
1. Vai su https://myaccount.google.com/security
2. Abilita "Verifica in due passaggi"
3. Vai su "Password per le app"
4. Genera una nuova password per "Mail"
5. Usa la password di 16 caratteri generata

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
    'password' => 'your-app-password', // Richiede App Password
    'from_name' => 'Your Name',
    'from_email' => 'your-email@yahoo.com',
]
```

### Server SMTP personalizzato

```php
'smtp' => [
    'enabled' => true,
    'host' => 'mail.yourdomain.com',
    'port' => 587, // o 25, 465
    'encryption' => 'tls', // o 'ssl', null
    'auth' => true, // o false se non richiesta
    'username' => 'noreply@yourdomain.com',
    'password' => 'your-password',
    'from_name' => 'Your Company',
    'from_email' => 'noreply@yourdomain.com',
]
```

## Test e verifica

### Test automatico

```bash
# Test completo con script incluso
php examples/test_smtp.php your-email@example.com

# Test con server personalizzato
php examples/test_smtp.php your-email@example.com http://localhost:8081
```

### Test manuale con cURL

```bash
# 1. Verifica stato SMTP
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/smtp

# 2. Test connessione
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/smtp/test

# 3. Invia email di test
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/messages \
    -F from='Test <your-email@gmail.com>' \
    -F to='recipient@example.com' \
    -F subject='Test SMTP' \
    -F text='Email di test inviata via SMTP!'
```

## Risoluzione problemi

### Errori comuni

**"SMTP connection failed"**
- Verifica host e porta
- Controlla username/password
- Verifica connessione internet

**"Authentication failed"**
- Per Gmail: usa App Password, non password normale
- Verifica che l'autenticazione a 2 fattori sia abilitata
- Controlla username (deve essere email completa)

**"Connection timeout"**
- Aumenta timeout in configurazione
- Verifica firewall/proxy
- Prova porta alternativa (465 per SSL)

**Email non arrivano**
- Controlla cartella spam
- Verifica indirizzo mittente valido
- Controlla log per errori: `tail -f logs/libre-mail-api.log`

### Debug avanzato

Abilita debug SMTP in configurazione:

```php
'smtp' => [
    // ... altre configurazioni
    'debug' => true, // Abilita output debug
    'verify_peer' => false, // Solo per test, disabilita verifica SSL
]
```

### Log e monitoraggio

```bash
# Monitora log in tempo reale
tail -f logs/libre-mail-api.log

# Cerca errori SMTP
grep "SMTP" logs/libre-mail-api.log

# Verifica messaggi salvati
ls -la storage/messages/
```

## Modalità dual

Il sistema funziona in modalità dual:

1. **Simulazione**: sempre attiva, salva messaggi in JSON
2. **SMTP**: se abilitato, invia email reali

Questo permette di:
- Mantenere log completi anche con SMTP
- Continuare a funzionare se SMTP fallisce
- Testare senza inviare email reali (SMTP disabilitato)

## Sicurezza

**Raccomandazioni:**
- Usa sempre App Password per Gmail/Yahoo
- Non committare password nel codice
- Usa variabili d'ambiente per credenziali sensibili
- Abilita SSL/TLS quando possibile
- Limita accesso al file di configurazione

**Esempio con variabili d'ambiente:**
```php
'smtp' => [
    'enabled' => getenv('SMTP_ENABLED') === 'true',
    'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'username' => getenv('SMTP_USERNAME'),
    'password' => getenv('SMTP_PASSWORD'),
]
```

## Supporto

Per problemi o domande:
1. Controlla i log: `logs/libre-mail-api.log`
2. Testa connessione: `php examples/test_smtp.php`
3. Verifica configurazione provider email
4. Consulta documentazione provider SMTP
