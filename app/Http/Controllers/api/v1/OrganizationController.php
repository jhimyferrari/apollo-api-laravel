<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string', ],
            'document' => [
                'required',
                'unique:organizations,document',
                'regex:/^(\d{11}|\d{14})$/',
            ],
            'email' => [
                'required',
                'email'],
            'password' => [
                'required',
                'min_digits:8',
            ],
        ]);

        // todo
        // Verificar com API

        $organization = Organization::create(
            [
                'name' => $validated['name'],
                'document' => $validated['document'],
            ]
        );

        $adminUser = User::create([
            'name' => 'Administrador',
            'email' => $validated['email'],
            'password' => $validated['password'],
            'organization_id' => $organization->id,
        ]);

        // todo
        // 1 - Create AdminUser with the email provided
        // 2 - Send Confirmating email for Admin
        // OBS: The Email provided here must to be the Admin Email
        // On login, the user need to informate the document of his organization

        return $this->success([
            'organization' => $organization,
            'admin_email' => $adminUser->email], 'Organization created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Organization $organization)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization)
    {
        //
    }
}
