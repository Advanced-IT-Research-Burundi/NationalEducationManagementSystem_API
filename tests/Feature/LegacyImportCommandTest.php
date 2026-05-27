<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyImportCommandTest extends TestCase
{
    public function test_it_requires_a_valid_destination_school(): void
    {
        $this->artisan('legacy:import-lysedb', [
            '--school-id' => 0,
            '--dry-run' => true,
        ])
            ->expectsOutput('Provide a valid --school-id for the default destination school.')
            ->assertExitCode(1);
    }
}
