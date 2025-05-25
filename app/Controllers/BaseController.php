<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use App\Domain\Entity\User; 
abstract class BaseController
{

    public function __construct(
        protected Twig $view
    ) {}

    protected function render(Response $response, string $template, array $data = []): Response
    {
        if (session_status() === PHP_SESSION_NONE) 
        {
            session_start();
        }

        $data['currentUserId'] = $_SESSION['user_id'] ?? null;
        $data['currentUserName'] = $_SESSION['username'] ?? null;

        return $this->view->render($response, $template, $data);
    }

    protected function getCurrentUser(): ?User
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
            return null;
        }

        return new User(
            $_SESSION['user_id'],
            $_SESSION['username'],
            '',
            new \DateTimeImmutable() 
        );
    }


    // TODO: add here any common controller logic and use in concrete controllers
}
