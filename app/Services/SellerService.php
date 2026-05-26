<?php

namespace App\Services;

use App\Actions\ValidateDuplicateField;
use App\Helpers\DocumentHelper;
use App\Models\Seller;
use App\Models\User;

class SellerService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new Seller);
    }

    public function create(array $data, User $user): Seller
    {

        $formatedDocument = DocumentHelper::remove_pontuation($data['document']);
        app(ValidateDuplicateField::class)->execute(new Seller, 'document', $formatedDocument, $user->organization_id);
        $data['document'] = $formatedDocument;

        $formatedStateRegistration = DocumentHelper::remove_pontuation($data['state_registration']);
        app(ValidateDuplicateField::class)->execute(new Seller, 'state_registration', $formatedStateRegistration, $user->organization_id);
        $data['state_registration'] = $formatedStateRegistration;

        $newSeller = new Seller($data);
        $newSeller->organization_id = $user->organization_id;
        $newSeller->save();

        return $newSeller;
    }
}
