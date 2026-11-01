<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed ACL roles/permissions after RefreshDatabase migrates.
     *
     * @var bool
     */
    protected $seed = true;

    /**
     * @var class-string
     */
    protected $seeder = RolesAndPermissionsSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Testes não dependem do build do front-end (public/build/manifest.json).
        $this->withoutVite();
    }
}
