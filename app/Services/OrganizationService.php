<?php

namespace App\Services;

use App\Actions\Validation\ValidateDuplicateField;
use App\Helpers\DocumentHelper;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    public function __construct(
        private UserService $userService
    ) {}

    public function create(array $data): array
    {
        $formatedDocument = DocumentHelper::remove_pontuation($data['document']);
        $formatedName = trim($data['name']);
        app(ValidateDuplicateField::class)->execute(new Organization, 'document', $formatedDocument);

        return DB::transaction(function () use ($data, $formatedDocument, $formatedName) {
            $organization = Organization::create([
                'name' => $formatedName,
                'document' => $formatedDocument,
            ]);

            $adminUser = $this->userService->createAdmin(
                [
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'organization_id' => $organization->id,
                ]
            );

            return [$organization, $adminUser];
        });
    }
}
