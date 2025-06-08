<?php

namespace LibreMailApi;

class Validator
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function validateMessage($data)
    {
        // Check required fields
        $requiredFields = ['from', 'to', 'subject'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return [
                    'valid' => false,
                    'error' => "Missing required field: $field"
                ];
            }
        }

        // Must have at least one content field
        if (!isset($data['text']) && !isset($data['html']) && !isset($data['template'])) {
            return [
                'valid' => false,
                'error' => 'Must provide at least one of: text, html, or template'
            ];
        }

        // Validate email addresses
        $fromValidation = $this->validateEmail($data['from']);
        if (!$fromValidation['valid']) {
            return [
                'valid' => false,
                'error' => 'Invalid from email: ' . $fromValidation['error']
            ];
        }

        $toValidation = $this->validateEmailList($data['to']);
        if (!$toValidation['valid']) {
            return [
                'valid' => false,
                'error' => 'Invalid to email: ' . $toValidation['error']
            ];
        }

        // Validate CC if provided
        if (isset($data['cc'])) {
            $ccValidation = $this->validateEmailList($data['cc']);
            if (!$ccValidation['valid']) {
                return [
                    'valid' => false,
                    'error' => 'Invalid cc email: ' . $ccValidation['error']
                ];
            }
        }

        // Validate BCC if provided
        if (isset($data['bcc'])) {
            $bccValidation = $this->validateEmailList($data['bcc']);
            if (!$bccValidation['valid']) {
                return [
                    'valid' => false,
                    'error' => 'Invalid bcc email: ' . $bccValidation['error']
                ];
            }
        }

        // Check recipient limits
        $totalRecipients = $this->countRecipients($data);
        if ($totalRecipients > $this->config['limits']['max_recipients']) {
            return [
                'valid' => false,
                'error' => 'Too many recipients. Maximum allowed: ' . $this->config['limits']['max_recipients']
            ];
        }

        // Validate delivery time if provided
        if (isset($data['o:deliverytime'])) {
            $deliveryValidation = $this->validateDeliveryTime($data['o:deliverytime']);
            if (!$deliveryValidation['valid']) {
                return [
                    'valid' => false,
                    'error' => 'Invalid delivery time: ' . $deliveryValidation['error']
                ];
            }
        }

        // Validate tags if provided
        if (isset($data['o:tag'])) {
            $tagValidation = $this->validateTags($data['o:tag']);
            if (!$tagValidation['valid']) {
                return [
                    'valid' => false,
                    'error' => 'Invalid tags: ' . $tagValidation['error']
                ];
            }
        }

        return ['valid' => true];
    }

    public function validateEmail($email)
    {
        // Extract email from "Name <email@domain.com>" format
        $cleanEmail = $this->extractEmailAddress($email);
        
        if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => "Invalid email format: $email"
            ];
        }

        return ['valid' => true];
    }

    public function validateEmailList($emailList)
    {
        if (is_array($emailList)) {
            $emails = $emailList;
        } else {
            $emails = explode(',', $emailList);
        }

        foreach ($emails as $email) {
            $email = trim($email);
            if (empty($email)) {
                continue;
            }

            $validation = $this->validateEmail($email);
            if (!$validation['valid']) {
                return $validation;
            }
        }

        return ['valid' => true];
    }

    public function validateDeliveryTime($deliveryTime)
    {
        // Validate RFC-2822 format
        $timestamp = strtotime($deliveryTime);
        
        if ($timestamp === false) {
            return [
                'valid' => false,
                'error' => 'Invalid date format. Use RFC-2822 format.'
            ];
        }

        // Check if delivery time is in the future
        if ($timestamp <= time()) {
            return [
                'valid' => false,
                'error' => 'Delivery time must be in the future'
            ];
        }

        // Check maximum scheduling period (7 days for this simulation)
        $maxFuture = time() + (7 * 24 * 60 * 60);
        if ($timestamp > $maxFuture) {
            return [
                'valid' => false,
                'error' => 'Delivery time cannot be more than 7 days in the future'
            ];
        }

        return ['valid' => true];
    }

    public function validateTags($tags)
    {
        if (is_array($tags)) {
            $tagList = $tags;
        } else {
            // Split by comma if it's a string with multiple tags
            $tagList = array_map('trim', explode(',', $tags));
        }

        foreach ($tagList as $tag) {
            // Skip empty tags
            if (empty($tag)) {
                continue;
            }

            // Tags should be alphanumeric with hyphens and underscores
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $tag)) {
                return [
                    'valid' => false,
                    'error' => "Invalid tag format: $tag. Use only alphanumeric characters, hyphens, and underscores."
                ];
            }

            // Tag length limit
            if (strlen($tag) > 128) {
                return [
                    'valid' => false,
                    'error' => "Tag too long: $tag. Maximum length is 128 characters."
                ];
            }
        }

        return ['valid' => true];
    }

    public function validateAttachments($files)
    {
        if (empty($files)) {
            return ['valid' => true];
        }

        $totalSize = 0;

        foreach ($files as $file) {
            if (is_array($file['size'])) {
                // Multiple files
                foreach ($file['size'] as $size) {
                    $totalSize += $size;
                }
            } else {
                // Single file
                $totalSize += $file['size'];
            }
        }

        if ($totalSize > $this->config['limits']['max_attachment_size']) {
            return [
                'valid' => false,
                'error' => 'Total attachment size exceeds limit of ' . 
                          ($this->config['limits']['max_attachment_size'] / (1024 * 1024)) . 'MB'
            ];
        }

        return ['valid' => true];
    }

    private function extractEmailAddress($emailString)
    {
        // Extract email from "Name <email@domain.com>" format
        if (preg_match('/<([^>]+)>/', $emailString, $matches)) {
            return $matches[1];
        }
        
        return trim($emailString);
    }

    private function countRecipients($data)
    {
        $count = 0;
        
        // Count TO recipients
        if (isset($data['to'])) {
            $count += count(explode(',', $data['to']));
        }
        
        // Count CC recipients
        if (isset($data['cc'])) {
            $count += count(explode(',', $data['cc']));
        }
        
        // Count BCC recipients
        if (isset($data['bcc'])) {
            $count += count(explode(',', $data['bcc']));
        }
        
        return $count;
    }
}
