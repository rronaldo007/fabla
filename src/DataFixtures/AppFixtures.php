<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\RegisterWorkflow;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture 
{
    public const SUPER_ADMIN_ROLE = 'super-admin-role';
    public const ADMIN_ROLE = 'admin-role';
    public const JURY_ROLE = 'jury-role';
    public const CANDIDATE_ROLE = 'candidate-role';

    private const ROLES = [
        [
            'name' => 'ROLE_SUPER_ADMIN',
            'description' => 'Full system access with contest management capabilities',
            'reference' => self::SUPER_ADMIN_ROLE
        ],
        [
            'name' => 'ROLE_ADMIN',
            'description' => 'Candidate management and validation capabilities',
            'reference' => self::ADMIN_ROLE
        ],
        [
            'name' => 'ROLE_JURY',
            'description' => 'Candidate evaluation and rating capabilities',
            'reference' => self::JURY_ROLE
        ],
        [
            'name' => 'ROLE_CANDIDATE',
            'description' => 'Application process and resource access if selected',
            'reference' => self::CANDIDATE_ROLE
        ],
    ];

    private const WORKFLOWS = [
        [
            'name' => 'Default Registration Flow',
            'key' => 'registration',
            'currentPlace' => 'new'
        ],
        [
            'name' => 'Email Registration Flow',
            'key' => 'registration_email',
            'currentPlace' => 'email_sent'
        ],
        [
            'name' => 'Validated Registration Flow',
            'key' => 'registration_validated',
            'currentPlace' => 'email_validated'
        ],
        [
            'name' => 'Profile Registration Flow',
            'key' => 'registration_profile',
            'currentPlace' => 'profile_completed'
        ],
        [
            'name' => 'Active Registration Flow',
            'key' => 'registration_active',
            'currentPlace' => 'active'
        ]
    ];

    public function load(ObjectManager $manager): void
    {
        $this->loadRoles($manager);
        $this->loadWorkflows($manager);
        $manager->flush();
    }

    private function loadRoles(ObjectManager $manager): void 
    {
        foreach (self::ROLES as $roleData) {
            $role = new Role();
            $role->setName($roleData['name'])
                 ->setDescription($roleData['description']);
            
            $manager->persist($role);
            $this->addReference($roleData['reference'], $role);
        }
    }

    private function loadWorkflows(ObjectManager $manager): void
    {
        foreach (self::WORKFLOWS as $index => $workflowData) {
            $workflow = new RegisterWorkflow();
            $workflow->setName($workflowData['name'])
                    ->setWorkflowKey($workflowData['key'])
                    ->setCurrentPlace($workflowData['currentPlace']);
            
            $manager->persist($workflow);
            $this->addReference('register_workflow_' . $index, $workflow);
        }
    }
}