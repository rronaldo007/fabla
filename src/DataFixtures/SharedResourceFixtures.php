<?php

namespace App\DataFixtures;

use App\Entity\SharedResource;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SharedResourceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $resources = [
            [
                'name' => 'Imprimante 3D à fusion métallique',
                'description' => 'A high-precision 3D printer designed for metal fusion projects.',
            ],
            [
                'name' => 'Extrudeur nucléaire de nanotubes',
                'description' => 'An advanced nanotube extruder for scientific research.',
            ],
            [
                'name' => 'Micro-collisionneur laser',
                'description' => 'A state-of-the-art laser collider for micro-scale experiments.',
            ],
            [
                'name' => 'Calculateur quantique à impulsion',
                'description' => 'A pulse-driven quantum computer for advanced calculations.',
            ],
        ];

        foreach ($resources as $resourceData) {
            $resource = new SharedResource();
            $resource->setName($resourceData['name']);
            $resource->setDescription($resourceData['description']);
            $manager->persist($resource);
        }

        $manager->flush();
    }
}
