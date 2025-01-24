<?php

namespace App\DataFixtures;

use App\Entity\Role;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public const SUPER_ADMIN_ROLE = 'super-admin-role';
    public const ADMIN_ROLE = 'admin-role';
    public const JURY_ROLE = 'jury-role';
    public const CANDIDATE_ROLE = 'candidate-role';

    public function load(ObjectManager $manager): void
    {
        $roles = [
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

        foreach ($roles as $roleData) {
            $role = new Role();
            $role->setName($roleData['name'])
                 ->setDescription($roleData['description']);
            
            $manager->persist($role);
            $this->addReference($roleData['reference'], $role);
        }

        $manager->flush();
    }
}
