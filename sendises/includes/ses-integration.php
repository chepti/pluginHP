<?php

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

if (!defined('WPINC')) {
    die;
}

/**
 * Handles integration with Amazon SES.
 */
class SENDISES_SES_Integration {

    /**
     * Sends an email using Amazon SES.
     *
     * @param string $recipient The recipient's email address.
     * @param string $subject   The email subject.
     * @param string $body_html The HTML body of the email.
     * @param string $body_text The plain text body of the email.
     * @return array Associative array with 'status' and 'message'.
     */
    public static function send_email($recipient, $subject, $body_html, $body_text = '') {
        // Find the correct autoloader
        $composer_autoloader = SENDISES_PLUGIN_DIR . 'vendor/autoload.php';
        $manual_autoloader = SENDISES_PLUGIN_DIR . 'vendor/aws-autoloader.php';
        
        $autoloader_to_load = false;
        if (file_exists($composer_autoloader)) {
            $autoloader_to_load = $composer_autoloader;
        } elseif (file_exists($manual_autoloader)) {
            $autoloader_to_load = $manual_autoloader;
        }

        if (!$autoloader_to_load) {
            return [
                'status'  => 'error',
                'message' => 'AWS SDK not found. Please install it.'
            ];
        }
        require_once $autoloader_to_load;

        $options = get_option('sendises_settings');
        if (
            empty($options['ses_access_key']) ||
            empty($options['ses_secret_key']) ||
            empty($options['ses_region']) ||
            empty($options['ses_from_email'])
        ) {
            return [
                'status'  => 'error',
                'message' => 'SES settings are not configured.'
            ];
        }

        $config = [
            'version'     => 'latest',
            'region'      => $options['ses_region'],
            'credentials' => [
                'key'    => $options['ses_access_key'],
                'secret' => $options['ses_secret_key'],
            ],
        ];

        try {
            $client = new SesClient($config);
        } catch (Exception $e) {
             return [
                'status'  => 'error',
                'message' => 'Failed to initialize SES client: ' . $e->getMessage(),
            ];
        }

        if (empty($body_text)) {
            $body_text = wp_strip_all_tags($body_html);
        }

        try {
            $result = $client->sendEmail([
                'Destination' => [
                    'ToAddresses' => [$recipient],
                ],
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => 'UTF-8',
                            'Data' => $body_html,
                        ],
                        'Text' => [
                            'Charset' => 'UTF-8',
                            'Data' => $body_text,
                        ],
                    ],
                    'Subject' => [
                        'Charset' => 'UTF-8',
                        'Data' => $subject,
                    ],
                ],
                'Source' => $options['ses_from_email'],
            ]);

            return [
                'status'  => 'success',
                'message' => $result->get('MessageId'),
            ];
        } catch (AwsException $e) {
            return [
                'status'  => 'error',
                'message' => $e->getAwsErrorMessage(),
            ];
        } catch (Exception $e) {
            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
