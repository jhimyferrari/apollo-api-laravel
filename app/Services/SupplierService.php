<?php

namespace App\Services;

use App\Actions\Treatment\TreatAddress;
use App\Actions\Treatment\TreatDocument;
use App\Actions\Treatment\TreatEmail;
use App\Actions\Treatment\TreatName;
use App\Actions\Treatment\TreatPhone;
use App\Actions\Treatment\TreatStateRegistration;
use App\Actions\Validation\ValidateStatusEnum;
use App\Interfaces\Services\AddressableService;
use App\Models\Supplier;
use App\Models\User;
use App\Traits\Service\HandlesAddress;
use Illuminate\Database\Eloquent\Model;

class SupplierService extends BaseService implements AddressableService
{
    use HandlesAddress;

    public function __construct(
        private TreatDocument $treatDocument,
        private TreatStateRegistration $treatStateRegistration,
        private TreatName $treatName,
        private TreatPhone $treatPhone,
        private TreatEmail $treatEmail,
        private TreatAddress $treatAddress
    ) {
        parent::__construct(new Supplier);
    }

    public function create(array $data, User $user): Supplier
    {

        $data['trade_name'] = $this->treatName->execute(
            $this->model,
            'trade_name',
            $data['trade_name'],
            mustBeNotNull: true,
            mustBeUnique: false
        );

        $data['legal_name'] = $this->treatName->execute(
            $this->model,
            'legal_name',
            $data['legal_name'],
            mustBeNotNull: true,
            mustBeUnique: false
        );
        $data['document'] = $this->treatDocument->execute(
            $this->model,
            'document',
            $data['document'],
        );

        if (isset($data['state_registration'])) {
            $data['state_registration'] = $this->treatStateRegistration->execute(
                $this->model,
                'state_registration',
                $data['state_registration'],
                mustBeNotNull: false,
                mustBeUnique: true
            );
        }

        if (isset($data['email'])) {
            $data['email'] = $this->treatEmail->execute(
                $this->model,
                'email',
                $data['email'],
                mustBeNotNull: false,
                mustBeUnique: false
            );
        }
        if (isset($data['phone'])) {
            $data['phone'] = $this->treatPhone->execute(
                $data['phone']
            );
        }
        $newSupplier = new Supplier($data);
        $newSupplier->organization_id = $user->organization_id;
        $newSupplier->save();

        return $newSupplier;
    }

    /**
     * @param  Supplier  $supplier
     */
    public function update(Model $supplier, array $data): Supplier
    {
        if (isset($data['status'])) {
            app(ValidateStatusEnum::class)->execute($this->model, $data['status']);
            $supplier->status = $data['status'];
        }
        if (isset($data['trade_name'])) {
            $supplier->trade_name = $this->treatName->execute(
                $this->model,
                'trade_name',
                $data['trade_name'],
                mustBeNotNull: true,
                mustBeUnique: false,
            );
        }

        if (isset($data['legal_name'])) {
            $supplier->legal_name = $this->treatName->execute(
                $this->model,
                'legal_name',
                $data['legal_name'],
                mustBeNotNull: true,
                mustBeUnique: false,
            );
        }
        if (isset($data['document'])) {
            $supplier->document = $this->treatDocument->execute(
                $this->model,
                'document',
                $data['document'],
                ignoredId: $supplier->id
            );
        }
        if (isset($data['state_registration'])) {
            $supplier->state_registration = $this->treatStateRegistration->execute(
                $this->model,
                'state_registration',
                $data['state_registration'],
                mustBeNotNull: false,
                mustBeUnique: true,
                ignoredId: $supplier->id
            );
        }

        if (isset($data['email'])) {
            $supplier->email = $this->treatEmail->execute(
                $this->model,
                'email',
                $data['email'],
                mustBeNotNull: false,
                mustBeUnique: false
            );
        }
        if (isset($data['phone'])) {
            $supplier->phone = $this->treatPhone->execute(
                $data['phone']
            );
        }
        $supplier->save();

        return $supplier;
    }

    public function delete(Model $supplier): void
    {
        $supplier->delete();
    }
}
