<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiUnauthenticatedResponseTest extends TestCase
{
    public function refreshDatabase(): void
    {
        //
    }

    public function test_api_routes_return_json_unauthorized_without_accept_header(): void
    {
        $this->get('/api/v2/scheduling/preview')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
