<?php

describe('POST api/organizations', function () {
    describe('anonymous user', function () {
        test('With Unique and Valid data', function () {
            $data = [
                'name' => 'newOrganization',
                'document' => '81185396012',
                'email' => 'uniqueEmail@email.com',
                'password' => '12345678',
            ];
            $header = [
                'Accept' => 'application/json',
            ];
            $response = $this->post(route('v1.organizations.store'),
                $data,
                $header);

            $response->assertStatus(201);
            $responseBody = $response->json();

            expect($responseBody['message'])->toBe('Organization created successfully.');

        });

    });
});
