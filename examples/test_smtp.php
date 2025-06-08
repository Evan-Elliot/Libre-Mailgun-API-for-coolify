<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Test script for SMTP functionality
 * This script tests both the API simulation and actual SMTP sending
 * Uses configuration from config/config.php
 */

// Load configuration
$config = require __DIR__ . '/../config/config.php';

class SmtpTester
{
    private $client;
    private $baseUrl;
    private $apiKey;
    private $domain;
    private $config;

    public function __construct($config = null, $baseUrl = null, $apiKey = null, $domain = null)
    {
        $this->config = $config;

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
     * Test SMTP status
     */
    public function testSmtpStatus()
    {
        try {
            echo "=== Testing SMTP Status ===\n";
            $response = $this->client->get("/v3/{$this->domain}/smtp");
            $result = json_decode($response->getBody(), true);
            
            echo "SMTP Enabled: " . ($result['smtp_enabled'] ? 'Yes' : 'No') . "\n";
            echo "SMTP Configured: " . ($result['smtp_configured'] ? 'Yes' : 'No') . "\n";
            
            if ($result['smtp_enabled']) {
                echo "SMTP Host: " . ($result['smtp_host'] ?? 'Not set') . "\n";
                echo "SMTP Port: " . ($result['smtp_port'] ?? 'Not set') . "\n";
                echo "SMTP Encryption: " . ($result['smtp_encryption'] ?? 'None') . "\n";
                echo "SMTP Username: " . ($result['smtp_username'] ?? 'Not set') . "\n";
            }
            
            return $result;

        } catch (RequestException $e) {
            echo "Error testing SMTP status: " . $e->getMessage() . "\n";
            return null;
        }
    }

    /**
     * Test SMTP connection
     */
    public function testSmtpConnection()
    {
        try {
            echo "\n=== Testing SMTP Connection ===\n";
            $response = $this->client->get("/v3/{$this->domain}/smtp/test");
            $result = json_decode($response->getBody(), true);
            
            echo "Connection Test: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            
            if (!$result['success'] && isset($result['error'])) {
                echo "Error: " . $result['error'] . "\n";
            }
            
            return $result;

        } catch (RequestException $e) {
            echo "Error testing SMTP connection: " . $e->getMessage() . "\n";
            return null;
        }
    }

    /**
     * Send a test email
     */
    public function sendTestEmail($to, $subject = 'Test Email from LibreMailApi', $testSmtp = true)
    {
        try {
            echo "\n=== Sending Test Email ===\n";
            echo "To: $to\n";
            echo "Subject: $subject\n";

            // Use configured email if SMTP is enabled
            $fromEmail = $this->config['smtp']['enabled'] ?
                $this->config['smtp']['from_name'] . ' <' . $this->config['smtp']['from_email'] . '>' :
                'LibreMailApi Test <test@' . $this->domain . '>';

            echo "From: $fromEmail\n";

            $response = $this->client->post("/v3/{$this->domain}/messages", [
                'form_params' => [
                    'from' => $fromEmail,
                    'to' => $to,
                    'subject' => $subject,
                    'text' => "This is a test email sent from LibreMailApi.\n\nTimestamp: " . date('Y-m-d H:i:s') . "\nSMTP Enabled: " . ($this->config['smtp']['enabled'] ? 'Yes' : 'No'),
                    'html' => "<h1>Test Email</h1><p>This is a test email sent from <strong>LibreMailApi</strong>.</p><p>Timestamp: " . date('Y-m-d H:i:s') . "</p><p>SMTP Enabled: " . ($this->config['smtp']['enabled'] ? 'Yes' : 'No') . "</p>"
                ]
            ]);
            
            $result = json_decode($response->getBody(), true);
            
            echo "API Response: " . ($response->getStatusCode() == 200 ? 'SUCCESS' : 'FAILED') . "\n";
            echo "Message ID: " . ($result['id'] ?? 'Not provided') . "\n";
            
            if (isset($result['smtp_status'])) {
                echo "SMTP Status: " . $result['smtp_status'] . "\n";
                if (isset($result['smtp_error'])) {
                    echo "SMTP Error: " . $result['smtp_error'] . "\n";
                }
            }
            
            return $result;

        } catch (RequestException $e) {
            echo "Error sending test email: " . $e->getMessage() . "\n";
            if ($e->hasResponse()) {
                echo "Response: " . $e->getResponse()->getBody() . "\n";
            }
            return null;
        }
    }

    /**
     * Send a test email with attachment
     */
    public function sendTestEmailWithAttachment($to, $attachmentPath = null)
    {
        try {
            echo "\n=== Sending Test Email with Attachment ===\n";
            echo "To: $to\n";
            
            // Create a simple test file if no attachment provided
            if (!$attachmentPath) {
                $attachmentPath = sys_get_temp_dir() . '/libremailapi_test_attachment.txt';
                file_put_contents($attachmentPath, "This is a test attachment created by LibreMailApi.\nTimestamp: " . date('Y-m-d H:i:s'));
            }

            if (!file_exists($attachmentPath)) {
                echo "Attachment file not found: $attachmentPath\n";
                return null;
            }

            echo "Attachment: " . basename($attachmentPath) . "\n";

            // Use configured email if SMTP is enabled
            $fromEmail = $this->config['smtp']['enabled'] ?
                $this->config['smtp']['from_name'] . ' <' . $this->config['smtp']['from_email'] . '>' :
                'LibreMailApi Test <test@' . $this->domain . '>';

            echo "From: $fromEmail\n";

            $response = $this->client->post("/v3/{$this->domain}/messages", [
                'multipart' => [
                    [
                        'name' => 'from',
                        'contents' => $fromEmail
                    ],
                    [
                        'name' => 'to',
                        'contents' => $to
                    ],
                    [
                        'name' => 'subject',
                        'contents' => 'Test Email with Attachment from LibreMailApi'
                    ],
                    [
                        'name' => 'text',
                        'contents' => "This is a test email with attachment sent from LibreMailApi.\n\nTimestamp: " . date('Y-m-d H:i:s') . "\nSMTP Enabled: " . ($this->config['smtp']['enabled'] ? 'Yes' : 'No')
                    ],
                    [
                        'name' => 'html',
                        'contents' => "<h1>Test Email with Attachment</h1><p>This is a test email with attachment sent from <strong>LibreMailApi</strong>.</p><p>Timestamp: " . date('Y-m-d H:i:s') . "</p><p>SMTP Enabled: " . ($this->config['smtp']['enabled'] ? 'Yes' : 'No') . "</p>"
                    ],
                    [
                        'name' => 'attachment',
                        'contents' => fopen($attachmentPath, 'r'),
                        'filename' => basename($attachmentPath)
                    ]
                ]
            ]);
            
            $result = json_decode($response->getBody(), true);
            
            echo "API Response: " . ($response->getStatusCode() == 200 ? 'SUCCESS' : 'FAILED') . "\n";
            echo "Message ID: " . ($result['id'] ?? 'Not provided') . "\n";
            
            if (isset($result['smtp_status'])) {
                echo "SMTP Status: " . $result['smtp_status'] . "\n";
                if (isset($result['smtp_error'])) {
                    echo "SMTP Error: " . $result['smtp_error'] . "\n";
                }
            }
            
            // Clean up temporary file
            if (strpos($attachmentPath, sys_get_temp_dir()) === 0) {
                unlink($attachmentPath);
            }
            
            return $result;

        } catch (RequestException $e) {
            echo "Error sending test email with attachment: " . $e->getMessage() . "\n";
            if ($e->hasResponse()) {
                echo "Response: " . $e->getResponse()->getBody() . "\n";
            }
            return null;
        }
    }
}

// Example usage
if (php_sapi_name() === 'cli') {
    echo "LibreMailApi SMTP Test\n";
    echo "======================\n\n";

    // Show loaded configuration
    echo "=== Loaded Configuration ===\n";
    echo "Base URL: " . $config['api']['base_url'] . "\n";
    echo "Domain: " . $config['api']['default_domain'] . "\n";
    echo "Username: " . $config['api']['auth']['username'] . "\n";
    echo "SMTP Enabled: " . ($config['smtp']['enabled'] ? 'Yes' : 'No') . "\n";
    if ($config['smtp']['enabled']) {
        echo "SMTP Host: " . $config['smtp']['host'] . "\n";
        echo "SMTP Port: " . $config['smtp']['port'] . "\n";
        echo "SMTP Encryption: " . $config['smtp']['encryption'] . "\n";
        echo "SMTP From: " . $config['smtp']['from_email'] . "\n";
    }
    echo "\n";

    // Check if email address is provided
    if ($argc < 2) {
        echo "Usage: php test_smtp.php <email-address> [base-url]\n";
        echo "Example: php test_smtp.php test@example.com http://localhost:8081\n";
        echo "\nNote: This script now uses configuration from config/config.php\n";
        exit(1);
    }

    $email = $argv[1];
    $baseUrl = $argv[2] ?? null; // Use null to let constructor use config value

    $tester = new SmtpTester($config, $baseUrl);

    // Test SMTP status
    $tester->testSmtpStatus();

    // Test SMTP connection (only if SMTP is enabled)
    $tester->testSmtpConnection();

    // Send test email
    $tester->sendTestEmail($email);

    // Send test email with attachment
    $tester->sendTestEmailWithAttachment($email);

    echo "\nTest completed!\n";
    echo "Check your email inbox and the logs for results.\n";
}
