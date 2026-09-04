<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_route_serves_the_react_spa(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('spa');
    }
}
