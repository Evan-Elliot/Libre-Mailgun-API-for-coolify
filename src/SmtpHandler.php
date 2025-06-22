<?php

namespace LibreMailApi;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Psr\Log\LoggerInterface;

/**
 * SMTP Handler for actually sending emails
 * Handles real email delivery via SMTP servers
 */
class SmtpHandler
{
    private $config;
    private $logger;
    private $mailer;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->initializeMailer();
    }

    /**
     * Initialize PHPMailer with SMTP configuration
     */
    private function initializeMailer()
    {
        $this->mailer = new PHPMailer(true);

        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['smtp']['host'];
        $this->mailer->Port = $this->config['smtp']['port'];
        $this->mailer->Timeout = $this->config['smtp']['timeout'];

        // Authentication
        if ($this->config['smtp']['auth']) {
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp']['username'];
            $this->mailer->Password = $this->config['smtp']['password'];
        }

        // Encryption
        if ($this->config['smtp']['encryption'] === 'tls') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($this->config['smtp']['encryption'] === 'ssl') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        // SSL options
        if (!$this->config['smtp']['verify_peer']) {
            $this->mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => $this->config['smtp']['allow_self_signed']
                ]
            ];
        }

        // Debug settings
        if ($this->config['smtp']['debug']) {
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            $this->mailer->Debugoutput = function($str, $level) {
                $this->logger->debug("SMTP Debug: " . trim($str));
            };
        }
    }

    /**
     * Send a regular email message
     */
    public function sendMessage($message)
    {
        try {
            // Reset mailer for new message
            $this->mailer->clearAllRecipients();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();

            // Set sender
            $fromEmail = $this->extractEmail($message['from']);
            $fromName = $this->extractName($message['from']);
            
            if (empty($fromEmail)) {
                $fromEmail = $this->config['smtp']['from_email'];
                $fromName = $this->config['smtp']['from_name'];
            }

            $this->mailer->setFrom($fromEmail, $fromName);

            // Set recipients
            $recipients = $this->parseRecipients($message['to']);
            foreach ($recipients as $recipient) {
                $this->mailer->addAddress($recipient['email'], $recipient['name']);
            }

            // Set CC recipients if present
            if (!empty($message['cc'])) {
                $ccRecipients = $this->parseRecipients($message['cc']);
                foreach ($ccRecipients as $recipient) {
                    $this->mailer->addCC($recipient['email'], $recipient['name']);
                }
            }

            // Set BCC recipients if present
            if (!empty($message['bcc'])) {
                $bccRecipients = $this->parseRecipients($message['bcc']);
                foreach ($bccRecipients as $recipient) {
                    $this->mailer->addBCC($recipient['email'], $recipient['name']);
                }
            }

            // Set reply-to if present
            if (!empty($message['h:Reply-To'])) {
                $replyTo = $this->extractEmail($message['h:Reply-To']);
                $this->mailer->addReplyTo($replyTo);
            }

            // Set subject
            $this->mailer->Subject = $message['subject'] ?? '';

            // Set body content
            if (!empty($message['html'])) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $message['html'];
                if (!empty($message['text'])) {
                    $this->mailer->AltBody = $message['text'];
                }
            } else {
                $this->mailer->isHTML(false);
                $this->mailer->Body = $message['text'] ?? '';
            }

            // Add custom headers
            if (!empty($message['headers']) && is_array($message['headers'])) {
                foreach ($message['headers'] as $header) {
                    if (is_array($header) && count($header) >= 2) {
                        // Headers are in format [['name', 'value'], ...]
                        $name = $header[0];
                        $value = $header[1];

                        // Skip standard headers that PHPMailer handles automatically
                        $skipHeaders = ['mime-version', 'subject', 'from', 'to', 'content-transfer-encoding'];
                        if (!in_array(strtolower($name), $skipHeaders)) {
                            $this->mailer->addCustomHeader($name, $value);
                        }
                    } elseif (is_string($header)) {
                        // Handle string headers in format "Name: Value"
                        $parts = explode(':', $header, 2);
                        if (count($parts) === 2) {
                            $name = trim($parts[0]);
                            $value = trim($parts[1]);
                            $skipHeaders = ['mime-version', 'subject', 'from', 'to', 'content-transfer-encoding'];
                            if (!in_array(strtolower($name), $skipHeaders)) {
                                $this->mailer->addCustomHeader($name, $value);
                            }
                        }
                    }
                }
            }

            // Add attachments
            if (!empty($message['attachments']) && is_array($message['attachments'])) {
                foreach ($message['attachments'] as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $this->mailer->addAttachment(
                            $attachment['path'],
                            $attachment['name'] ?? basename($attachment['path']),
                            'base64',
                            $attachment['type'] ?? 'application/octet-stream'
                        );
                    }
                }
            }

            // Send the email
            $result = $this->mailer->send();

            $this->logger->info("Email sent successfully via SMTP", [
                'message_id' => $message['message_id'] ?? 'unknown',
                'to' => $message['to'],
                'subject' => $message['subject'] ?? 'no subject',
                'smtp_host' => $this->config['smtp']['host']
            ]);

            return [
                'success' => true,
                'message' => 'Email sent successfully via SMTP',
                'smtp_info' => $this->mailer->getSentMIMEMessage() ? 'Message sent' : 'No MIME info available'
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to send email via SMTP", [
                'message_id' => $message['message_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'smtp_host' => $this->config['smtp']['host']
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'smtp_error' => $this->mailer->ErrorInfo
            ];
        }
    }

    /**
     * Send a MIME message
     */
    public function sendMimeMessage($message)
    {
        try {
            // Reset mailer for new message
            $this->mailer->clearAllRecipients();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();

            // Set recipients
            $recipients = $this->parseRecipients($message['to']);
            foreach ($recipients as $recipient) {
                $this->mailer->addAddress($recipient['email'], $recipient['name']);
            }

            // Set the raw MIME message
            $this->mailer->MsgHTML($message['mime_content']);

            // Send the email
            $result = $this->mailer->send();

            $this->logger->info("MIME email sent successfully via SMTP", [
                'message_id' => $message['message_id'] ?? 'unknown',
                'to' => $message['to'],
                'smtp_host' => $this->config['smtp']['host']
            ]);

            return [
                'success' => true,
                'message' => 'MIME email sent successfully via SMTP'
            ];

        } catch (Exception $e) {
            $this->logger->error("Failed to send MIME email via SMTP", [
                'message_id' => $message['message_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'smtp_host' => $this->config['smtp']['host']
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'smtp_error' => $this->mailer->ErrorInfo
            ];
        }
    }

    /**
     * Test SMTP connection
     */
    public function testConnection()
    {
        try {
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            
            return [
                'success' => true,
                'message' => 'SMTP connection successful'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract email address from string like "Name <email@domain.com>"
     */
    private function extractEmail($emailString)
    {
        if (preg_match('/<([^>]+)>/', $emailString, $matches)) {
            return trim($matches[1]);
        }
        return trim($emailString);
    }

    /**
     * Extract name from string like "Name <email@domain.com>"
     */
    private function extractName($emailString)
    {
        if (preg_match('/^([^<]+)</', $emailString, $matches)) {
            $name = trim($matches[1]);

            // Remove outer quotes if present (both single and double quotes)
            if (preg_match('/^["\'](.+)["\']$/', $name, $quoteMatches)) {
                $name = $quoteMatches[1];

                // Remove escaped characters (handles cases like \"Name\" -> "Name")
                $name = stripslashes($name);

                // Remove quotes again if they were escaped (handles "\"Name\"" -> Name)
                if (preg_match('/^["\'](.+)["\']$/', $name, $innerQuotes)) {
                    $name = $innerQuotes[1];
                }
            }

            return $name;
        }
        return '';
    }

    /**
     * Parse recipients string into array of email/name pairs
     */
    private function parseRecipients($recipientsString)
    {
        $recipients = [];
        $emails = explode(',', $recipientsString);
        
        foreach ($emails as $email) {
            $email = trim($email);
            if (!empty($email)) {
                $recipients[] = [
                    'email' => $this->extractEmail($email),
                    'name' => $this->extractName($email)
                ];
            }
        }
        
        return $recipients;
    }

    /**
     * Check if SMTP is enabled in configuration
     */
    public function isEnabled()
    {
        return !empty($this->config['smtp']['enabled']) && 
               !empty($this->config['smtp']['host']) && 
               !empty($this->config['smtp']['username']);
    }
}
