<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\IncidentRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CalendarController extends AbstractController
{
    private const MONTHS = [
        1 => 'janvier',
        2 => 'février',
        3 => 'mars',
        4 => 'avril',
        5 => 'mai',
        6 => 'juin',
        7 => 'juillet',
        8 => 'août',
        9 => 'septembre',
        10 => 'octobre',
        11 => 'novembre',
        12 => 'décembre',
    ];

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly IncidentRepository $incidentRepository,
    ) {
    }

    #[Route('/calendrier', name: 'app_calendar', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $cursor = $this->month($request->query->getString('month'));
        $monthStart = $cursor;
        $monthEnd = $cursor->modify('last day of this month');
        $gridStart = $cursor->modify('-' . ((int) $cursor->format('N') - 1) . ' days');
        $today = new \DateTimeImmutable('today');

        /** @var array<string, list<array{kind: string, title: string, url: string}>> $byDay */
        $byDay = [];
        $monthProjects = [];
        foreach ($this->projectRepository->findBy([], ['name' => 'ASC']) as $project) {
            $start = $project->getStartDate();
            $end = $project->getForecastEndDate() ?? $project->getEndDate() ?? $start;
            if ($end < $start) {
                $end = $start;
            }
            if ($this->overlaps($start, $end, $monthStart, $monthEnd)) {
                $monthProjects[] = $project;
            }
            $this->push($byDay, $start, [
                'kind' => 'project-start',
                'title' => $project->getName(),
                'url' => $this->generateUrl('app_project_show', ['id' => $project->getId()]),
            ]);
            if ($end->format('Y-m-d') !== $start->format('Y-m-d')) {
                $this->push($byDay, $end, [
                    'kind' => 'project-end',
                    'title' => 'Fin · ' . $project->getName(),
                    'url' => $this->generateUrl('app_project_show', ['id' => $project->getId()]),
                ]);
            }
            $monthKey = $cursor->format('Y-m');
            if ($start->format('Y-m') !== $monthKey && $end->format('Y-m') !== $monthKey && $this->overlaps($start, $end, $monthStart, $monthEnd)) {
                $this->push($byDay, $monthStart, [
                    'kind' => 'project-start',
                    'title' => 'En cours · ' . $project->getName(),
                    'url' => $this->generateUrl('app_project_show', ['id' => $project->getId()]),
                ]);
            }
        }

        $monthIncidents = [];
        foreach ($this->incidentRepository->findBy([], ['openedAt' => 'DESC']) as $incident) {
            $opened = $incident->getOpenedAt();
            $due = $incident->getDueDate();
            $inMonth = $this->overlaps($opened, $opened, $monthStart, $monthEnd)
                || ($due !== null && $this->overlaps($due, $due, $monthStart, $monthEnd));
            if ($inMonth) {
                $monthIncidents[] = $incident;
            }
            $this->push($byDay, $opened, [
                'kind' => 'incident',
                'title' => $incident->getTitle(),
                'url' => $this->generateUrl('app_incident_show', ['id' => $incident->getId()]),
            ]);
            if ($due !== null && $due->format('Y-m-d') !== $opened->format('Y-m-d')) {
                $this->push($byDay, $due, [
                    'kind' => 'incident-due',
                    'title' => 'Échéance · ' . $incident->getTitle(),
                    'url' => $this->generateUrl('app_incident_show', ['id' => $incident->getId()]),
                ]);
            }
        }

        $days = [];
        for ($i = 0; $i < 42; ++$i) {
            $date = $gridStart->modify('+' . $i . ' days');
            $key = $date->format('Y-m-d');
            $days[] = [
                'date' => $date,
                'inMonth' => $date->format('Y-m') === $cursor->format('Y-m'),
                'isToday' => $key === $today->format('Y-m-d'),
                'events' => $byDay[$key] ?? [],
            ];
        }

        return $this->render('calendar/index.html.twig', [
            'monthLabel' => self::MONTHS[(int) $cursor->format('n')] . ' ' . $cursor->format('Y'),
            'previousMonth' => $cursor->modify('-1 month')->format('Y-m'),
            'nextMonth' => $cursor->modify('+1 month')->format('Y-m'),
            'todayMonth' => $today->format('Y-m'),
            'days' => $days,
            'monthProjects' => $monthProjects,
            'monthIncidents' => $monthIncidents,
        ]);
    }

    private function month(string $raw): \DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m', $raw);

        return $parsed instanceof \DateTimeImmutable ? $parsed : new \DateTimeImmutable('first day of this month');
    }

    private function overlaps(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        \DateTimeImmutable $rangeStart,
        \DateTimeImmutable $rangeEnd,
    ): bool {
        return $start->format('Y-m-d') <= $rangeEnd->format('Y-m-d')
            && $end->format('Y-m-d') >= $rangeStart->format('Y-m-d');
    }

    /**
     * @param array<string, list<array{kind: string, title: string, url: string}>> $byDay
     * @param array{kind: string, title: string, url: string} $event
     */
    private function push(array &$byDay, \DateTimeImmutable $date, array $event): void
    {
        $byDay[$date->format('Y-m-d')][] = $event;
    }
}
