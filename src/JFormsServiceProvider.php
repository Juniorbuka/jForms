<?php

namespace Jevo\JForms;

use EvolutionCMS\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class JFormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $base = dirname(__DIR__);
        $this->mergeConfigFrom($base . '/config/jforms.php', 'jevo.jforms');
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
            $base . '/config/jforms.php' => EVO_CORE_PATH . 'custom/config/jevo/jforms.php',
        ], 'jforms-config');
        $this->publishes([
            $base . '/resources/js/jforms.min.js' => public_path('assets/modules/jforms/jforms.min.js'),
        ], 'jforms-assets');

        Blade::precompiler(static function (string $template): string {
            $script = '<script src="/assets/modules/jforms/jforms.min.js" defer></script>';
            if (!str_contains($template, 'data-jforms-form') || str_contains($template, $script)) {
                return $template;
            }

            $formEnd = strripos($template, '</form>');
            if ($formEnd === false) {
                return $template . "\n" . $script;
            }

            $insertAt = $formEnd + strlen('</form>');
            return substr($template, 0, $insertAt) . "\n" . $script . substr($template, $insertAt);
        });

        require $base . '/routes/frontend.php';
    }
}
