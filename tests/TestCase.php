<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        if (! is_file(dirname(__DIR__).'/.env.testing')) {
            throw new RuntimeException('Tests require a local .env.testing file.');
        }

        $app = parent::createApplication();

        if ($app->configurationIsCached() || ! $app->environment('testing') || $app->environmentFile() !== '.env.testing') {
            throw new RuntimeException('Tests require uncached configuration loaded from .env.testing.');
        }

        $connection = $app->make('db')->connection();

        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== 'prueba_optimaretail_test') {
            throw new RuntimeException('Tests may only use MySQL database prueba_optimaretail_test.');
        }

        // Check both PDO connections before RefreshDatabase can run migrations or reset tables.
        foreach ([$connection->getPdo(), $connection->getReadPdo()] as $pdo) {
            $statement = $pdo->query('SELECT DATABASE()');

            if ($statement === false || $statement->fetchColumn() !== 'prueba_optimaretail_test') {
                throw new RuntimeException('The effective test database must be prueba_optimaretail_test.');
            }
        }

        return $app;
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
