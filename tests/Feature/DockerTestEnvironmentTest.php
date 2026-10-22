<?php

namespace Tests\Feature;

use Tests\TestCase;

class DockerTestEnvironmentTest extends TestCase
{
    public function test_tests_are_isolated_from_docker_services(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('array', config('mail.default'));
        $this->assertSame('sync', config('queue.default'));
    }
}
