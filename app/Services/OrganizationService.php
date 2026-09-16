<?php

namespace App\Services;

use App\Models\Organization;
use App\Services\TreatmentService\Strategies\TreatDocument;
use App\Services\TreatmentService\Strategies\TreatRegularString;
use App\Services\TreatmentService\TreatmentService;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly TreatmentService $treatment,
        private readonly TreatDocument $treatDocument,
        private readonly TreatRegularString $treatString
    ) {}

    public function create(array $data): array
    {

        $formatedDocument = $this->treatment->for($this->treatDocument, $data['document'], 'document', new Organization)
            ->mustBeNotNull()->mustBeUnique()->handle();
        $formatedName = $this->treatment->for($this->treatString, $data['name'], 'name', new Organization)->mustBeNotNull()->handle();

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
