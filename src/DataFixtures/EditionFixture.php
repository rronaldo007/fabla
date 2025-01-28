<?php

namespace App\DataFixtures;

use App\Entity\Edition;
use App\Entity\Submission;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;

class EditionFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create sample editions for different years
        for ($year = 2023; $year <= 2025; $year++) {
            // Create new Edition entity
            $edition = new Edition();
            $edition->setYear($year);
            $edition->setStartPublication(new \DateTimeImmutable("{$year}-01-01 00:00:00"));
            $edition->setStartApplication(new \DateTimeImmutable("{$year}-09-01 00:00:00"));
            $edition->setEndApplication(new \DateTimeImmutable("{$year}-09-30 23:59:59"));
            $edition->setAnnouncementDate(new \DateTimeImmutable("{$year}-10-15 00:00:00"));
            $edition->setIsCurrent($year === 2024); // Make 2024 the current edition


            $manager->persist($edition);
        }

        // Flush all data into the database
        $manager->flush();
    }
}
