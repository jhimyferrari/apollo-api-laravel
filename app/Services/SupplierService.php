<?php

namespace App\Services;

use App\Actions\ValidateDuplicateField;
use App\Helpers\DocumentHelper;
use App\Models\Supplier;
use App\Models\User;

class SupplierService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new Supplier);
    }

    public function create(array $data, User $user): Supplier
    {

        $formatedDocument = DocumentHelper::remove_pontuation($data['document']);
        app(ValidateDuplicateField::class)->execute(new Supplier, 'document', $formatedDocument, $user->organization_id);
        $data['document'] = $formatedDocument;

        $formatedStateRegistration = DocumentHelper::remove_pontuation($data['state_registration']);
        app(ValidateDuplicateField::class)->execute(new Supplier, 'state_registration', $formatedStateRegistration, $user->organization_id);
        $data['state_registration'] = $formatedStateRegistration;

        $newSupplier = new Supplier($data);
        $newSupplier->organization_id = $user->organization_id;
        $newSupplier->save();

        return $newSupplier;
    }
}
