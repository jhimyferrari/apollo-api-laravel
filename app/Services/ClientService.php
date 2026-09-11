<?php

namespace App\Services;

use App\Actions\Validation\ValidateStatusEnum;
use App\Models\Client;
use App\Models\User;
use App\Services\TreatmentService\Strategies\TreatDocument;
use App\Services\TreatmentService\Strategies\TreatEmail;
use App\Services\TreatmentService\Strategies\TreatPhone;
use App\Services\TreatmentService\Strategies\TreatRegularString;
use App\Services\TreatmentService\Strategies\TreatStateRegistration;
use App\Services\TreatmentService\TreatmentService;
use DB;
use Illuminate\Database\Eloquent\Model;

class ClientService extends BaseService
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
        parent::__construct(new Client);
    }

    public function create(array $data, User $user): Client
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

        $newClient = new Client($data);
        $newClient->organization_id = $user->organization_id;
        DB::transaction(function () use ($newClient) {
            $newClient->save();
        });

        return $newClient;
    }

    /**
     * @param  Client  $client
     */
    public function update(Model $client, array $data): Client
    {
        if (\array_key_exists('status', $data)) {
            $this->validateStatusEnum->execute($this->model, $data['status']);
            $client->status = $data['status'];
        }

        if (\array_key_exists('legal_name', $data)) {

            $client->legal_name = $this->treament->for($this->treatString, $data['legal_name'], 'legal_name', $this->model)->mustBeNotNull()->handle();
        }

        if (\array_key_exists('trade_name', $data)) {

            $client->trade_name = $this->treament->for($this->treatString, $data['trade_name'], 'trade_name', $this->model)->mustBeNotNull()->handle();
        }

        if (\array_key_exists('document', $data)) {
            $client->document = $this->treament->for($this->treatDocument, $data['document'], 'document', $this->model)->mustBeNotNull()->mustBeUnique()->ignoredId($client->id)->handle();
        }

        if (\array_key_exists('state_registration', $data)) {

            $client->state_registration = $this->treament->for($this->treatStateRegistration, $data['state_registration'], 'state_registration', $this->model)->mustBeUnique()->ignoredId($client->id)->handle();
        }

        if (\array_key_exists('phone', $data)) {
            $client->phone = $this->treament->for($this->treatPhone, $data['phone'], 'phone', $this->model)->handle();
        }

        if (\array_key_exists('email', $data)) {

            $client->email = $this->treament->for($this->treatEmail, $data['email'], 'email', $this->model)->handle();
        }

        DB::transaction(function () use ($client) {
            $client->save();
        });

        return $client;

    }

    public function delete(Model $client): void
    {
        DB::transaction(function () use ($client) {
            $client->addresses()->delete();
            $client->delete();
        });
    }
}
