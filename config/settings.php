
<?php

use Slim\App;

return static function (App $app) {
    $container = $app->getContainer();

    $container->set('expense_categories', function () {
        return [
            'groceries',
            'utilities',
            'transport',
            'entertainment',
            'housing',
            'health',
            'other',
        ];
    });

   
    $show = ($_ENV['APP_ENV'] ?? 'dev') === 'dev';
    $app->addErrorMiddleware($show, true, true);
};
