# LibreMailApi - API email gratuita con supporto SMTP

Un'API email gratuita e open-source scritta in PHP che non solo simula l'invio di messaggi email, ma può anche inviarli realmente tramite server SMTP configurabili. Utilizza Guzzle HTTP client, PHPMailer e altre librerie moderne per fornire un'API compatibile con Mailgun.

Questa idea è nata quando ho dovuto inviare newsletter attraverso [Ghost](https://ghost.org) e serviva pagare per il servizio di Mailgun nonostante aver configurato un server SMTP apposito per newsletter.

## Caratteristiche

- **Endpoint API compatibili**: implementa i principali endpoint di Mailgun per l'invio email
- **Invio email reale**: supporta l'invio effettivo di email tramite server SMTP configurabili
- **Modalità dual**: funziona sia in modalità simulazione che invio reale
- **Autenticazione HTTP basic**: simula l'autenticazione API di Mailgun
- **Storage simulato**: salva i messaggi in file JSON per il recupero successivo
- **Validazione completa**: valida indirizzi email, allegati e parametri
- **Logging avanzato**: registra sia le operazioni simulate che quelle SMTP reali
- **Gestione allegati**: supporta l'upload, storage e invio di allegati
- **Formato MIME**: supporta l'invio di messaggi in formato MIME
- **Configurazione SMTP flessibile**: supporta vari provider SMTP (Gmail, Outlook, server personalizzati)

## Endpoint implementati

### Invio messaggi
- `POST /v3/{domain}/messages` - invia email con parametri form
- `POST /v3/{domain}/messages.mime` - invia email in formato MIME

### Gestione messaggi
- `GET /v3/domains/{domain}/messages/{storage_key}` - recupera email salvata
- `GET /v3/domains/{domain}/sending_queues` - stato delle code di invio
- `DELETE /v3/{domain}/envelopes` - elimina messaggi programmati

### Gestione SMTP (nuovi endpoint)
- `GET /v3/{domain}/smtp` - stato della configurazione SMTP
- `GET /v3/{domain}/smtp/test` - test della connessione SMTP

## Installazione

1. **Clona il repository**:
```bash
git clone <repository-url>
cd libre-mail-api
```

2. **Installa le dipendenze**:
```bash
composer install
```

3. **Avvia il server**:
```bash
composer start
# oppure
php -S localhost:8080 index.php
```

## Configurazione

### Configurazione base
La configurazione si trova in `config/config.php`. Per iniziare, copia il file di esempio:

```bash
cp config/config.example.php config/config.php
```

### Configurazione SMTP
Per abilitare l'invio reale di email, modifica la sezione `smtp` in `config/config.php`:

```php
'smtp' => [
    'enabled' => true, // Abilita l'invio SMTP
    'host' => 'smtp.gmail.com', // Server SMTP
    'port' => 587, // Porta SMTP
    'encryption' => 'tls', // Crittografia (tls/ssl/null)
    'auth' => true, // Autenticazione SMTP
    'username' => 'your-email@gmail.com', // Username SMTP
    'password' => 'your-app-password', // Password SMTP
    'from_name' => 'Your Name', // Nome mittente predefinito
    'from_email' => 'your-email@gmail.com', // Email mittente predefinita
]
```

#### Esempi di configurazione SMTP

**Gmail (con app password):**
```php
'smtp' => [
    'enabled' => true,
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your-email@gmail.com',
    'password' => 'your-16-char-app-password', // Non la password normale!
]
```

**Outlook/Hotmail:**
```php
'smtp' => [
    'enabled' => true,
    'host' => 'smtp-mail.outlook.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your-email@outlook.com',
    'password' => 'your-password',
]
```

**Server SMTP personalizzato:**
```php
'smtp' => [
    'enabled' => true,
    'host' => 'mail.yourdomain.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'noreply@yourdomain.com',
    'password' => 'your-password',
]
```

### Configurazione completa

```php
return [
    'api' => [
        'base_url' => 'http://localhost:8080',
        'version' => 'v3',
        'auth' => [
            'username' => 'api',
            'password' => 'key-test123456789'
        ]
    ],
    'storage' => [
        'path' => __DIR__ . '/../storage',
        'retention_days' => 30
    ],
    'limits' => [
        'max_recipients' => 1000,
        'max_attachment_size' => 25 * 1024 * 1024 // 25MB
    ]
];
```

## Utilizzo

### Esempio con cURL

```bash
# Invia un'email semplice
curl -s --user 'api:key-test123456789' \
    http://localhost:8080/v3/sandbox.libremailapi.org/messages \
    -F from='Excited User <hello@sandbox.libremailapi.org>' \
    -F to='test@example.com' \
    -F subject='Hello' \
    -F text='Testing some LibreMailApi awesomeness!'

# Invia email a destinatari multipli (ognuno riceve un'email individuale)
curl -s --user 'api:key-test123456789' \
    http://localhost:8080/v3/sandbox.libremailapi.org/messages \
    -F from='Newsletter <newsletter@sandbox.libremailapi.org>' \
    -F to='utente1@example.com,utente2@example.com,utente3@example.com' \
    -F cc='manager@example.com' \
    -F subject='Newsletter settimanale' \
    -F text='Questa settimana nella nostra newsletter...' \
    -F html='<h1>Newsletter settimanale</h1><p>Questa settimana nella nostra newsletter...</p>'
```

### Esempio con PHP e Guzzle

```php
use GuzzleHttp\Client;

$client = new Client();

// Email semplice
$response = $client->post('http://localhost:8080/v3/sandbox.libremailapi.org/messages', [
    'auth' => ['api', 'key-test123456789'],
    'form_params' => [
        'from' => 'Excited User <hello@sandbox.libremailapi.org>',
        'to' => 'test@example.com',
        'subject' => 'Hello',
        'text' => 'Testing some LibreMailApi awesomeness!'
    ]
]);

$result = json_decode($response->getBody(), true);
echo "Message ID: " . $result['id'] . "\n";

// Email a destinatari multipli (ognuno riceve un'email individuale)
$response = $client->post('http://localhost:8080/v3/sandbox.libremailapi.org/messages', [
    'auth' => ['api', 'key-test123456789'],
    'form_params' => [
        'from' => 'Newsletter <newsletter@sandbox.libremailapi.org>',
        'to' => 'utente1@example.com,utente2@example.com,utente3@example.com',
        'cc' => 'manager@example.com',
        'subject' => 'Newsletter settimanale',
        'text' => 'Questa settimana nella nostra newsletter...',
        'html' => '<h1>Newsletter settimanale</h1><p>Questa settimana nella nostra newsletter...</p>'
    ]
]);

$result = json_decode($response->getBody(), true);
echo "Message ID: " . $result['id'] . "\n";
echo "Destinatari processati: " . $result['smtp_total_recipients'] . "\n";
```

### Parametri supportati

#### Parametri base
- `from` (required): indirizzo email mittente
- `to` (required): indirizzi email destinatari (separati da virgola per destinatari multipli)
- `subject` (required): oggetto del messaggio
- `text`: corpo del messaggio in formato testo
- `html`: corpo del messaggio in formato HTML
- `cc`: destinatari in copia (separati da virgola)
- `bcc`: destinatari in copia nascosta (separati da virgola)

**Nota sui destinatari multipli**: quando vengono specificati più destinatari nel campo `to` (separati da virgola), ogni destinatario riceve un'email individuale. I destinatari CC e BCC vengono inclusi in ogni singola email inviata.

#### Parametri avanzati
- `o:tag`: tag per categorizzare i messaggi
- `o:deliverytime`: programmazione invio (RFC-2822)
- `o:testmode`: modalità test (yes/no)
- `o:tracking`: abilitazione tracking (yes/no)
- `o:tracking-clicks`: tracking click (yes/no/htmlonly)
- `o:tracking-opens`: tracking aperture (yes/no)
- `template`: nome template da utilizzare
- `t:variables`: variabili per il template (JSON)
- `h:X-Custom-Header`: header personalizzati
- `v:custom-data`: dati personalizzati

## Struttura del progetto

```
libre-mail-api/
├── config/
│   └── config.php          # Configurazione
├── src/
│   ├── LibreMailApi.php    # Classe principale
│   ├── MessageHandler.php  # Gestione messaggi
│   ├── Storage.php         # Storage simulato
│   └── Validator.php       # Validazione dati
├── storage/                # Directory storage (auto-creata)
│   ├── messages/          # Messaggi salvati
│   ├── attachments/       # Allegati
│   └── logs/             # File di log
├── logs/                  # Log applicazione
├── composer.json          # Dipendenze
├── index.php             # Entry point
└── README.md             # Documentazione
```

## Testing

### Test SMTP rapido
Utilizza lo script di test incluso per verificare la funzionalità SMTP:

```bash
# Test completo SMTP (sostituisci con la tua email)
php examples/test_smtp.php your-email@example.com

# Test con URL personalizzato
php examples/test_smtp.php your-email@example.com http://localhost:8081
```

Lo script testerà:
- stato della configurazione SMTP
- connessione al server SMTP
- invio di email semplice
- invio di email con allegato

### Test endpoint SMTP
```bash
# Verifica stato SMTP
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/smtp

# Test connessione SMTP
curl --user 'api:key-test123456789' \
    http://localhost:8081/v3/sandbox.libremailapi.org/smtp/test
```

## Logging

I log vengono salvati in `logs/libre-mail-api.log` e includono:
- richieste ricevute
- messaggi inviati
- errori e eccezioni
- operazioni di storage

## Modalità di funzionamento

### Modalità simulazione (SMTP disabilitato)
- simula l'invio di email senza inviarle realmente
- salva i messaggi in file JSON per il recupero
- ideale per sviluppo e testing senza spam

### Modalità SMTP (SMTP abilitato)
- invia email reali tramite server SMTP configurato
- mantiene anche la simulazione per logging e debugging
- supporta allegati, HTML, testo e header personalizzati
- gestisce errori SMTP senza interrompere l'API

### Limitazioni

- storage locale in file JSON (non database)
- autenticazione semplificata (non OAuth)
- nessuna integrazione con servizi di tracking avanzati

## Dipendenze

- **PHP >= 7.4**
- **Guzzle HTTP**: client HTTP per richieste
- **Monolog**: logging avanzato
- **Ramsey UUID**: generazione UUID per identificatori
- **PHPMailer**: invio email SMTP

## Docker

### Avvio con Docker
```bash
# Build e avvio
docker-compose up --build

# Solo avvio (dopo il primo build)
docker-compose up

# Avvio in background
docker-compose up -d
```

### Avvio con Docker semplice
```bash
# Build dell'immagine
docker build -t libre-mail-api .

# Avvio del container con mappatura volumi per config e storage
docker run -p 8080:8080 \
  -v $(pwd)/config/config.php:/app/config/config.php \
  -v $(pwd)/storage:/app/storage \
  libre-mail-api
```

## Manutenzione

### Script di manutenzione
```bash
# Visualizza statistiche
php scripts/maintenance.php stats

# Lista messaggi
php scripts/maintenance.php list

# Pulizia messaggi vecchi
php scripts/maintenance.php cleanup

# Aiuto
php scripts/maintenance.php help
```

### Pulizia manuale
```bash
# Elimina tutti i messaggi
rm storage/messages/*.json

# Elimina tutti gli allegati
rm storage/attachments/*

# Pulisci log
> logs/libre-mail-api.log
```

## Testing avanzato

### Suite di test completa
```bash
# Esegui tutti i test
php tests/ApiTest.php

# Test semplice
php test_simple.php
```



### Esempi dettagliati
Consulta `USAGE_EXAMPLES-it.md` per esempi completi con:
- cURL
- PHP con Guzzle
- JavaScript/Node.js
- Python

## Supporta il progetto

Questo progetto è mantenuto e supportato da **ILS Este** (Italian Linux Society - sezione di Este), una comunità locale del Veneto che fornisce servizi liberi e open-source attraverso [ServiziLiberi.it](https://serviziliberi.it) ai suoi utenti.

ILS Este opera completamente **senza traccianti, senza pubblicità e completamente gratuito**, credendo nella libertà digitale e nei diritti alla privacy per tutte le persone. La nostra missione è offrire alternative etiche ai servizi commerciali rispettando la privacy degli utenti.

**Se questo progetto ti è stato utile, considera di supportare la missione di ILS Este con una donazione.** Il tuo contributo ci aiuta a mantenere questi servizi gratuiti e supporta lo sviluppo di nuovi strumenti liberi per la comunità.

**💖 [Supporta ILS Este su Ko-fi](https://ko-fi.com/ilseste) 💖**

Ogni donazione, non importa quanto piccola, fa la differenza nel mantenere questi servizi liberi e accessibili a tutte le persone.

## Licenza

AGPLv3 License - Vedi file LICENSE per dettagli.
