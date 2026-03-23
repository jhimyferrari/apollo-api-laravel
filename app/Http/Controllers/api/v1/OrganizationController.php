<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationRequest $request)
    {
        $validated = $request->validated();

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
            'is_administrator' => true,
            'organization_id' => $organization->id,
        ]);

        $permissions = Permission::all();

        $adminUser->permissions()->sync($permissions);

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
