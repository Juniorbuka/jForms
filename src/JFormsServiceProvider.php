<?php

namespace Jevo\JForms;

use EvolutionCMS\ServiceProvider;

class JFormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $base = dirname(__DIR__);
        $this->mergeConfigFrom($base . '/config/jforms.php', 'jforms');
        $this->app->singleton(SubmissionService::class);
        $this->app->registerRoutingModule(
            $this->app->getLocale() === 'en' ? 'Form responses' : 'Відповіді форм',
            $base . '/routes/manager.php',
            'tabler-forms'
        );
    }

    public function boot(): void
    {
        $base = dirname(__DIR__);
        $this->loadViewsFrom($base . '/resources/views', 'jForms');
        $this->loadTranslationsFrom($base . '/lang', 'jForms');
        $this->loadMigrationsFrom($base . '/migrations');
        $this->publishes([
            $base . '/config/jforms.php' => EVO_CORE_PATH . 'custom/config/jforms.php',
        ], 'jforms-config');

        require $base . '/routes/frontend.php';
    }
}
