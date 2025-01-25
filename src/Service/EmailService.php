<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function sendValidationEmail(User $user): void
    {
        $validationUrl = $this->urlGenerator->generate(
            'app_validate_email',
            ['token' => $user->getEmailValidationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from('noreply@example.com')
            ->to($user->getEmail())
            ->subject('Validate your email')
            ->html("<p>Click <a href='{$validationUrl}'>here</a> to validate your email.</p>");

        $this->mailer->send($email);
    }
}