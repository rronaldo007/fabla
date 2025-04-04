<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Submission;
use App\Entity\Edition;
use App\Entity\Evaluation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Twig\Environment;

class EmailService
{
    private $mailer;
    private $urlGenerator;
    private $translator;
    private $twig;
    private $fromEmail;
    private $fromName;
    private $adminEmail;
    
    public function __construct(
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        Environment $twig,
        string $fromEmail = 'no-reply@fablab.com',
        string $fromName = 'FabLab Platform',
        string $adminEmail = 'admin@fablab.com'
    ) {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->twig = $twig;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->adminEmail = $adminEmail;
    }

    /* --- AUTH RELATED EMAIL METHODS --- */
    
   /**
     * Sends the registration confirmation email to the user.
     *
     * @param User $user
     *
     * @return bool Returns true if the email was sent successfully.
     */
    public function sendRegistrationConfirmationEmail(User $user): bool
    {
        // Generate the absolute URL for email validation
        $validationUrl = $this->urlGenerator->generate(
            'app_validate_email',
            ['token' => $user->getEmailValidationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Build the email content
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Confirm Your Registration')
            ->html(
                '<p>Hello,</p>' .
                '<p>Thank you for registering on our platform. Please confirm your email address by clicking the link below:</p>' .
                '<p><a href="' . $validationUrl . '">' . $validationUrl . '</a></p>' .
                '<p>This link is valid for 24 hours.</p>' .
                '<p>If you did not register, please ignore this email.</p>'
            );

        try {
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Send email validation success notification
     */
    public function sendEmailValidationSuccessNotification(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject($this->translator->trans('email.validation.success.subject'))
            ->htmlTemplate('emails/email_validation_success.html.twig')
            ->context([
                'user' => $user,
                'loginUrl' => $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);
            
        $this->mailer->send($email);
    }
    
    /**
     * Send password reset email with reset link
     */
    public function sendPasswordResetEmail(User $user): void
    {
        if (!$user->getResetPasswordToken()) {
            throw new \InvalidArgumentException('User must have a reset password token');
        }
        
        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $user->getResetPasswordToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject($this->translator->trans('email.password.reset.subject'))
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expiresAt' => $user->getResetPasswordTokenExpiresAt()
            ]);
            
        $this->mailer->send($email);
    }
    
    /**
     * Send password changed confirmation
     */
    public function sendPasswordChangedConfirmation(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject($this->translator->trans('email.password.changed.subject'))
            ->htmlTemplate('emails/password_changed.html.twig')
            ->context([
                'user' => $user,
                'changeTime' => new \DateTime(),
                'supportEmail' => 'support@fablab.com'
            ]);
            
        $this->mailer->send($email);
    }
    
    /* --- APPLICATION RELATED EMAIL METHODS --- */
    
    /**
     * Send email notification when a user submits an initial application
     */
    public function sendApplicationSubmittedEmail(User $user, Submission $submission, Edition $edition): void
    {
        $continueUrl = $this->urlGenerator->generate(
            'app_apply_finish', 
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject($this->translator->trans('email.application.submitted.subject'))
            ->htmlTemplate('emails/application_submitted.html.twig')
            ->context([
                'user' => $user,
                'submission' => $submission,
                'edition' => $edition,
                'continueUrl' => $continueUrl
            ]);
            
        $this->mailer->send($email);
    }
    
    /**
     * Send email notification when application is submitted for review
     */
    public function sendApplicationUnderReviewEmail(User $user, Submission $submission): void
    {
        $statusUrl = $this->urlGenerator->generate(
            'application_confirmation', 
            ['id' => $submission->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject($this->translator->trans('email.application.under_review.subject'))
            ->htmlTemplate('emails/application_under_review.html.twig')
            ->context([
                'user' => $user,
                'submission' => $submission,
                'statusUrl' => $statusUrl
            ]);
            
        $this->mailer->send($email);
    }
    
    /**
     * Send email notification when application status changes
     */
    public function sendApplicationStatusChangeEmail(User $user, Submission $submission, string $newStatus): void
    {
        $statusUrl = $this->urlGenerator->generate(
            'application_confirmation', 
            ['id' => $submission->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject($this->translator->trans('email.application.status_change.subject', ['%status%' => $newStatus]))
            ->htmlTemplate('emails/application_status_change.html.twig')
            ->context([
                'user' => $user,
                'submission' => $submission,
                'newStatus' => $newStatus,
                'statusUrl' => $statusUrl
            ]);
            
        $this->mailer->send($email);
    }
    
    /**
     * Send email notification when application is accepted
     */
    public function sendApplicationAcceptedEmail(User $user, Submission $submission): void
    {
        $resultsUrl = $this->urlGenerator->generate(
            'application_results', 
            ['id' => $submission->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject($this->translator->trans('email.application.accepted.subject'))
            ->htmlTemplate('emails/application_accepted.html.twig')
            ->context([
                'user' => $user,
                'submission' => $submission,
                'resultsUrl' => $resultsUrl,
                'nextStepsUrl' => $resultsUrl
            ]);
            
        $this->mailer->send($email);
    }
    
    /**
     * Send email notification when application is rejected
     */
    public function sendApplicationRejectedEmail(User $user, Submission $submission, ?string $reason = null): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject($this->translator->trans('email.application.rejected.subject'))
            ->htmlTemplate('emails/application_rejected.html.twig')
            ->context([
                'user' => $user,
                'submission' => $submission,
                'rejectionReason' => $reason,
                'supportEmail' => 'support@fablab.com',
                'futureOpportunitiesUrl' => $this->urlGenerator->generate('app_apply_page', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);
            
        $this->mailer->send($email);
    }
    
    /**
     * Send reminder for incomplete application
     */
    public function sendIncompleteApplicationReminderEmail(User $user, Submission $submission): void
    {
        $continueUrl = $this->urlGenerator->generate(
            'app_apply_finish', 
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject($this->translator->trans('email.application.reminder.subject'))
            ->htmlTemplate('emails/application_reminder.html.twig')
            ->context([
                'user' => $user,
                'submission' => $submission,
                'continueUrl' => $continueUrl,
                'deadlineDate' => null // This could be fetched from the edition if available
            ]);
            
        $this->mailer->send($email);
    }
    
    /* --- ADMIN NOTIFICATION EMAILS --- */
    
    /**
     * Notify administrators about new submissions
     */
    public function sendAdminNewSubmissionNotificationEmail(Submission $submission, Edition $edition): void
    {

        
        $candidateProfile = $submission->getCandidateProfile();
        $userProfile = $candidateProfile->getUserProfile();
        $user = $userProfile->getUser();
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($this->adminEmail)
            ->subject($this->translator->trans('email.admin.new_submission.subject'))
            ->htmlTemplate('emails/admin/new_submission.html.twig')
            ->context([
                'submission' => $submission,
                'edition' => $edition,
                'user' => $user,
                'candidateProfile' => $candidateProfile,
            ]);
            
        $this->mailer->send($email);
    }
    
    /**
     * Notify administrators about submission status changes
     */
    public function sendAdminSubmissionStatusChangeEmail(Submission $submission, string $newStatus): void
    {

        
        $candidateProfile = $submission->getCandidateProfile();
        $userProfile = $candidateProfile->getUserProfile();
        $user = $userProfile->getUser();
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($this->adminEmail)
            ->subject($this->translator->trans('email.admin.status_change.subject', ['%status%' => $newStatus]))
            ->htmlTemplate('emails/admin/submission_status_change.html.twig')
            ->context([
                'submission' => $submission,
                'user' => $user,
                'candidateProfile' => $candidateProfile,
                'newStatus' => $newStatus,
            ]);
            
        $this->mailer->send($email);
    }

    /**
     * Send email requesting revisions to an application
     */
    public function sendApplicationRevisionRequestEmail(User $user, Submission $submission, string $revisionDetails, ?\DateTime $revisionDeadline = null): void
    {
        $revisionUrl = $this->urlGenerator->generate(
            'application_revision', 
            ['id' => $submission->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject($this->translator->trans('email.application.revision.subject'))
            ->htmlTemplate('emails/application_revision_request.html.twig')
            ->context([
                'user' => $user,
                'submission' => $submission,
                'revisionDetails' => $revisionDetails,
                'revisionUrl' => $revisionUrl,
                'revisionDeadline' => $revisionDeadline
            ]);
            
        $this->mailer->send($email);
    }

    /**
     * Send notification to candidate when evaluation has started
     */
    public function sendSubmissionEvaluationStartedEmail(User $user, Submission $submission): void
    {
        $statusUrl = $this->urlGenerator->generate(
            'application_confirmation', 
            ['id' => $submission->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($user->getEmail())
            ->subject($this->translator->trans('email.evaluation.started.subject'))
            ->htmlTemplate('emails/evaluation_started.html.twig')
            ->context([
                'user' => $user,
                'submission' => $submission,
                'statusUrl' => $statusUrl
            ]);
            
        $this->mailer->send($email);
    }

    /**
     * Send notification to admin about new evaluation
     */
    public function sendAdminEvaluationNotificationEmail(
        User $adminUser, 
        Submission $submission, 
        Evaluation $evaluation,
        User $juryMember
    ): void {
        
        $submissionUrl = $this->urlGenerator->generate(
            'admin_view_candidate', 
            ['id' => $submission->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $candidateProfile = $submission->getCandidateProfile();
        $userProfile = $candidateProfile->getUserProfile();
        $candidateUser = $userProfile->getUser();
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($adminUser->getEmail())
            ->subject($this->translator->trans('email.admin.evaluation.new.subject'))
            ->htmlTemplate('emails/admin/new_evaluation.html.twig')
            ->context([
                'adminUser' => $adminUser,
                'submission' => $submission,
                'evaluation' => $evaluation,
                'juryMember' => $juryMember,
                'candidateUser' => $candidateUser,
                'submissionUrl' => $submissionUrl
            ]);
            
        $this->mailer->send($email);
    }

    /**
     * Send notification to admin about updated evaluation
     */
    public function sendAdminEvaluationUpdatedNotificationEmail(
        User $adminUser, 
        Submission $submission, 
        Evaluation $evaluation,
        User $juryMember
    ): void {

        
        $submissionUrl = $this->urlGenerator->generate(
            'admin_view_candidate', 
            ['id' => $submission->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $candidateProfile = $submission->getCandidateProfile();
        $userProfile = $candidateProfile->getUserProfile();
        $candidateUser = $userProfile->getUser();
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($adminUser->getEmail())
            ->subject($this->translator->trans('email.admin.evaluation.updated.subject'))
            ->htmlTemplate('emails/admin/evaluation_updated.html.twig')
            ->context([
                'adminUser' => $adminUser,
                'submission' => $submission,
                'evaluation' => $evaluation,
                'juryMember' => $juryMember,
                'candidateUser' => $candidateUser,
                'submissionUrl' => $submissionUrl,
                'updatedAt' => new \DateTime() // Since the entity doesn't have an updatedAt field
            ]);
            
        $this->mailer->send($email);
    }

    /**
     * Send notification to admin when all evaluations are complete
     */
    public function sendAllEvaluationsCompletedEmail(User $adminUser, Submission $submission): void
    {
        $submissionUrl = $this->urlGenerator->generate(
            'admin_view_candidate', 
            ['id' => $submission->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $candidateProfile = $submission->getCandidateProfile();
        $userProfile = $candidateProfile->getUserProfile();
        $candidateUser = $userProfile->getUser();
        
        $evaluations = $submission->getEvaluations();
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($adminUser->getEmail())
            ->subject($this->translator->trans('email.admin.evaluation.completed.subject'))
            ->htmlTemplate('emails/admin/evaluations_completed.html.twig')
            ->context([
                'adminUser' => $adminUser,
                'submission' => $submission,
                'evaluations' => $evaluations,
                'candidateUser' => $candidateUser,
                'submissionUrl' => $submissionUrl
            ]);
            
        $this->mailer->send($email);
    }
}