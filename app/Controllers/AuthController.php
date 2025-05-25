<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
         // TODO: call corresponding service to perform user registration

        $data = (array) $request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');

        $errors = [];

        if (strlen($username) < 4) {
            $errors['username'] = 'Username must be at least 4 characters.';
        }

        if (strlen($password) < 8 || !preg_match('/\d/', $password)) {
            $errors['password'] = 'Password must be at least 8 characters and contain at least one number.';
        }

        if ($errors) {
            return $this->render($response, 'auth/register.twig', [
                'errors' => $errors,
                'old' => ['username' => $username],
            ]);
        }

        try {
            $this->authService->register($username, $password);

            return $response->withHeader('Location', '/login')->withStatus(302);
        } catch (\Exception $e) {
            error_log('Register error: ' . $e->getMessage());
            
            $errors['general'] = $e->getMessage();
            return $this->render($response, 'auth/register.twig', [
                'errors' => ['general' => $e->getMessage()],
                'old' => ['username' => $username],
            ]);
        }
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');

        $errors = [];

        try {
            $user = $this->authService->login($username, $password);

            //$_SESSION['user_id'] = $user->id;
            $data['currentUserId'] = $_SESSION['user_id'] ?? null;
            $data['currentUserName'] = $_SESSION['username'] ?? null;

            return $response->withHeader('Location', '/')->withStatus(302);
        } catch (\RuntimeException $e) {
            $errors['general'] = $e->getMessage(); 
        }

        return $this->render($response, 'auth/login.twig', [
            'errors' => $errors,
            'old' => ['username' => $username],
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        // TODO: handle logout by clearing session data and destroying session
        session_destroy();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
