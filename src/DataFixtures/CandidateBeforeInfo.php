<?php

namespace App\DataFixtures;

use App\Entity\School;
use App\Entity\Specialization;
use App\Entity\Nationality;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CandidateBeforeInfo extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Schools
        $schools = [
            ['name' => 'Harvard University', 'location' => 'Cambridge, Massachusetts'],
            ['name' => 'Oxford University', 'location' => 'Oxford, United Kingdom'],
            ['name' => 'ETH Zürich', 'location' => 'Zürich, Switzerland'],
            ['name' => 'University of Tokyo', 'location' => 'Tokyo, Japan'],
            ['name' => 'Stanford University', 'location' => 'Stanford, California']
        ];

        foreach ($schools as $schoolData) {
            $school = new School();
            $school->setName($schoolData['name']);
            $school->setLocation($schoolData['location']);
            $manager->persist($school);
        }

        // Specializations
        $specializations = [
            ['name' => 'Industrial Robotics', 'description' => 'Design and implementation of robotic systems for manufacturing and industrial automation'],
            ['name' => 'Humanoid Robotics', 'description' => 'Development of human-like robots for interaction and assistance'],
            ['name' => 'Medical Robotics', 'description' => 'Robotics applications in surgery, rehabilitation, and healthcare'],
            ['name' => 'AI and Robotics', 'description' => 'Integration of artificial intelligence with robotic systems'],
            ['name' => 'Micro-Robotics', 'description' => 'Study of miniature robots for precision tasks and medical applications']
        ];

        foreach ($specializations as $specializationData) {
            $specialization = new Specialization();
            $specialization->setName($specializationData['name']);
            $specialization->setDescription($specializationData['description']);
            $manager->persist($specialization);
        }

        // Nationalities
        $nationalities = [
            ['name' => 'United States', 'code' => 'US'],
            ['name' => 'United Kingdom', 'code' => 'GB'],
            ['name' => 'France', 'code' => 'FR'],
            ['name' => 'Germany', 'code' => 'DE'],
            ['name' => 'Japan', 'code' => 'JP'],
            ['name' => 'China', 'code' => 'CN'],
            ['name' => 'India', 'code' => 'IN'],
            ['name' => 'Brazil', 'code' => 'BR']
        ];

        foreach ($nationalities as $nationalityData) {
            $nationality = new Nationality();
            $nationality->setName($nationalityData['name']);
            $nationality->setCode($nationalityData['code']);
            $manager->persist($nationality);
        }

        $manager->flush();
    }
}