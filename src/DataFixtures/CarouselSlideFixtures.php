<?php

namespace App\DataFixtures;

use App\Entity\CarouselSlide;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CarouselSlideFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $slides = [
            [
                'tag' => 'Prochain événement',
                'tagColor' => 'text-custom-orange',
                'title' => 'Senses Etch - Champs de Valoris',
                'date' => '25 JAN',
                'btnText' => "S'INSCRIRE",
                'btnClass' => 'bg-custom-orange group-hover:bg-orange-600 shadow-custom-orange/20',
                'btnUrl' => null,
            ],
            [
                'tag' => 'Nouvel article',
                'tagColor' => 'text-cyan-400',
                'title' => 'Guide : Peindre ses figurines',
                'date' => 'PUBLIÉ LUNDI',
                'btnText' => 'LIRE LA SUITE',
                'btnClass' => 'bg-cyan-600 group-hover:bg-cyan-700 shadow-cyan-500/20',
                'btnUrl' => null,
            ],
            [
                'tag' => 'Vie du forum',
                'tagColor' => 'text-purple-400',
                'title' => 'Organisation GN été 2026',
                'date' => 'EN DISCUSSION',
                'btnText' => 'PARTICIPER',
                'btnClass' => 'bg-purple-600 group-hover:bg-purple-700 shadow-purple-500/20',
                'btnUrl' => null,
            ],
            [
                'tag' => 'Soirée spéciale',
                'tagColor' => 'text-rose-400',
                'title' => 'Soirée Jeux de société Expert',
                'date' => '12 FÉVRIER',
                'btnText' => 'RÉSERVER',
                'btnClass' => 'bg-rose-600 group-hover:bg-rose-700 shadow-rose-500/20',
                'btnUrl' => null,
            ],
            [
                'tag' => 'Association',
                'tagColor' => 'text-emerald-400',
                'title' => 'Nouvel arrivage : 15 nouveaux jeux',
                'date' => 'DISPONIBLES AU LOCAL',
                'btnText' => 'VOIR LA LISTE',
                'btnClass' => 'bg-emerald-600 group-hover:bg-emerald-700 shadow-emerald-500/20',
                'btnUrl' => null,
            ],
        ];

        foreach ($slides as $index => $data) {
            $slide = new CarouselSlide();
            $slide->setPosition($index);
            $slide->setTag($data['tag']);
            $slide->setTagColor($data['tagColor']);
            $slide->setTitle($data['title']);
            $slide->setDate($data['date']);
            $slide->setBtnText($data['btnText']);
            $slide->setBtnClass($data['btnClass']);
            $slide->setBtnUrl($data['btnUrl']);
            $manager->persist($slide);
        }

        $manager->flush();
    }
}
