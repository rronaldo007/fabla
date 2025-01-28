<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\WorkflowState;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class UserProfileFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $adminRole = new Role();
        $adminRole->setName('ROLE_ADMIN');
        $adminRole->setDescription('Candidate management and validation capabilities');
        $manager->persist($adminRole);

        $superAdminRole = new Role();
        $superAdminRole->setName('ROLE_SUPER_ADMIN');
        $superAdminRole->setDescription('Admin management and validation capabilities');
        $manager->persist($superAdminRole);

        $juryRole = new Role();
        $juryRole->setName('ROLE_JURY');
        $juryRole->setDescription('Jury management and validation capabilities');
        $manager->persist($juryRole);

        $candidateRole = new Role();
        $candidateRole->setName('ROLE_CANDIDATE');
        $candidateRole->setDescription('Candidate management and validation capabilities');
        $manager->persist($candidateRole);

        $manager->flush();

        $users = [
            [
                'email' => 'super.admin@example.com',
                'password' => 'superadmin123',
                'role' => $superAdminRole,
                'is_active' => true,
                'is_validated' => true,
                'current_place' => 'active',
                'workflow_states' => ['new', 'email_sent', 'email_validated', 'profile_completed', 'active'],
                'email_validation_token' => Uuid::v4()->toRfc4122(),
                'email_validation_token_expires_at' => new \DateTime('+1 day'),
                'profile' => [
                    'first_name' => 'Super',
                    'last_name' => 'Admin',
                    'phone' => '+1234567890',
                    'address' => '123 Admin St',
                    'date_of_birth' => new \DateTime('1990-01-01'),
                ]
            ],
            [
                'email' => 'admin@example.com',
                'password' => 'admin123',
                'role' => $adminRole,
                'is_active' => true,
                'is_validated' => true,
                'current_place' => 'active',
                'workflow_states' => ['new', 'email_sent', 'email_validated', 'profile_completed', 'active'],
                'email_validation_token' => Uuid::v4()->toRfc4122(),
                'email_validation_token_expires_at' => new \DateTime('+1 day'),
                'profile' => [
                    'first_name' => 'Admin',
                    'last_name' => 'User',
                    'phone' => '+1234567891',
                    'address' => '456 Admin Ave',
                    'date_of_birth' => new \DateTime('1991-02-02'),
                ]
            ],
            [
                'email' => 'jury@example.com',
                'password' => 'jury123',
                'role' => $juryRole,
                'is_active' => true,
                'is_validated' => true,
                'current_place' => 'active',
                'workflow_states' => ['new', 'email_sent', 'email_validated', 'profile_completed', 'active'],
                'email_validation_token' => Uuid::v4()->toRfc4122(),
                'email_validation_token_expires_at' => new \DateTime('+1 day'),
                'profile' => [
                    'first_name' => 'Jury',
                    'last_name' => 'Member',
                    'phone' => '+1234567892',
                    'address' => '789 Jury Blvd',
                    'date_of_birth' => new \DateTime('1992-03-03'),
                ]
            ],
            [
                'email' => 'candidate@example.com',
                'password' => 'candidate123',
                'role' => $candidateRole,
                'is_active' => true,
                'is_validated' => false,
                'current_place' => 'profile_completed',
                'workflow_states' => ['profile_completed'],
                'email_validation_token' => Uuid::v4()->toRfc4122(),
                'email_validation_token_expires_at' => new \DateTime('+1 day'),
                'profile' => [
                    'first_name' => 'Test',
                    'last_name' => 'Candidate',
                    'phone' => '+1234567893',
                    'address' => '321 Candidate Rd',
                    'date_of_birth' => new \DateTime('1993-04-04'),
                ]
            ],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email'])
                ->setPassword($this->passwordHasher->hashPassword($user, $userData['password']))
                ->setIsActive($userData['is_active'])
                ->setIsValidated($userData['is_validated'])
                ->setRole($userData['role'])
                ->setCurrentPlace($userData['current_place']);

            if (isset($userData['email_validation_token'])) {
                $user->setEmailValidationToken($userData['email_validation_token'])
                     ->setEmailValidationTokenExpiresAt($userData['email_validation_token_expires_at']);
            }

            foreach ($userData['workflow_states'] as $state) {
                $workflowState = new WorkflowState();
                $workflowState->setState($state);
                $workflowState->setUser($user);
                $manager->persist($workflowState);
            }

            $profile = new UserProfile();
            $profile->setFirstName($userData['profile']['first_name'])
                   ->setLastName($userData['profile']['last_name'])
                   ->setPhone($userData['profile']['phone'])
                   ->setAddress($userData['profile']['address'])
                   ->setDateOfBirth($userData['profile']['date_of_birth'])
                   ->setUser($user);

            $manager->persist($user);
            $manager->persist($profile);
        }

        $manager->flush();
    }

}