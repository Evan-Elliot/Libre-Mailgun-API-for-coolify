<?php

namespace LibreMailApi;

use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LibreMailApi
{
    private $config;
    private $logger;
    private $messageHandler;
    private $storage;
    private $validator;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/config.php';
        $this->initializeLogger();
        $this->initializeComponents();
    }

    private function initializeLogger()
    {
        $this->logger = new Logger('libre-mail-api');
        if ($this->config['logging']['enabled']) {
            $logDir = dirname($this->config['logging']['file']);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $this->logger->pushHandler(
                new StreamHandler($this->config['logging']['file'], Logger::INFO)
            );
        }
    }

    private function initializeComponents()
    {
        $this->storage = new Storage($this->config);
        $this->validator = new Validator($this->config);
        $this->messageHandler = new MessageHandler($this->config, $this->storage, $this->logger);
    }

    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove leading slash
        $uri = ltrim($uri, '/');
        
        $this->logger->info("Request: $method $uri");

        try {
            // Authenticate request
            if (!$this->authenticate()) {
                return $this->sendResponse(401, ['message' => 'Unauthorized']);
            }

            // Route the request
            $response = $this->route($method, $uri);
            return $this->sendResponse($response['status'], $response['data']);

        } catch (\Exception $e) {
            $this->logger->error("Error handling request: " . $e->getMessage());
            return $this->sendResponse(500, ['message' => 'Internal server error']);
        }
    }

    private function authenticate()
    {
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            return false;
        }

        return $_SERVER['PHP_AUTH_USER'] === $this->config['api']['auth']['username'] &&
               $_SERVER['PHP_AUTH_PW'] === $this->config['api']['auth']['password'];
    }

    private function route($method, $uri)
    {
        // Parse URI patterns:
        // v3/{domain}/messages
        // v3/domains/{domain}/messages/{storage_key}
        $parts = explode('/', $uri);

        if (count($parts) < 3 || $parts[0] !== 'v3') {
            return ['status' => 404, 'data' => ['message' => 'Not found']];
        }

        // Handle different URI patterns
        if ($parts[1] === 'domains') {
            // Pattern: v3/domains/{domain}/messages/{storage_key}
            if (count($parts) < 4) {
                return ['status' => 404, 'data' => ['message' => 'Not found']];
            }
            $domain = $parts[2];
            $endpoint = $parts[3];
        } else {
            // Pattern: v3/{domain}/messages
            $domain = $parts[1];
            $endpoint = $parts[2];
        }

        switch ($method) {
            case 'POST':
                return $this->handlePost($domain, $endpoint, $parts);
            case 'GET':
                return $this->handleGet($domain, $endpoint, $parts);
            case 'DELETE':
                return $this->handleDelete($domain, $endpoint, $parts);
            default:
                return ['status' => 405, 'data' => ['message' => 'Method not allowed']];
        }
    }

    private function handlePost($domain, $endpoint, $parts)
    {
        switch ($endpoint) {
            case 'messages':
                return $this->messageHandler->sendMessage($domain, $_POST, $_FILES);
            case 'messages.mime':
                return $this->messageHandler->sendMimeMessage($domain, $_POST, $_FILES);
            default:
                return ['status' => 404, 'data' => ['message' => 'Endpoint not found']];
        }
    }

    private function handleGet($domain, $endpoint, $parts)
    {
        switch ($endpoint) {
            case 'messages':
                // Check for storage key in different positions based on URI pattern
                $storageKey = null;
                if ($parts[1] === 'domains' && isset($parts[4])) {
                    // Pattern: v3/domains/{domain}/messages/{storage_key}
                    $storageKey = $parts[4];
                } elseif ($parts[1] !== 'domains' && isset($parts[3])) {
                    // Pattern: v3/{domain}/messages/{storage_key}
                    $storageKey = $parts[3];
                }

                if ($storageKey) {
                    return $this->messageHandler->retrieveMessage($domain, $storageKey);
                }
                break;
            case 'sending_queues':
                return $this->messageHandler->getQueueStatus($domain);
            case 'smtp':
                // Handle SMTP-related endpoints
                if (isset($parts[4]) && $parts[4] === 'test') {
                    return $this->messageHandler->testSmtpConnection();
                } else {
                    return $this->messageHandler->getSmtpStatus();
                }
            default:
                return ['status' => 404, 'data' => ['message' => 'Endpoint not found']];
        }

        return ['status' => 404, 'data' => ['message' => 'Not found']];
    }

    private function handleDelete($domain, $endpoint, $parts)
    {
        switch ($endpoint) {
            case 'envelopes':
                return $this->messageHandler->deleteEnvelopes($domain);
            default:
                return ['status' => 404, 'data' => ['message' => 'Endpoint not found']];
        }
    }

    private function sendResponse($status, $data)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        return true;
    }
}
