<?php

namespace App\Services;

use App\Actions\ValidateDuplicateField;
use App\Helpers\DocumentHelper;
use App\Models\Client;
use App\Models\User;

class ClientService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new Client);
    }

    public function create(array $data, User $user): Client
    {
        $formatedDocument = DocumentHelper::remove_pontuation($data['document']);
        app(ValidateDuplicateField::class)->execute(new Client, 'document', $formatedDocument, $user->organization_id);
        $data['document'] = $formatedDocument;

        $formatedStateRegistration = DocumentHelper::remove_pontuation($data['state_registration']);
        app(ValidateDuplicateField::class)->execute(new Client, 'state_registration', $formatedStateRegistration, $user->organization_id);
        $data['state_registration'] = $formatedStateRegistration;

        $newClient = new Client($data);
        $newClient->organization_id = $user->organization_id;
        $newClient->save();

        return $newClient;
    }
}
