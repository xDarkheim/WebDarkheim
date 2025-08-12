<?php
/**
 * Controller for handling contact form submissions
 * This controller handles the submission of contact form data and sends an email to the admin.
 * It validates the form data, sends an email, and handles errors.
 *
 * @author Dmytro Hovenko
 */

declare(strict_types=1);

namespace App\Application\Controllers;

use Exception;

class ContactFormController extends BaseFormController
{
    public function handle(): void
    {
        // Check request method
        if (!$this->requirePostMethod()) {
            $this->redirect('/index.php?page=contact');
            return;
        }

        // Validate CSRF token
        if (!$this->validateCSRF()) {
            $this->handleValidationError(
                'Security error: Invalid CSRF token.',
                '/index.php?page=contact'
            );
            return;
        }

        $contactData = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'subject' => trim($_POST['subject'] ?? ''),
            'message' => trim($_POST['message'] ?? '')
        ];

        // Save form data for repopulation on error
        $_SESSION['form_data_contact'] = $contactData;

        // Validation for required fields
        if (empty($contactData['name']) || empty($contactData['email']) ||
            empty($contactData['subject']) || empty($contactData['message'])) {
            $this->handleValidationError(
                'Please fill in all required fields.',
                '/index.php?page=contact'
            );
            return;
        }

        // Validation for email
        if (!filter_var($contactData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->handleValidationError(
                'Please enter a valid email address.',
                '/index.php?page=contact'
            );
            return;
        }

        // Validation for subject and message length
        if (strlen($contactData['name']) > 100) {
            $this->handleValidationError(
                'Name must be less than 100 characters.',
                '/index.php?page=contact'
            );
            return;
        }

        if (strlen($contactData['subject']) > 200) {
            $this->handleValidationError(
                'Subject must be less than 200 characters.',
                '/index.php?page=contact'
            );
            return;
        }

        if (strlen($contactData['message']) > 2000) {
            $this->handleValidationError(
                'Message must be less than 2000 characters.',
                '/index.php?page=contact'
            );
            return;
        }

        try {
            // Send email to admin
            $mailer = $this->services->getMailer();

            $emailSent = $mailer->sendTemplateEmail(
                'admin@' . parse_url($_SERVER['HTTP_HOST'] ?? 'localhost', PHP_URL_HOST),
                'Contact Form Submission: ' . $contactData['subject'],
                'contact_form',
                [
                    'name' => $contactData['name'],
                    'email' => $contactData['email'],
                    'subject' => $contactData['subject'],
                    'message' => $contactData['message'],
                    'date' => date('Y-m-d H:i:s'),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]
            );

            if ($emailSent) {
                // Clear form data after successful submission
                unset($_SESSION['form_data_contact']);

                $this->logger->info('Contact form submitted successfully', [
                    'name' => $contactData['name'],
                    'email' => $contactData['email'],
                    'subject' => $contactData['subject'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);

                $this->flashMessage->addSuccess(
                    'Thank you for your message! We will get back to you soon.'
                );
                $this->redirect('/index.php?page=contact');
            } else {
                throw new Exception('Failed to send email');
            }
        } catch (Exception $e) {
            $this->logger->error('Contact form submission failed', [
                'name' => $contactData['name'],
                'email' => $contactData['email'],
                'error' => $e->getMessage()
            ]);

            $this->handleValidationError(
                'Failed to send your message. Please try again later.',
                '/index.php?page=contact'
            );
        }
    }
}
