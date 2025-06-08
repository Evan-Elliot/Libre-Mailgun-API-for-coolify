<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Example usage of LibreMailApi
 * This script demonstrates how to send emails using Guzzle HTTP client
 * Uses configuration from config/config.php
 */

// Load configuration
$config = require __DIR__ . '/../config/config.php';

class LibreMailApiClient
{
    private $client;
    private $baseUrl;
    private $apiKey;
    private $domain;

    public function __construct($config = null, $baseUrl = null, $apiKey = null, $domain = null)
    {
        // Use values from configuration if available, otherwise use parameters or defaults
        $this->baseUrl = $baseUrl ?? ($config['api']['base_url'] ?? 'http://localhost:8081');
        $this->apiKey = $apiKey ?? ($config['api']['auth']['password'] ?? 'key-test123456789');
        $this->domain = $domain ?? ($config['api']['default_domain'] ?? 'sandbox.libremailapi.org');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'auth' => [$config['api']['auth']['username'] ?? 'api', $this->apiKey]
        ]);
    }

    /**
     * Send a simple email
     */
    public function sendSimpleEmail($from, $to, $subject, $text, $html = null)
    {
        try {
            $data = [
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'text' => $text
            ];

            if ($html) {
                $data['html'] = $html;
            }

            $response = $this->client->post("/v3/{$this->domain}/messages", [
                'form_params' => $data
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            throw new Exception('Error sending email: ' . $e->getMessage());
        }
    }

    /**
     * Send an advanced email with more options
     */
    public function sendAdvancedEmail($options)
    {
        try {
            $response = $this->client->post("/v3/{$this->domain}/messages", [
                'form_params' => $options
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            throw new Exception('Error sending email: ' . $e->getMessage());
        }
    }

    /**
     * Send email with attachments
     */
    public function sendEmailWithAttachment($from, $to, $subject, $text, $attachmentPath)
    {
        try {
            $multipart = [
                ['name' => 'from', 'contents' => $from],
                ['name' => 'to', 'contents' => $to],
                ['name' => 'subject', 'contents' => $subject],
                ['name' => 'text', 'contents' => $text],
                [
                    'name' => 'attachment',
                    'contents' => fopen($attachmentPath, 'r'),
                    'filename' => basename($attachmentPath)
                ]
            ];

            $response = $this->client->post("/v3/{$this->domain}/messages", [
                'multipart' => $multipart
            ]);

            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            throw new Exception('Error sending email with attachment: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a saved message
     */
    public function retrieveMessage($storageKey)
    {
        try {
            $response = $this->client->get("/v3/domains/{$this->domain}/messages/{$storageKey}");
            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            throw new Exception('Error retrieving message: ' . $e->getMessage());
        }
    }

    /**
     * Get queue status
     */
    public function getQueueStatus()
    {
        try {
            $response = $this->client->get("/v3/domains/{$this->domain}/sending_queues");
            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            throw new Exception('Error retrieving queue status: ' . $e->getMessage());
        }
    }

    /**
     * Delete scheduled messages
     */
    public function deleteScheduledMessages()
    {
        try {
            $response = $this->client->delete("/v3/{$this->domain}/envelopes");
            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            throw new Exception('Error deleting messages: ' . $e->getMessage());
        }
    }

    /**
     * Test SMTP connection
     */
    public function testSmtpConnection()
    {
        try {
            $response = $this->client->get("/v3/{$this->domain}/smtp/test");
            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            throw new Exception('Error testing SMTP connection: ' . $e->getMessage());
        }
    }

    /**
     * Get SMTP status
     */
    public function getSmtpStatus()
    {
        try {
            $response = $this->client->get("/v3/{$this->domain}/smtp");
            return json_decode($response->getBody(), true);

        } catch (RequestException $e) {
            throw new Exception('Error getting SMTP status: ' . $e->getMessage());
        }
    }
}

// Usage examples
try {
    // Initialize client with loaded configuration
    $libreMailApi = new LibreMailApiClient($config);

    // Show used configuration
    echo "=== Used Configuration ===\n";
    echo "Base URL: " . $config['api']['base_url'] . "\n";
    echo "Domain: " . $config['api']['default_domain'] . "\n";
    echo "Username: " . $config['api']['auth']['username'] . "\n";
    echo "SMTP Enabled: " . ($config['smtp']['enabled'] ? 'Yes' : 'No') . "\n";
    if ($config['smtp']['enabled']) {
        echo "SMTP Host: " . $config['smtp']['host'] . "\n";
        echo "SMTP From: " . $config['smtp']['from_email'] . "\n";
    }
    echo "\n";

    echo "=== Test 1: Simple Email ===\n";
    $fromEmail = $config['smtp']['enabled'] ? $config['smtp']['from_email'] : 'sender@' . $config['api']['default_domain'];
    $result = $libreMailApi->sendSimpleEmail(
        $fromEmail,
        'recipient@example.com',
        'Simple Test Email',
        'This is the text content of the email.',
        '<h1>HTML Content</h1><p>This is the HTML content of the email.</p>'
    );
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

    echo "=== Test 2: Advanced Email ===\n";
    $advancedOptions = [
        'from' => $config['smtp']['enabled'] ?
            $config['smtp']['from_name'] . ' <' . $config['smtp']['from_email'] . '>' :
            'Advanced Sender <advanced@' . $config['api']['default_domain'] . '>',
        'to' => 'recipient1@example.com,recipient2@example.com',
        'cc' => 'cc@example.com',
        'subject' => 'Advanced Email with Options',
        'text' => 'Text content of the advanced email.',
        'html' => '<h2>Advanced Email</h2><p>HTML content with <strong>formatting</strong>.</p>',
        'o:tag' => 'test,advanced,demo',
        'o:tracking' => $config['features']['tracking'] ? 'yes' : 'no',
        'o:tracking-clicks' => $config['features']['tracking'] ? 'yes' : 'no',
        'o:tracking-opens' => $config['features']['tracking'] ? 'yes' : 'no',
        'o:testmode' => $config['features']['testmode'] ? 'yes' : 'no',
        'h:X-Custom-Header' => 'Custom-Value',
        'v:custom-data' => json_encode(['user_id' => 123, 'campaign' => 'demo'])
    ];

    $result = $libreMailApi->sendAdvancedEmail($advancedOptions);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

    echo "=== Test 3: SMTP Status ===\n";
    $smtpStatus = $libreMailApi->getSmtpStatus();
    echo "SMTP status: " . json_encode($smtpStatus, JSON_PRETTY_PRINT) . "\n\n";

    if ($config['smtp']['enabled']) {
        echo "=== Test 4: SMTP Connection Test ===\n";
        $smtpTest = $libreMailApi->testSmtpConnection();
        echo "SMTP test: " . json_encode($smtpTest, JSON_PRETTY_PRINT) . "\n\n";
    }

    echo "=== Test 5: Queue Status ===\n";
    $queueStatus = $libreMailApi->getQueueStatus();
    echo "Queue status: " . json_encode($queueStatus, JSON_PRETTY_PRINT) . "\n\n";

    echo "=== Test 6: Delete Scheduled Messages ===\n";
    $deleteResult = $libreMailApi->deleteScheduledMessages();
    echo "Delete result: " . json_encode($deleteResult, JSON_PRETTY_PRINT) . "\n\n";

    echo "All tests completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
