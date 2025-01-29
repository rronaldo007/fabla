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
                'brandModel' => 'EOS M 290',
                'commissioningDate' => new \DateTime('2020-01-15'),
                'description' => 'A high-precision 3D printer designed for metal fusion projects.',
                'isArchived' => false,
            ],
            [
                'name' => 'Extrudeur nucléaire de nanotubes',
                'brandModel' => 'NanoExtrude X-200',
                'commissioningDate' => new \DateTime('2018-06-30'),
                'description' => 'An advanced nanotube extruder for scientific research.',
                'isArchived' => true, // This resource is archived
            ],
            [
                'name' => 'Micro-collisionneur laser',
                'brandModel' => 'Photon Collider 5000',
                'commissioningDate' => new \DateTime('2019-11-22'),
                'description' => 'A state-of-the-art laser collider for micro-scale experiments.',
                'isArchived' => false,
            ],
            [
                'name' => 'Calculateur quantique à impulsion',
                'brandModel' => 'QuantumPulse QX-1',
                'commissioningDate' => new \DateTime('2021-04-10'),
                'description' => 'A pulse-driven quantum computer for advanced calculations.',
                'isArchived' => true, // Another archived resource
            ],
        ];

        foreach ($resources as $resourceData) {
            $resource = new SharedResource();
            $resource->setName($resourceData['name']);
            $resource->setBrandModel($resourceData['brandModel']);
            $resource->setCommissioningDate($resourceData['commissioningDate']);
            $resource->setDescription($resourceData['description']);
            $resource->setArchived($resourceData['isArchived']);
            $manager->persist($resource);
        }

        $manager->flush();
    }
}
