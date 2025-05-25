<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Domain\Service\MonthlySummaryService;
use App\Domain\Service\AlertGenerator;
use App\Domain\Service\ExpenseService;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        // TODO: add necessary services here and have them injected by the DI container
        private readonly ExpenseService $expenseService,
        private readonly MonthlySummaryService $monthlySummaryService,
        private readonly AlertGenerator $alertGenerator,
    )
    {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: parse the request parameters
        // TODO: load the currently logged-in user
        // TODO: get the list of available years for the year-month selector
        // TODO: call service to generate the overspending alerts for current month
        // TODO: call service to compute total expenditure per selected year/month
        // TODO: call service to compute category totals per selected year/month
        // TODO: call service to compute category averages per selected year/month

        $user = $this->getCurrentUser();
        if ($user === null) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $queryParams = $request->getQueryParams();
        $year = isset($queryParams['year']) ? (int)$queryParams['year'] : (int)date('Y');
        $month = isset($queryParams['month']) ? (int)$queryParams['month'] : (int)date('n');

        // Exemplu: apelează serviciul tău MonthlySummaryService
        $totalForMonth = $this->monthlySummaryService->computeTotalExpenditure($user, $year, $month);
        $totalsForCategories = $this->monthlySummaryService->computePerCategoryTotals($user, $year, $month);
        $averagesForCategories = $this->monthlySummaryService->computePerCategoryAverages($user, $year, $month);

        // Alertele: presupunem că ai un AlertGeneratorService cu funcția generateAlerts(user, year, month)
        $alerts = $this->alertGenerator->generate($user, $year, $month);

        $years = $this->expenseService->listExpenditureYears($user);

        return $this->render($response, 'dashboard.twig', [
            'alerts'                => $alerts,
            'totalForMonth'         => $totalForMonth,
            'totalsForCategories'   => $totalsForCategories,
            'averagesForCategories' => $averagesForCategories,
            'year'                  => $year,
            'month'                 => $month,
            'years'                 => $years,
        ]);
    }
}
