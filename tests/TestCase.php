<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Monolog\Handler\NullHandler;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'logging.default' => 'null',
            'logging.channels.stellar' => [
                'driver' => 'monolog',
                'handler' => NullHandler::class,
            ],
            'logging.channels.security' => [
                'driver' => 'monolog',
                'handler' => NullHandler::class,
            ],
        ]);

        $this->withoutVite();
    }
}
