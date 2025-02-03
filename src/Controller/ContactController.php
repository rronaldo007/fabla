<?php

namespace App\Controller;

use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ContactController extends AbstractController
{
    public function __construct(private EmailService $emailService) {}

    #[Route('/contact', name: 'app_contact')]
    public function index(): Response
    {
        return $this->render('contact/index.html.twig');
    }

    #[Route('/contact/submit', name: 'contact_submit', methods: ['POST'])]
    public function submit(Request $request, ValidatorInterface $validator): Response
    {
        $data = $request->request->all();

        // Validate form fields
        $constraints = new Assert\Collection([
            'name' => [new Assert\NotBlank(), new Assert\Length(['min' => 3])],
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'subject' => [new Assert\NotBlank(), new Assert\Length(['min' => 5])],
            'message' => [new Assert\NotBlank(), new Assert\Length(['min' => 10])]
        ]);

        $violations = $validator->validate($data, $constraints);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $this->addFlash('error', $violation->getMessage());
            }
            return $this->redirectToRoute('app_contact');
        }

        // Send email
        $this->emailService->sendContactMessage(
            $data['email'],
            $data['subject'],
            $data['message'],
            $data['name']
        );

        $this->addFlash('success', 'Your message has been sent successfully.');
        return $this->redirectToRoute('app_contact');
    }
}

