<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Project;
use App\Domain\Entity\Risk;
use App\Domain\Entity\Task;
use App\Domain\Enum\RiskStatus;
use Dompdf\Dompdf;
use Dompdf\Options;

final class SteeringCommitteePdfService
{
    public function __construct(
        private readonly AiSummaryService $aiSummaryService,
    ) {
    }

    public function generate(Project $project): string
    {
        $html = $this->buildHtml($project);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function buildHtml(Project $project): string
    {
        $summary = htmlspecialchars($this->aiSummaryService->generateWeeklySummary($project), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $projectName = htmlspecialchars($project->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $status = htmlspecialchars($project->getStatus()->label(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $health = htmlspecialchars($project->getHealthStatus()->label(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $progress = number_format($project->getProgressPercent(), 1);
        $taskCount = $project->getTasks()->count();

        $overdueTasks = $project->getTasks()->filter(static fn (Task $task): bool => $task->isOverdue())->count();
        $openRisks = $project->getRisks()->filter(
            static fn (Risk $risk): bool => !in_array($risk->getStatus(), [RiskStatus::RESOLVED, RiskStatus::ACCEPTED], true)
        );

        $riskRows = '';
        foreach ($openRisks as $risk) {
            $riskRows .= sprintf(
                '<tr><td>%s</td><td>%d</td><td>%d</td><td>%s</td></tr>',
                htmlspecialchars($risk->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $risk->getProbability(),
                $risk->getImpact(),
                htmlspecialchars($risk->getStatus()->label(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        if ($riskRows === '') {
            $riskRows = '<tr><td colspan="4">Aucun risque actif</td></tr>';
        }

        $decisionRows = '';
        foreach ($project->getDecisions() as $decision) {
            $decisionRows .= sprintf(
                '<tr><td>%s</td><td>%s</td></tr>',
                htmlspecialchars($decision->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($decision->getMeetingDate()->format('d/m/Y'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        if ($decisionRows === '') {
            $decisionRows = '<tr><td colspan="2">Aucune décision enregistrée</td></tr>';
        }

        return <<<HTML
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
                    h1 { font-size: 20px; margin-bottom: 4px; }
                    h2 { font-size: 14px; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
                    th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                    th { background: #f5f5f5; }
                    .meta { color: #666; margin-bottom: 16px; }
                    .summary { background: #f9f9f9; padding: 12px; border-left: 4px solid #0d6efd; white-space: pre-wrap; }
                </style>
            </head>
            <body>
                <h1>Comité de pilotage — {$projectName}</h1>
                <p class="meta">Généré le {$this->formatDate(new \DateTimeImmutable())} · Projet interne</p>

                <h2>Indicateurs clés</h2>
                <table>
                    <tr><th>Statut</th><td>{$status}</td><th>Santé</th><td>{$health}</td></tr>
                    <tr><th>Avancement</th><td>{$progress} %</td><th>Tâches</th><td>{$taskCount}</td></tr>
                    <tr><th>Tâches en retard</th><td colspan="3">{$overdueTasks}</td></tr>
                </table>

                <h2>Synthèse hebdomadaire</h2>
                <div class="summary">{$summary}</div>

                <h2>Risques actifs</h2>
                <table>
                    <thead><tr><th>Titre</th><th>Probabilité</th><th>Impact</th><th>Statut</th></tr></thead>
                    <tbody>{$riskRows}</tbody>
                </table>

                <h2>Décisions récentes</h2>
                <table>
                    <thead><tr><th>Titre</th><th>Date</th></tr></thead>
                    <tbody>{$decisionRows}</tbody>
                </table>
            </body>
            </html>
            HTML;
    }

    private function formatDate(\DateTimeImmutable $date): string
    {
        return $date->format('d/m/Y H:i');
    }
}
