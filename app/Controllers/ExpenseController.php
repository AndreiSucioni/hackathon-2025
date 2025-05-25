<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Container\ContainerInterface;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
        private readonly ContainerInterface $container
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the expenses page

        // Hints:
        // - use the session to get the current user ID
        // - use the request query parameters to determine the page number and page size
        // - use the expense service to fetch expenses for the current user
        
        $user = $this->getCurrentUser();
        if ($user === null) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
       
        // parse request parameters
        //$userId = 1; // TODO: obtain logged-in user ID from session
        $queryParams = $request->getQueryParams();

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        //$pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);
        $pageSize = 5;

        //$year = (int)date('Y');
        //$month = (int)date('n');
        
        $year = (isset($queryParams['year']) && $queryParams['year'] !== '') ? (int)$queryParams['year'] : null;
        $month = (isset($queryParams['month']) && $queryParams['month'] !== '') ? (int)$queryParams['month'] : null;
        //$expenses = $this->expenseService->list($user, $year, $month, $page, $pageSize);
        list($expenses, $total) = $this->expenseService->list($user, $year, $month, $page, $pageSize);
        
        $years = $this->expenseService->listExpenditureYears($user);

        $pagesCount = (int)ceil($total / $pageSize);

        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'page'     => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'pagesCount' => $pagesCount,
            'year' => $year,
            'month' => $month,
            'years' => $years,    
        ]);

    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view

        //return $this->render($response, 'expenses/create.twig', ['categories' => []]);
        $categories = $this->container->get('expense_categories');
        //return $this->view->render($response, 'expenses/create.twig');
        return $this->view->render($response, 'expenses/create.twig', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense
        // Hints:
        // - use the session to get the current user ID
        $user = $this->getCurrentUser();
        if ($user === null) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $data = (array) $request->getParsedBody();
        $dateString = trim($data['date'] ?? '');
        $category = trim($data['category'] ?? '');
        $amount = (float) trim($data['amount'] ?? '');
        $description = trim($data['description'] ?? '');
        
        try {
            $date = new \DateTimeImmutable($dateString);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid date format.');
        }

        $errors = [];

        try {
            $this->expenseService->create($user, $amount, $description, $date, $category);

            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\Exception $e) {
            error_log('Register error: ' . $e->getMessage());
            
            $errors['general'] = $e->getMessage();
            return $this->render($response, 'expenses/index.twig', [
                'errors' => ['general' => $e->getMessage()],
                //'old' => ['username' => $username],
            ]);
        }
        
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success
        return $response->withHeader('Location', '/expenses')->withStatus(302);
        //return $response;
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to display the edit expense page
        // Hints:
        // - obtain the list of available categories from configuration and pass to the view ..ok
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        /* $expenseId = (int)$routeParams['id']; */

        $currentUser = $this->getCurrentUser();

        if (!$currentUser) {
            return $response->withStatus(401); 
        }

        $expenseId = (int)($routeParams['id'] ?? 0);

        $expense = $this->expenseService->findId($expenseId);
//var_dump($currentUser);
        if (!$expense) {
            return $response->withStatus(404); 
        }

        if ($expense->getUserId() !== $currentUser->id) {
            return $response->withStatus(403);
        }

         $categories = $this->container->get('expense_categories');


        return $this->render($response, 'expenses/edit.twig', [
            'expense' => $expense,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            return $response->withStatus(401);
        }

        $expenseId = (int)($routeParams['id'] ?? 0);
        $expense = $this->expenseService->findId($expenseId);

        if (!$expense) {
            return $response->withStatus(404);
        }

        if ($expense->getUserId() !== $currentUser->id) {
            return $response->withStatus(403);
        }

        $data = (array)$request->getParsedBody();
        $amount = (float) trim($data['amount'] ?? '');
        $description = trim($data['description'] ?? '');
        $category = trim($data['category'] ?? '');
        $dateString = trim($data['date'] ?? '');

        try {
            $date = new \DateTimeImmutable($dateString);
        } catch (\Exception $e) {
            return $this->render($response, 'expenses/edit.twig', [
                'expense' => $expense,
                'errors' => ['date' => 'Invalid date format.']
            ]);
        }

        try {
            $this->expenseService->update($expense, $amount, $description, $date, $category);
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\Exception $e) {
            return $this->render($response, 'expenses/edit.twig', [
                'expense' => $expense,
                'errors' => ['general' => $e->getMessage()]
            ]);
        }

        //return $response;
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

        $expenseId = (int)($routeParams['id'] ?? 0);

        $expense = $this->expenseService->findId($expenseId);

        if (!$expense) {
            return $response->withStatus(404)->withBody('Expense not found');
        }

        $currentUser = $this->getCurrentUser();
         if ($expense->getUserId() !== $currentUser->id) {
            return $response->withStatus(403);
        }
        
        $this->expenseService->delete($expenseId);

        return $response
            ->withStatus(302)
            ->withHeader('Location', '/expenses');

        //return $response;
    }


    public function importCsv(Request $request, Response $response, array $args): Response
    {
        $user = $this->getCurrentUser();
        if ($user === null) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles['csv'])) {
            $response->getBody()->write('No CSV file uploaded');
            return $response->withStatus(400);
        }

        $csvFile = $uploadedFiles['csv'];
        //error_log('Uploaded file size: ' . $csvFile->getSize());
        try {
            $this->expenseService->importCsv($user, $csvFile);
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\Exception $e) {
            $response->getBody()->write('Import failed: ' . $e->getMessage());
            return $response->withStatus(500);
        }
    }



}
