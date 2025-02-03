<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use App\Entity\User;
use App\Entity\Submission;

class EmailService
{
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;
    private LoggerInterface $logger;

    public function __construct(
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    /**
     * Generic email sender
     */
    public function sendEmail(string $to, string $subject, string $content): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($to)
            ->subject($subject)
            ->html($content);

        $this->mailer->send($email);
        $this->logger->info("Email sent to $to with subject: $subject");
    }

    // ==================== USER REGISTRATION WORKFLOW EMAILS ====================

    /**
     * Send validation email when user registers
     */
    public function sendValidationEmail(User $user): void
    {
        $validationUrl = $this->urlGenerator->generate(
            'app_validate_email',
            ['token' => $user->getEmailValidationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $content = "<p>Click <a href='{$validationUrl}'>here</a> to validate your email.</p>";

        $this->sendEmail($user->getEmail(), 'Validate your email', $content);
    }

    /**
     * Send email after email validation is successful
     */
    public function sendEmailValidatedNotification(User $user): void
    {
        $content = "<p>Congratulations, your email has been validated! You can now complete your profile.</p>";

        $this->sendEmail($user->getEmail(), 'Email Validated - Next Steps', $content);
    }

    /**
     * Send email when user profile is completed
     */
    public function sendProfileCompletedNotification(User $user): void
    {
        $content = "<p>Your profile is now complete. You can start applying to research projects.</p>";

        $this->sendEmail($user->getEmail(), 'Profile Completed', $content);
    }

    // ==================== SUBMISSION WORKFLOW EMAILS ====================

    /**
     * Notify the user when their submission is submitted
     */
    public function sendSubmissionNotification(Submission $submission): void
    {
        $content = "<p>Your submission has been received and is now under review.</p>";

        $this->sendEmail($submission->getCandidateProfile()->getUser()->getEmail(), 'Submission Received', $content);
    }

    /**
     * Notify the user when their submission is approved
     */
    public function sendSubmissionApprovedNotification(Submission $submission): void
    {
        $content = "<p>Congratulations! Your submission has been approved.</p>";

        $this->sendEmail($submission->getCandidateProfile()->getUser()->getEmail(), 'Submission Approved', $content);
    }

    /**
     * Notify the user when their submission is rejected
     */
    public function sendSubmissionRejectedNotification(Submission $submission): void
    {
        $content = "<p>Unfortunately, your submission was rejected. Feel free to reach out for feedback.</p>";

        $this->sendEmail($submission->getCandidateProfile()->getUser()->getEmail(), 'Submission Rejected', $content);
    }

    /**
     * Notify the user when they are accepted as a candidate
     */
    public function sendCandidateAcceptedNotification(Submission $submission): void
    {
        $content = "<p>Congratulations! You have been accepted as a candidate.</p>";

        $this->sendEmail($submission->getCandidateProfile()->getUser()->getEmail(), 'Candidate Accepted', $content);
    }

    /**
     * Notify the user when they are rejected as a candidate
     */
    public function sendCandidateRejectedNotification(Submission $submission): void
    {
        $content = "<p>Unfortunately, your application has not been accepted. We encourage you to apply again.</p>";

        $this->sendEmail($submission->getCandidateProfile()->getUser()->getEmail(), 'Candidate Rejected', $content);
    }

    // ==================== PASSWORD RESET EMAIL ====================

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(User $user): void
    {
        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $user->getResetPasswordToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $content = "<p>Click <a href='{$resetUrl}'>here</a> to reset your password. This link is valid for 1 hour.</p>";

        $this->sendEmail($user->getEmail(), 'Reset Your Password', $content);
    }

    public function sendEmailValidationSuccessNotification(User $user): void
    {
        $loginUrl = $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from('noreply@example.com')
            ->to($user->getEmail())
            ->subject('Email Successfully Validated')
            ->html("
                <p>Congratulations! Your email has been successfully validated.</p>
                <p>You can now log in and complete your profile.</p>
                <p><a href='{$loginUrl}'>Click here to login</a></p>
            ");

        $this->mailer->send($email);
    }

      /**
     * Send email to confirm submission application.
     */
    public function sendApplicationConfirmation(User $user, Submission $submission): void
    {
        $resultsUrl = $this->urlGenerator->generate('application_results', ['id' => $submission->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from('noreply@example.com')
            ->to($user->getEmail())
            ->subject('Application Submitted Successfully')
            ->html("
                <p>Your application has been successfully submitted.</p>
                <p>You can check your application status anytime here: <a href='{$resultsUrl}'>View Application Status</a></p>
                <p>Best regards,<br>Électron Research Team</p>
            ");

        $this->mailer->send($email);
    }

    /**
     * Notify candidate when application is accepted.
     */
    public function sendApplicationAccepted(User $user, Submission $submission): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($user->getEmail())
            ->subject('Congratulations! Your Application Has Been Accepted')
            ->html("
                <p>Congratulations! Your application for the Électron Research Lab has been <strong>accepted</strong>.</p>
                <p>We are excited to welcome you aboard.</p>
                <p>Best regards,<br>Électron Research Team</p>
            ");

        $this->mailer->send($email);
    }

    /**
     * Notify candidate when application is rejected.
     */
    public function sendApplicationRejected(User $user, Submission $submission): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($user->getEmail())
            ->subject('Application Update: Not Selected')
            ->html("
                <p>We appreciate your application to Électron, but unfortunately, your application was not selected this time.</p>
                <p>We encourage you to apply again in the future.</p>
                <p>Best regards,<br>Électron Research Team</p>
            ");

        $this->mailer->send($email);
    }

    public function sendContactMessage(string $fromEmail, string $subject, string $message, string $name): void
    {
        $email = (new Email())
            ->from($fromEmail)
            ->to('rukundoronaldo4@gmail.com') // Replace with your admin email
            ->subject("Contact Form: $subject")
            ->html("
                <p><strong>From:</strong> {$name} ({$fromEmail})</p>
                <p><strong>Message:</strong></p>
                <p>{$message}</p>
            ");

        $this->mailer->send($email);
    }
}
