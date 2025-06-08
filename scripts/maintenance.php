<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MailgunClone\Storage;

/**
 * Maintenance script for LibreMailApi
 * Handles cleanup, statistics and general maintenance
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
                echo "Unrecognized command: $command\n";
                $this->showHelp();
        }
    }

    private function showHelp()
    {
        echo "=== LIBREMAILAPI - MAINTENANCE SCRIPT ===\n\n";
        echo "Usage: php scripts/maintenance.php [command]\n\n";
        echo "Available commands:\n";
        echo "  cleanup  - Delete old messages (older than {$this->config['storage']['retention_days']} days)\n";
        echo "  stats    - Show storage statistics\n";
        echo "  list     - List all saved messages\n";
        echo "  clear    - Delete ALL messages (warning!)\n";
        echo "  help     - Show this help\n\n";
        echo "Examples:\n";
        echo "  php scripts/maintenance.php stats\n";
        echo "  php scripts/maintenance.php cleanup\n";
    }

    private function cleanup()
    {
        echo "Starting cleanup of old messages...\n";

        $deletedCount = $this->storage->cleanupOldMessages();

        echo "Cleanup completed!\n";
        echo "Messages deleted: $deletedCount\n";

        if ($deletedCount > 0) {
            echo "Space freed: " . $this->formatBytes($this->calculateFreedSpace()) . "\n";
        }
    }

    private function showStats()
    {
        echo "=== STORAGE STATISTICS ===\n\n";

        $stats = $this->storage->getStorageStats();

        echo "Total messages: " . $stats['total_messages'] . "\n";
        echo "Total attachments: " . $stats['total_attachments'] . "\n";
        echo "Total size: " . $this->formatBytes($stats['total_size_bytes']) . "\n";
        echo "Storage path: " . $stats['storage_path'] . "\n\n";

        // Additional statistics
        $this->showDetailedStats();
    }

    private function showDetailedStats()
    {
        $messages = $this->storage->listMessages();
        
        if (empty($messages)) {
            echo "No messages found.\n";
            return;
        }

        // Group by domain
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

        echo "=== DETAILS ===\n";
        echo "Messages by domain:\n";
        foreach ($domainStats as $domain => $count) {
            echo "  $domain: $count messages\n";
        }

        if ($oldestMessage) {
            echo "\nOldest message: " . date('Y-m-d H:i:s', $oldestMessage['timestamp']) . "\n";
            echo "Newest message: " . date('Y-m-d H:i:s', $newestMessage['timestamp']) . "\n";
        }
    }

    private function listMessages($limit = 20)
    {
        echo "=== MESSAGE LIST (last $limit) ===\n\n";

        $messages = $this->storage->listMessages(null, $limit);

        if (empty($messages)) {
            echo "No messages found.\n";
            return;
        }

        // Sort by descending timestamp
        usort($messages, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        printf("%-20s %-25s %-30s %-50s\n", "DATE", "DOMAIN", "FROM", "SUBJECT");
        echo str_repeat("-", 125) . "\n";

        foreach ($messages as $message) {
            $date = date('Y-m-d H:i:s', $message['timestamp']);
            $domain = substr($message['domain'], 0, 24);
            $from = substr($message['from'], 0, 29);
            $subject = substr($message['subject'], 0, 49);
            
            printf("%-20s %-25s %-30s %-50s\n", $date, $domain, $from, $subject);
        }

        echo "\nTotal messages: " . count($messages) . "\n";
    }

    private function clearAll()
    {
        echo "WARNING: This operation will delete ALL saved messages!\n";
        echo "Are you sure you want to continue? (type 'yes' to confirm): ";

        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);

        if ($confirmation !== 'yes') {
            echo "Operation cancelled.\n";
            return;
        }

        $messagesDir = $this->config['storage']['path'] . '/messages';
        $attachmentsDir = $this->config['storage']['path'] . '/attachments';
        
        $deletedMessages = 0;
        $deletedAttachments = 0;

        // Delete messages
        $messageFiles = glob($messagesDir . '/*.json');
        foreach ($messageFiles as $file) {
            if (unlink($file)) {
                $deletedMessages++;
            }
        }

        // Delete attachments
        $attachmentFiles = glob($attachmentsDir . '/*');
        foreach ($attachmentFiles as $file) {
            if (is_file($file) && unlink($file)) {
                $deletedAttachments++;
            }
        }

        echo "Cleanup completed!\n";
        echo "Messages deleted: $deletedMessages\n";
        echo "Attachments deleted: $deletedAttachments\n";
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
        // Rough estimate - in a real implementation
        // you should track the size before cleanup
        return 1024 * 1024; // 1MB as example
    }
}

// Execute the script if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $maintenance = new MaintenanceScript();
    $command = $argv[1] ?? null;
    $maintenance->run($command);
}
