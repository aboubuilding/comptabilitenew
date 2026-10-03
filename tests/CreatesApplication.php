<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Les tests vident et reconstruisent la base : jamais sur la base de travail.
        $base = (string) $app['config']->get('database.connections.'.$app['config']->get('database.default').'.database');
        if (! str_ends_with($base, '_test')) {
            throw new RuntimeException(
                "Tests interrompus : la base « {$base} » n'est pas une base de test (nom attendu en *_test, voir phpunit.xml)."
            );
        }

        return $app;
    }
}
