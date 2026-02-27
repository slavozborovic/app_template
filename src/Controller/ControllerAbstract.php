<?php
namespace App\Controller;

use App\App;

abstract class ControllerAbstract
{
    protected App   $app;
    protected array $viewVars = [];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Assign a variable to the view.
     */
    protected function assign(string $name, mixed $value): void
    {
        $this->viewVars[$name] = $value;
    }

    /**
     * Render a view template.
     * E.g. render('index/index') loads view/index/index.php
     */
    protected function render(string $template): void
    {
        // Make view variables available
        extract($this->viewVars);

        // Provide common helpers
        $_locale  = $this->app->getLocale();
        $_shopUrl = $this->app->getShopData()['shop_url'] ?? '';

        $viewPath = __DIR__ . '/../../view/' . $template . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        include $viewPath;
    }
}
