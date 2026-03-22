<?php

namespace App\Controller;

use App\Enum\ActivityKind;
use App\Repository\ActivityRepository;
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
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');
        $today = (int) date('j');

        $month = (int) $request->query->get('month', $currentMonth);
        $year = (int) $request->query->get('year', $currentYear);

        // Filtre par type d'activité
        $allowedTypes = ActivityKind::values();
        $filterType = $request->query->get('type');
        if ($filterType !== null && !\in_array($filterType, $allowedTypes, true)) {
            $filterType = null;
        }

        // Pré-sélectionner le jour courant si on est sur le mois en cours et qu'aucun jour n'est explicitement demandé
        $defaultDay = ($month === $currentMonth && $year === $currentYear) ? $today : 0;
        $selectedDay = (int) $request->query->get('day', $defaultDay);

        // Limiter à un mois valide
        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        $firstDay = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $lastDay = $firstDay->modify('last day of this month')->setTime(23, 59, 59);
        $lastDayNum = (int) $lastDay->format('j');

        $activities = $this->activityRepository->findBetween($firstDay, $lastDay, $filterType);

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

        // Jours du mois qui ont au moins une activité + nombre d'activités par jour + types par jour
        $daysWithActivities = [];
        $activitiesCountByDay = [];
        $activitiesTypesByDay = [];
        foreach ($activities as $activity) {
            $d = (int) $activity->getStartAt()->format('j');
            if (!\in_array($d, $daysWithActivities, true)) {
                $daysWithActivities[] = $d;
            }
            $activitiesCountByDay[$d] = ($activitiesCountByDay[$d] ?? 0) + 1;
            $type = $activity->getType() ?? '';
            if ($type && !isset($activitiesTypesByDay[$d])) {
                $activitiesTypesByDay[$d] = $type;
            } elseif ($type && isset($activitiesTypesByDay[$d]) && $activitiesTypesByDay[$d] !== $type) {
                $activitiesTypesByDay[$d] = 'mixed';
            }
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

        $loginCsrfToken = $this->csrfTokenManager->getToken('authenticate')->getValue();
        $loginError = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('home/index.html.twig', [
            'nowYear'  => (int) (new \DateTimeImmutable())->format('Y'),
            'nowMonth' => (int) (new \DateTimeImmutable())->format('n'),
            'nowDay'   => (int) (new \DateTimeImmutable())->format('j'),
            'login_csrf_token' => $loginCsrfToken,
            'login_error' => $loginError,
            'last_username' => $lastUsername,
            'today' => ($month === $currentMonth && $year === $currentYear) ? $today : 0,
            'calendarMonth' => $month,
            'calendarYear' => $year,
            'calendarMonthName' => $this->getMonthName($month),
            'calendarDays' => $calendarDays,
            'daysWithActivities' => $daysWithActivities,
            'activitiesCountByDay' => $activitiesCountByDay,
            'activitiesTypesByDay' => $activitiesTypesByDay,
            'filterType' => $filterType,
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

}
