<?php

namespace App\Services;

use App\Actions\Validation\ValidateStatusEnum;
use App\Models\Seller;
use App\Models\User;
use App\Services\TreatmentService\Strategies\TreatDocument;
use App\Services\TreatmentService\Strategies\TreatEmail;
use App\Services\TreatmentService\Strategies\TreatPhone;
use App\Services\TreatmentService\Strategies\TreatRegularString;
use App\Services\TreatmentService\Strategies\TreatStateRegistration;
use App\Services\TreatmentService\TreatmentService;
use DB;
use Illuminate\Database\Eloquent\Model;

class SellerService extends BaseService
{
    public function __construct(

        private readonly TreatmentService $treament,
        private readonly TreatRegularString $treatString,
        private readonly TreatStateRegistration $treatStateRegistration,
        private readonly TreatPhone $treatPhone,
        private readonly TreatEmail $treatEmail,
        private readonly TreatDocument $treatDocument,
        private readonly ValidateStatusEnum $validateStatusEnum,
    ) {
        parent::__construct(new Seller);
    }

    public function create(array $data, User $user): Seller
    {

        if (isset($data['status'])) {
            $this->validateStatusEnum->execute($this->model, $data['status']);
        }

        $data['legal_name'] = $this->treament->for($this->treatString, $data['legal_name'], 'legal_name', $this->model)->mustBeNotNull()->handle();

        $data['trade_name'] = $this->treament->for($this->treatString, $data['trade_name'], 'trade_name', $this->model)->mustBeNotNull()->handle();

        $data['document'] = $this->treament->for($this->treatDocument, $data['document'], 'document', $this->model)->mustBeNotNull()->mustBeUnique()->handle();

        if (isset($data['state_registration'])) {
            $data['state_registration'] = $this->treament->for($this->treatStateRegistration, $data['state_registration'], 'state_registration', $this->model)->mustBeUnique()->handle();
        }

        if (isset($data['email'])) {
            $data['email'] = $this->treament->for($this->treatEmail, $data['email'], 'email', $this->model)->handle();
        }

        if (isset($data['phone'])) {
            $data['phone'] = $this->treament->for($this->treatPhone, $data['phone'], 'phone', $this->model)->handle();
        }

        $newSeller = new Seller($data);
        $newSeller->organization_id = $user->organization_id;

        DB::transaction(function () use ($newSeller) {
            $newSeller->save();
        });

        return $newSeller;
    }

    /**
     * @param  Seller  $seller
     */
    public function update(Model $seller, array $data): Seller
    {

        if (\array_key_exists('status', $data)) {

            $this->validateStatusEnum->execute($this->model, $data['status']);
            $seller->status = $data['status'];
        }

        if (\array_key_exists('legal_name', $data)) {

            $seller->legal_name = $this->treament->for($this->treatString, $data['legal_name'], 'legal_name', $this->model)->mustBeNotNull()->handle();
        }

        if (\array_key_exists('trade_name', $data)) {

            $seller->trade_name = $this->treament->for($this->treatString, $data['trade_name'], 'trade_name', $this->model)->mustBeNotNull()->handle();
        }

        if (\array_key_exists('document', $data)) {
            $seller->document = $this->treament->for($this->treatDocument, $data['document'], 'document', $this->model)->mustBeNotNull()->mustBeUnique()->ignoredId($seller->id)->handle();
        }

        if (\array_key_exists('state_registration', $data)) {

            $seller->state_registration = $this->treament->for($this->treatStateRegistration, $data['state_registration'], 'state_registration', $this->model)->mustBeUnique()->ignoredId($seller->id)->handle();
        }

        if (\array_key_exists('phone', $data)) {
            $seller->phone = $this->treament->for($this->treatPhone, $data['phone'], 'phone', $this->model)->handle();
        }

        if (\array_key_exists('email', $data)) {

            $seller->email = $this->treament->for($this->treatEmail, $data['email'], 'email', $this->model)->handle();
        }

        DB::transaction(function () use ($seller) {
            $seller->save();
        });

        return $seller;
    }

    /**
     * @param  Seller  $seller
     */
    public function delete(Model $seller): void
    {
        DB::transaction(function () use ($seller) {
            $seller->addresses()->delete();
            $seller->delete();
        });
    }
}
