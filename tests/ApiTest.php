<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Test suite for LibreMailApi
 */
class ApiTest
{
    private $client;
    private $baseUrl = 'http://localhost:8081';
    private $domain = 'sandbox.libremailapi.org';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10,
            'auth' => ['api', 'key-test123456789']
        ]);
    }

    public function runAllTests()
    {
        echo "=== LIBREMAILAPI TESTS ===\n\n";

        $tests = [
            'testSimpleEmail',
            'testAdvancedEmail',
            'testEmailWithMultipleRecipients',
            'testEmailValidation',
            'testMessageRetrieval',
            'testQueueStatus',
            'testDeleteEnvelopes',
            'testAuthentication',
            'testSmtpStatus',
            'testSmtpConnection'
        ];

        $passed = 0;
        $failed = 0;

        foreach ($tests as $test) {
            try {
                echo "Running $test... ";
                $this->$test();
                echo "✅ PASSED\n";
                $passed++;
            } catch (Exception $e) {
                echo "❌ FAILED: " . $e->getMessage() . "\n";
                $failed++;
            }
        }

        echo "\n=== RESULTS ===\n";
        echo "Tests passed: $passed\n";
        echo "Tests failed: $failed\n";
        echo "Total: " . ($passed + $failed) . "\n";
    }

    private function testSimpleEmail()
    {
        $response = $this->client->post("/v3/{$this->domain}/messages", [
            'form_params' => [
                'from' => 'test@example.com',
                'to' => 'recipient@example.com',
                'subject' => 'Test Simple Email',
                'text' => 'This is a simple test email.'
            ]
        ]);

        $result = json_decode($response->getBody(), true);
        
        if (!isset($result['id']) || !isset($result['message'])) {
            throw new Exception('Invalid response for simple email');
        }

        if ($result['message'] !== 'Queued. Thank you.') {
            throw new Exception('Incorrect response message');
        }
    }

    private function testAdvancedEmail()
    {
        $response = $this->client->post("/v3/{$this->domain}/messages", [
            'form_params' => [
                'from' => 'Advanced Sender <advanced@example.com>',
                'to' => 'recipient@example.com',
                'cc' => 'cc@example.com',
                'subject' => 'Advanced Test Email',
                'text' => 'Text content',
                'html' => '<h1>HTML Content</h1>',
                'o:tag' => 'test,advanced',
                'o:tracking' => 'yes',
                'h:X-Custom-Header' => 'CustomValue'
            ]
        ]);

        $result = json_decode($response->getBody(), true);

        if (!isset($result['id'])) {
            throw new Exception('Missing message ID for advanced email');
        }
    }

    private function testEmailWithMultipleRecipients()
    {
        $response = $this->client->post("/v3/{$this->domain}/messages", [
            'form_params' => [
                'from' => 'sender@example.com',
                'to' => 'recipient1@example.com,recipient2@example.com,recipient3@example.com',
                'subject' => 'Multiple Recipients Test',
                'text' => 'This email goes to multiple recipients.'
            ]
        ]);

        $result = json_decode($response->getBody(), true);

        if (!isset($result['id'])) {
            throw new Exception('Multiple recipients sending failed');
        }
    }

    private function testEmailValidation()
    {
        // Test email without required field
        try {
            $response = $this->client->post("/v3/{$this->domain}/messages", [
                'form_params' => [
                    'to' => 'recipient@example.com',
                    'subject' => 'Missing From Field',
                    'text' => 'This should fail.'
                ]
            ]);

            throw new Exception('Validation did not detect missing field');
        } catch (RequestException $e) {
            if ($e->getResponse()->getStatusCode() !== 400) {
                throw new Exception('Incorrect error code for validation');
            }
        }
    }

    private function testMessageRetrieval()
    {
        // Prima invia un messaggio
        $response = $this->client->post("/v3/{$this->domain}/messages", [
            'form_params' => [
                'from' => 'test@example.com',
                'to' => 'recipient@example.com',
                'subject' => 'Test Retrieval',
                'text' => 'This message will be retrieved.'
            ]
        ]);

        $sendResult = json_decode($response->getBody(), true);
        
        // Trova l'ultimo messaggio salvato
        $storageDir = __DIR__ . '/../storage/messages';
        $files = glob($storageDir . '/*.json');
        
        if (empty($files)) {
            throw new Exception('No messages found in storage');
        }

        $latestFile = array_reduce($files, function($a, $b) {
            return filemtime($a) > filemtime($b) ? $a : $b;
        });

        $data = json_decode(file_get_contents($latestFile), true);
        $storageKey = $data['storage_key'];

        // Retrieve the message
        $response = $this->client->get("/v3/domains/{$this->domain}/messages/{$storageKey}");
        $result = json_decode($response->getBody(), true);

        if (!isset($result['Subject']) || $result['Subject'] !== 'Test Retrieval') {
            throw new Exception('Message retrieval failed');
        }
    }

    private function testQueueStatus()
    {
        $response = $this->client->get("/v3/domains/{$this->domain}/sending_queues");
        $result = json_decode($response->getBody(), true);

        if (!isset($result['regular']) || !isset($result['scheduled'])) {
            throw new Exception('Invalid queue status');
        }
    }

    private function testDeleteEnvelopes()
    {
        $response = $this->client->delete("/v3/{$this->domain}/envelopes");
        $result = json_decode($response->getBody(), true);

        if (!isset($result['message']) || $result['message'] !== 'done') {
            throw new Exception('Envelope deletion failed');
        }
    }

    private function testAuthentication()
    {
        // Test with wrong credentials
        $wrongClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10,
            'auth' => ['api', 'wrong-key']
        ]);

        try {
            $response = $wrongClient->post("/v3/{$this->domain}/messages", [
                'form_params' => [
                    'from' => 'test@example.com',
                    'to' => 'recipient@example.com',
                    'subject' => 'Should Fail',
                    'text' => 'This should fail due to wrong auth.'
                ]
            ]);

            throw new Exception('Authentication did not detect wrong credentials');
        } catch (RequestException $e) {
            if ($e->getResponse()->getStatusCode() !== 401) {
                throw new Exception('Incorrect error code for authentication');
            }
        }
    }

    private function testSmtpStatus()
    {
        $response = $this->client->get("/v3/{$this->domain}/smtp");
        $result = json_decode($response->getBody(), true);

        if (!isset($result['smtp_enabled'])) {
            throw new Exception('SMTP status response invalid');
        }
    }

    private function testSmtpConnection()
    {
        try {
            $response = $this->client->get("/v3/{$this->domain}/smtp/test");
            $result = json_decode($response->getBody(), true);

            if (!isset($result['success'])) {
                throw new Exception('SMTP connection test response invalid');
            }
        } catch (RequestException $e) {
            // SMTP connection test might fail if SMTP is not configured, which is OK
            if ($e->getResponse()->getStatusCode() !== 400 && $e->getResponse()->getStatusCode() !== 500) {
                throw new Exception('Unexpected error code for SMTP test');
            }
        }
    }
}

// Run tests if script is called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new ApiTest();
    $tester->runAllTests();
}
