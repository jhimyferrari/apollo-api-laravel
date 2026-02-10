<?php

describe('Create Organization and Login flow', function () {
    test('Create an Organization', function () {
        $data = [
            'name' => 'OrganizationFlow',
            'document' => '30053961000125',
            'email' => 'emailFlow@email.com',
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
    test('Login using Admin user', function () {
        $data = [
            'email' => 'emailFlow@email.com',
            'password' => '12345678',
            'organization_document' => '30053961000125',
        ];
        $header = [
            'Accept' => 'application/json',
        ];
        $response = $this->post(route('v1.login'),
            $data,
            $header);
        $response->assertStatus(200);
    });
});
