<?php

namespace App\Services;

use App\Actions\Treatment\TreatDocument;
use App\Actions\Treatment\TreatEmail;
use App\Actions\Treatment\TreatName;
use App\Actions\Treatment\TreatPhone;
use App\Actions\Treatment\TreatStateRegistration;
use App\Actions\Validation\ValidateStatusEnum;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SellerService extends BaseService
{
    public function __construct(
        private TreatDocument $treatDocument,
        private TreatName $treatName,
        private TreatStateRegistration $treatStateRegistration,
        private TreatPhone $treatPhone,
        private TreatEmail $treatEmail
    ) {
        parent::__construct(new Seller);
    }

    public function create(array $data, User $user): Seller
    {

        if (isset($data['status'])) {
            app(ValidateStatusEnum::class)->execute($this->model, $data['status']);
        }

        $data['legal_name'] = $this->treatName->execute(
            $this->model,
            'legal_name',
            $data['legal_name'],
            mustBeNotNull: true,
            mustBeUnique: false);

        $data['trade_name'] = $this->treatName->execute(
            $this->model,
            'trade_name',
            $data['trade_name'],
            mustBeNotNull: true,
            mustBeUnique: false);

        $data['document'] = $this->treatDocument->execute($this->model, 'document', $data['document']);

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
            $data['phone'] = $this->treatPhone->execute($data['phone']);
        }
        $newSeller = new Seller($data);
        $newSeller->organization_id = $user->organization_id;
        $newSeller->save();

        return $newSeller;
    }

    /**
     * @param  Seller  $seller
     */
    public function update(Model $seller, array $data): Seller
    {
        if (isset($data['status'])) {
            app(ValidateStatusEnum::class)->execute($seller, $data['status']);
            $seller->status = $data['status'];
        }

        if (isset($data['document'])) {
            $seller->document = $this->treatDocument->execute($this->model, 'document', $data['document'], $seller->id);
        }

        if (isset($data['legal_name'])) {
            $seller->legal_name = $this->treatName->execute(
                $this->model,
                'legal_name',
                $data['legal_name'],
                mustBeNotNull: true,
                mustBeUnique: false);
        }

        if (isset($data['trade_name'])) {
            $seller->trade_name = $this->treatName->execute(
                $this->model,
                'trade_name',
                $data['trade_name'],
                mustBeNotNull: true,
                mustBeUnique: false
            );
        }

        if (isset($data['state_registration'])) {
            $seller->state_registration = $this->treatStateRegistration->execute(
                $this->model,
                'state_registration',
                $data['state_registration'],
                mustBeNotNull: false,
                mustBeUnique: true,
                ignoredId: $seller->id);
        }

        if (isset($data['phone'])) {
            $seller->phone = $this->treatPhone->execute($data['phone']);
        }

        if (isset($data['email'])) {
            $seller->email = $this->treatEmail->execute(
                $this->model,
                'email',
                $data['email'],
                mustBeNotNull: false,
                mustBeUnique: false);
        }

        $seller->save();

        return $seller;
    }

    public function delete(Model $seller): void
    {
        $seller->delete();
    }
}
