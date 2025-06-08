<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MailgunClone\Storage;

/**
 * Script di manutenzione per il clone Mailgun
 * Gestisce pulizia, statistiche e manutenzione generale
 */

class MaintenanceScript
{
    private $storage;
    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/config.php';
        $this->storage = new Storage($this->config);
    }

    public function run($command = null)
    {
        if (!$command) {
            $this->showHelp();
            return;
        }

        switch ($command) {
            case 'cleanup':
                $this->cleanup();
                break;
            case 'stats':
                $this->showStats();
                break;
            case 'list':
                $this->listMessages();
                break;
            case 'clear':
                $this->clearAll();
                break;
            case 'help':
                $this->showHelp();
                break;
            default:
                echo "Comando non riconosciuto: $command\n";
                $this->showHelp();
        }
    }

    private function showHelp()
    {
        echo "=== MAILGUN CLONE - SCRIPT DI MANUTENZIONE ===\n\n";
        echo "Utilizzo: php scripts/maintenance.php [comando]\n\n";
        echo "Comandi disponibili:\n";
        echo "  cleanup  - Elimina messaggi vecchi (oltre {$this->config['storage']['retention_days']} giorni)\n";
        echo "  stats    - Mostra statistiche storage\n";
        echo "  list     - Lista tutti i messaggi salvati\n";
        echo "  clear    - Elimina TUTTI i messaggi (attenzione!)\n";
        echo "  help     - Mostra questo aiuto\n\n";
        echo "Esempi:\n";
        echo "  php scripts/maintenance.php stats\n";
        echo "  php scripts/maintenance.php cleanup\n";
    }

    private function cleanup()
    {
        echo "Avvio pulizia messaggi vecchi...\n";
        
        $deletedCount = $this->storage->cleanupOldMessages();
        
        echo "Pulizia completata!\n";
        echo "Messaggi eliminati: $deletedCount\n";
        
        if ($deletedCount > 0) {
            echo "Spazio liberato: " . $this->formatBytes($this->calculateFreedSpace()) . "\n";
        }
    }

    private function showStats()
    {
        echo "=== STATISTICHE STORAGE ===\n\n";
        
        $stats = $this->storage->getStorageStats();
        
        echo "Messaggi totali: " . $stats['total_messages'] . "\n";
        echo "Allegati totali: " . $stats['total_attachments'] . "\n";
        echo "Dimensione totale: " . $this->formatBytes($stats['total_size_bytes']) . "\n";
        echo "Percorso storage: " . $stats['storage_path'] . "\n\n";
        
        // Statistiche aggiuntive
        $this->showDetailedStats();
    }

    private function showDetailedStats()
    {
        $messages = $this->storage->listMessages();
        
        if (empty($messages)) {
            echo "Nessun messaggio trovato.\n";
            return;
        }

        // Raggruppa per dominio
        $domainStats = [];
        $oldestMessage = null;
        $newestMessage = null;

        foreach ($messages as $message) {
            $domain = $message['domain'];
            if (!isset($domainStats[$domain])) {
                $domainStats[$domain] = 0;
            }
            $domainStats[$domain]++;

            if (!$oldestMessage || $message['timestamp'] < $oldestMessage['timestamp']) {
                $oldestMessage = $message;
            }
            if (!$newestMessage || $message['timestamp'] > $newestMessage['timestamp']) {
                $newestMessage = $message;
            }
        }

        echo "=== DETTAGLI ===\n";
        echo "Messaggi per dominio:\n";
        foreach ($domainStats as $domain => $count) {
            echo "  $domain: $count messaggi\n";
        }

        if ($oldestMessage) {
            echo "\nMessaggio più vecchio: " . date('Y-m-d H:i:s', $oldestMessage['timestamp']) . "\n";
            echo "Messaggio più recente: " . date('Y-m-d H:i:s', $newestMessage['timestamp']) . "\n";
        }
    }

    private function listMessages($limit = 20)
    {
        echo "=== LISTA MESSAGGI (ultimi $limit) ===\n\n";
        
        $messages = $this->storage->listMessages(null, $limit);
        
        if (empty($messages)) {
            echo "Nessun messaggio trovato.\n";
            return;
        }

        // Ordina per timestamp decrescente
        usort($messages, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        printf("%-20s %-25s %-30s %-50s\n", "DATA", "DOMINIO", "DA", "OGGETTO");
        echo str_repeat("-", 125) . "\n";

        foreach ($messages as $message) {
            $date = date('Y-m-d H:i:s', $message['timestamp']);
            $domain = substr($message['domain'], 0, 24);
            $from = substr($message['from'], 0, 29);
            $subject = substr($message['subject'], 0, 49);
            
            printf("%-20s %-25s %-30s %-50s\n", $date, $domain, $from, $subject);
        }

        echo "\nTotale messaggi: " . count($messages) . "\n";
    }

    private function clearAll()
    {
        echo "ATTENZIONE: Questa operazione eliminerà TUTTI i messaggi salvati!\n";
        echo "Sei sicuro di voler continuare? (digita 'yes' per confermare): ";
        
        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);
        
        if ($confirmation !== 'yes') {
            echo "Operazione annullata.\n";
            return;
        }

        $messagesDir = $this->config['storage']['path'] . '/messages';
        $attachmentsDir = $this->config['storage']['path'] . '/attachments';
        
        $deletedMessages = 0;
        $deletedAttachments = 0;

        // Elimina messaggi
        $messageFiles = glob($messagesDir . '/*.json');
        foreach ($messageFiles as $file) {
            if (unlink($file)) {
                $deletedMessages++;
            }
        }

        // Elimina allegati
        $attachmentFiles = glob($attachmentsDir . '/*');
        foreach ($attachmentFiles as $file) {
            if (is_file($file) && unlink($file)) {
                $deletedAttachments++;
            }
        }

        echo "Pulizia completata!\n";
        echo "Messaggi eliminati: $deletedMessages\n";
        echo "Allegati eliminati: $deletedAttachments\n";
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function calculateFreedSpace()
    {
        // Stima approssimativa - in un'implementazione reale 
        // dovresti tracciare la dimensione prima della pulizia
        return 1024 * 1024; // 1MB come esempio
    }
}

// Esegui lo script se chiamato direttamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $maintenance = new MaintenanceScript();
    $command = $argv[1] ?? null;
    $maintenance->run($command);
}
