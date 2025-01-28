<?php
namespace App\Twig;

use App\Entity\Submission;  // Add this import
use App\Repository\SubmissionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SubmissionsExtension extends AbstractExtension
{
    public function __construct(
        private SubmissionRepository $submissionRepository,
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_user_submissions', [$this, 'getUserSubmissions']),
        ];
    }

    public function getUserSubmissions(): ?Submission  // Change return type to ?Submission
    {
        $user = $this->security->getUser();
        if (!$user) {
            return null;
        } else {
            $userProfile = $user->getUserProfile();

        }
        
        if ($userProfile && $userProfile->getCandidateProfile()) {
            return $this->submissionRepository->findOneBy([
                'candidateProfile' => $userProfile->getCandidateProfile(),
                'currentState' => 'under_review'
            ]);
        }

        return null;
    }
}