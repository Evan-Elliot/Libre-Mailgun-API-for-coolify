<?php

namespace LibreMailApi;

use Ramsey\Uuid\Uuid;

class Storage
{
    private $config;
    private $storagePath;

    public function __construct($config)
    {
        $this->config = $config;
        $this->storagePath = $config['storage']['path'];
        $this->ensureStorageDirectory();
    }

    private function ensureStorageDirectory()
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
        
        // Create subdirectories
        $subdirs = ['messages', 'attachments', 'logs'];
        foreach ($subdirs as $subdir) {
            $path = $this->storagePath . '/' . $subdir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    public function storeMessage($message)
    {
        $storageKey = $this->generateStorageKey();
        $filePath = $this->storagePath . '/messages/' . $storageKey . '.json';
        
        $data = [
            'storage_key' => $storageKey,
            'stored_at' => date('c'),
            'message' => $message
        ];
        
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        
        return $storageKey;
    }

    public function retrieveMessage($storageKey)
    {
        $filePath = $this->storagePath . '/messages/' . $storageKey . '.json';
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($filePath), true);
        
        if (!$data || !isset($data['message'])) {
            return null;
        }
        
        return $data['message'];
    }

    public function deleteMessage($storageKey)
    {
        $filePath = $this->storagePath . '/messages/' . $storageKey . '.json';
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }

    public function listMessages($domain = null, $limit = 100, $offset = 0)
    {
        $messagesDir = $this->storagePath . '/messages';
        $files = glob($messagesDir . '/*.json');
        
        $messages = [];
        $count = 0;
        
        foreach ($files as $file) {
            if ($count < $offset) {
                $count++;
                continue;
            }
            
            if (count($messages) >= $limit) {
                break;
            }
            
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['message'])) {
                $message = $data['message'];
                
                // Filter by domain if specified
                if ($domain && $message['domain'] !== $domain) {
                    continue;
                }
                
                $messages[] = [
                    'storage_key' => $data['storage_key'],
                    'stored_at' => $data['stored_at'],
                    'message_id' => $message['message_id'],
                    'domain' => $message['domain'],
                    'from' => $message['from'],
                    'to' => $message['to'],
                    'subject' => $message['subject'],
                    'timestamp' => $message['timestamp']
                ];
            }
            
            $count++;
        }
        
        return $messages;
    }

    public function storeAttachment($file, $messageId)
    {
        $attachmentId = Uuid::uuid4()->toString();
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $attachmentId . '.' . $extension;
        $filePath = $this->storagePath . '/attachments/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'attachment_id' => $attachmentId,
                'filename' => $filename,
                'original_name' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type'],
                'path' => $filePath
            ];
        }
        
        return null;
    }

    public function getAttachment($attachmentId)
    {
        $pattern = $this->storagePath . '/attachments/' . $attachmentId . '.*';
        $files = glob($pattern);
        
        if (empty($files)) {
            return null;
        }
        
        $filePath = $files[0];
        
        return [
            'path' => $filePath,
            'content' => file_get_contents($filePath),
            'size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath)
        ];
    }

    public function cleanupOldMessages()
    {
        $retentionDays = $this->config['storage']['retention_days'];
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        
        $messagesDir = $this->storagePath . '/messages';
        $files = glob($messagesDir . '/*.json');
        
        $deletedCount = 0;
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['message']['timestamp'])) {
                if ($data['message']['timestamp'] < $cutoffTime) {
                    unlink($file);
                    $deletedCount++;
                }
            }
        }
        
        return $deletedCount;
    }

    public function getStorageStats()
    {
        $messagesDir = $this->storagePath . '/messages';
        $attachmentsDir = $this->storagePath . '/attachments';
        
        $messageFiles = glob($messagesDir . '/*.json');
        $attachmentFiles = glob($attachmentsDir . '/*');
        
        $totalSize = 0;
        foreach (array_merge($messageFiles, $attachmentFiles) as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'total_messages' => count($messageFiles),
            'total_attachments' => count($attachmentFiles),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / (1024 * 1024), 2),
            'storage_path' => $this->storagePath
        ];
    }

    private function generateStorageKey()
    {
        return 'msg_' . Uuid::uuid4()->toString();
    }
}
