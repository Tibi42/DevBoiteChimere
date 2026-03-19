<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ActivityFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('now');
        $baseMonth = $now->modify('first day of this month');

        $activities = [
            [
                'title' => 'Senses Etch - Champs de Valoris',
                'type' => 'BOITE',
                'description' => 'Soirée découverte du jeu Senses Etch dans l\'univers Champs de Valoris.',
                'dayOffset' => min(25, (int) $now->format('t')) - 1,
                'hour' => 19,
                'minute' => 0,
                'duration' => 4,
                'location' => 'Local association',
            ],
            [
                'title' => 'Soirée Jeux de société',
                'type' => 'JDS',
                'description' => 'Venez jouer à une sélection de jeux de société. Ouvert à tous.',
                'dayOffset' => 7,
                'hour' => 19,
                'minute' => 0,
                'duration' => 4,
                'location' => 'Local association',
            ],
            [
                'title' => 'Soirée Jeux de société Expert',
                'type' => 'JDS',
                'description' => 'Session dédiée aux jeux experts. Prérequis : avoir déjà participé à une soirée.',
                'dayOffset' => 11,
                'hour' => 19,
                'minute' => 30,
                'duration' => 5,
                'location' => 'Local association',
            ],
            [
                'title' => 'Partie JDR - Donjons & Dragons',
                'type' => 'JDR',
                'description' => 'Suite de la campagne. Nouveaux joueurs bienvenus sur inscription.',
                'dayOffset' => 14,
                'hour' => 14,
                'minute' => 0,
                'duration' => 6,
                'location' => 'Salle du premier étage',
            ],
            [
                'title' => 'Partie JDR - Appel de Cthulhu',
                'type' => 'JDR',
                'description' => 'One-shot horreur. Places limitées.',
                'dayOffset' => 21,
                'hour' => 18,
                'minute' => 0,
                'duration' => 4,
                'location' => 'Local association',
            ],
            [
                'title' => 'Préparation GN été 2026',
                'type' => 'GN',
                'description' => 'Réunion d\'organisation et ateliers costumes pour le GN de l\'été.',
                'dayOffset' => 4,
                'hour' => 10,
                'minute' => 0,
                'duration' => 6,
                'location' => 'Local + extérieur',
            ],
            [
                'title' => 'Assemblée générale',
                'type' => 'AG',
                'description' => 'AG annuelle de l\'association. Ordre du jour : bilan, budget, élections.',
                'dayOffset' => 27,
                'hour' => 18,
                'minute' => 30,
                'duration' => 2,
                'location' => 'Local association',
            ],
            [
                'title' => 'Initiation jeux de plateau',
                'type' => 'JDS',
                'description' => 'Découverte de jeux accessibles pour débutants. Enfants bienvenus à partir de 8 ans.',
                'dayOffset' => 1,
                'hour' => 14,
                'minute' => 0,
                'duration' => 3,
                'location' => 'Local association',
            ],
            [
                'title' => 'Nouvel arrivage : soirée découverte',
                'type' => 'BOITE',
                'description' => 'Test des 15 nouveaux jeux arrivés au local. Inscription conseillée.',
                'dayOffset' => 18,
                'hour' => 19,
                'minute' => 0,
                'duration' => 4,
                'location' => 'Local association',
            ],
            // Même jour que "Soirée Jeux de société" (jour 8)
            [
                'title' => 'Midi jeux - déjeuner sur l\'herbe',
                'type' => 'JDS',
                'description' => 'Session jeux en extérieur avec pique-nique. Prévoir couverture et repas.',
                'dayOffset' => 7,
                'hour' => 12,
                'minute' => 0,
                'duration' => 3,
                'location' => 'Parc à côté du local',
            ],
            // Même jour que "Partie JDR - D&D" (jour 15)
            [
                'title' => 'Apéro jeux après la partie',
                'type' => 'BOITE',
                'description' => 'Petite soirée jeux légers après la partie de JDR. Ouvert à tous.',
                'dayOffset' => 14,
                'hour' => 20,
                'minute' => 0,
                'duration' => 3,
                'location' => 'Local association',
            ],
            // Même jour que "Préparation GN" (jour 5)
            [
                'title' => 'Repas commun - Prépa GN',
                'type' => 'GN',
                'description' => 'Repas partagé entre participants à la prépa GN.',
                'dayOffset' => 4,
                'hour' => 13,
                'minute' => 0,
                'duration' => 2,
                'location' => 'Local association',
            ],
        ];

        $lastDay = (int) $baseMonth->modify('last day of this month')->format('j');

        foreach ($activities as $data) {
            $day = $data['dayOffset'] + 1;
            if ($day > $lastDay) {
                continue;
            }

            $startAt = $baseMonth->setTime($data['hour'], $data['minute'], 0)->modify("+{$data['dayOffset']} days");
            $endAt = $startAt->modify("+{$data['duration']} hours");

            $activity = new Activity();
            $activity->setTitle($data['title']);
            $activity->setType($data['type']);
            $activity->setDescription($data['description']);
            $activity->setStartAt($startAt);
            $activity->setEndAt($endAt);
            $activity->setLocation($data['location']);

            $manager->persist($activity);
        }

        // Quelques événements le mois prochain
        $nextMonth = $baseMonth->modify('+1 month');
        $nextActivities = [
            ['title' => 'Soirée JDS - Spéciale stratégie', 'type' => 'JDS', 'day' => 2, 'hour' => 19, 'minute' => 0],
            ['title' => 'Week-end GN du Crépuscule', 'type' => 'GN', 'day' => 14, 'hour' => 9, 'minute' => 0],
            ['title' => 'Soirée in-game - GN du Crépuscule', 'type' => 'GN', 'day' => 14, 'hour' => 18, 'minute' => 0],
            ['title' => 'Partie JDR one-shot', 'type' => 'JDR', 'day' => 20, 'hour' => 14, 'minute' => 0],
        ];

        foreach ($nextActivities as $data) {
            $startAt = $nextMonth->setTime($data['hour'], $data['minute'], 0)->modify('+' . ($data['day'] - 1) . ' days');
            $endAt = $startAt->modify('+4 hours');

            $activity = new Activity();
            $activity->setTitle($data['title']);
            $activity->setType($data['type']);
            $activity->setStartAt($startAt);
            $activity->setEndAt($endAt);
            $activity->setLocation('Local association');

            $manager->persist($activity);
        }

        $manager->flush();
    }
}
