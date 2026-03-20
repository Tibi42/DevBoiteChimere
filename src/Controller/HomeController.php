<?php

namespace App\Controller;

use App\Repository\ActivityRepository;
use App\Repository\CarouselSlideRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly CarouselSlideRepository $carouselSlideRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $month = (int) $request->query->get('month', (int) date('n'));
        $year = (int) $request->query->get('year', (int) date('Y'));
        $selectedDay = (int) $request->query->get('day', 0);

        // Limiter à un mois valide
        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        $firstDay = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $lastDay = $firstDay->modify('last day of this month')->setTime(23, 59, 59);
        $lastDayNum = (int) $lastDay->format('j');

        $activities = $this->activityRepository->findBetween($firstDay, $lastDay);

        // Jour sélectionné : filtrer les activités du jour (si jour valide)
        $activitiesForSelectedDay = [];
        if ($selectedDay >= 1 && $selectedDay <= $lastDayNum) {
            foreach ($activities as $activity) {
                if ((int) $activity->getStartAt()->format('j') === $selectedDay) {
                    $activitiesForSelectedDay[] = $activity;
                }
            }
        } else {
            $selectedDay = 0;
        }

        // Jours du mois qui ont au moins une activité + nombre d'activités par jour
        $daysWithActivities = [];
        $activitiesCountByDay = [];
        foreach ($activities as $activity) {
            $d = (int) $activity->getStartAt()->format('j');
            if (!\in_array($d, $daysWithActivities, true)) {
                $daysWithActivities[] = $d;
            }
            $activitiesCountByDay[$d] = ($activitiesCountByDay[$d] ?? 0) + 1;
        }

        // Grille du calendrier : offset (null) puis 1, 2, ... lastDay
        $dayOfWeek = (int) $firstDay->format('N'); // 1 = lundi, 7 = dimanche
        $offset = $dayOfWeek - 1;
        $calendarDays = array_fill(0, $offset, null);
        for ($d = 1; $d <= $lastDayNum; $d++) {
            $calendarDays[] = $d;
        }

        // Mois précédant / suivant pour la navigation
        $prev = $firstDay->modify('-1 month');
        $next = $firstDay->modify('+1 month');

        // Carousel : slides depuis la BDD ou valeurs par défaut
        $slides = $this->getCarouselSlides();

        $loginCsrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();
        $loginError = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('home/index.html.twig', [
            'login_csrf_token' => $loginCsrfToken,
            'login_error' => $loginError,
            'last_username' => $lastUsername,
            'slides' => $slides,
            'calendarMonth' => $month,
            'calendarYear' => $year,
            'calendarMonthName' => $this->getMonthName($month),
            'calendarDays' => $calendarDays,
            'daysWithActivities' => $daysWithActivities,
            'activitiesCountByDay' => $activitiesCountByDay,
            'activities' => $activities,
            'selectedDay' => $selectedDay,
            'activitiesForSelectedDay' => $activitiesForSelectedDay,
            'prevMonth' => (int) $prev->format('n'),
            'prevYear' => (int) $prev->format('Y'),
            'prevMonthName' => $this->getMonthName((int) $prev->format('n')),
            'nextMonth' => (int) $next->format('n'),
            'nextYear' => (int) $next->format('Y'),
            'nextMonthName' => $this->getMonthName((int) $next->format('n')),
        ]);
    }

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];
        return $months[$month] ?? '';
    }

    /**
     * @return array<int, array{tag: string, tag_color: string, title: string, date: string, btn_text: string, btn_class: string, btn_url: string|null}>
     */
    private function getCarouselSlides(): array
    {
        $entities = $this->carouselSlideRepository->findAllOrderByPosition();
        if (\count($entities) > 0) {
            $slides = [];
            foreach ($entities as $s) {
                $slides[] = [
                    'tag' => $s->getTag() ?? '',
                    'tag_color' => $s->getTagColor() ?? 'text-custom-orange',
                    'title' => $s->getTitle() ?? '',
                    'date' => $s->getDate() ?? '',
                    'btn_text' => $s->getBtnText() ?? '',
                    'btn_class' => $s->getBtnClass() ?? 'bg-custom-orange group-hover:bg-orange-600 shadow-custom-orange/20',
                    'btn_url' => $s->getBtnUrl(),
                ];
            }
            return $slides;
        }

        // Slides par défaut (comportement actuel si la table est vide)
        return [
            [
                'tag' => 'Prochain événement',
                'tag_color' => 'text-custom-orange',
                'title' => 'Senses Etch - Champs de Valoris',
                'date' => '25 JAN',
                'btn_text' => "S'INSCRIRE",
                'btn_class' => 'bg-custom-orange group-hover:bg-orange-600 shadow-custom-orange/20',
                'btn_url' => null,
            ],
            [
                'tag' => 'Nouvel article',
                'tag_color' => 'text-cyan-400',
                'title' => 'Guide : Peindre ses figurines',
                'date' => 'PUBLIÉ LUNDI',
                'btn_text' => 'LIRE LA SUITE',
                'btn_class' => 'bg-cyan-600 group-hover:bg-cyan-700 shadow-cyan-500/20',
                'btn_url' => null,
            ],
            [
                'tag' => 'Vie du forum',
                'tag_color' => 'text-purple-400',
                'title' => 'Organisation GN été 2026',
                'date' => 'EN DISCUSSION',
                'btn_text' => 'PARTICIPER',
                'btn_class' => 'bg-purple-600 group-hover:bg-purple-700 shadow-purple-500/20',
                'btn_url' => null,
            ],
            [
                'tag' => 'Soirée spéciale',
                'tag_color' => 'text-rose-400',
                'title' => 'Soirée Jeux de société Expert',
                'date' => '12 FÉVRIER',
                'btn_text' => 'RÉSERVER',
                'btn_class' => 'bg-rose-600 group-hover:bg-rose-700 shadow-rose-500/20',
                'btn_url' => null,
            ],
            [
                'tag' => 'Association',
                'tag_color' => 'text-emerald-400',
                'title' => 'Nouvel arrivage : 15 nouveaux jeux',
                'date' => 'DISPONIBLES AU LOCAL',
                'btn_text' => 'VOIR LA LISTE',
                'btn_class' => 'bg-emerald-600 group-hover:bg-emerald-700 shadow-emerald-500/20',
                'btn_url' => null,
            ],
        ];
    }
}
