<?php

namespace App\Services;

use App\Actions\Treatment\TreatDocument;
use App\Actions\Treatment\TreatEmail;
use App\Actions\Treatment\TreatName;
use App\Actions\Treatment\TreatPhone;
use App\Actions\Treatment\TreatStateRegistration;
use App\Actions\Validation\ValidateStatusEnum;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ClientService extends BaseService
{
    public function __construct(
        private TreatDocument $treatDocument,
        private TreatName $treatName,
        private TreatStateRegistration $treatStateRegistration,
        private TreatPhone $treatPhone,
        private TreatEmail $treatEmail)
    {
        parent::__construct(new Client);
    }

    public function create(array $data, User $user): Client
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
        $newClient = new Client($data);
        $newClient->organization_id = $user->organization_id;
        $newClient->save();

        return $newClient;
    }

    /**
     * @param  Client  $client
     */
    public function update(Model $client, array $data): Client
    {

        if (isset($data['status'])) {
            app(ValidateStatusEnum::class)->execute($client, $data['status']);
            $client->status = $data['status'];
        }

        if (isset($data['document'])) {
            $client->document = $this->treatDocument->execute($this->model, 'document', $data['document'], $client->id);
        }

        if (isset($data['legal_name'])) {
            $client->legal_name = $this->treatName->execute(
                $this->model,
                'legal_name',
                $data['legal_name'],
                mustBeNotNull: true,
                mustBeUnique: false);
        }

        if (isset($data['trade_name'])) {
            $client->trade_name = $this->treatName->execute(
                $this->model,
                'trade_name',
                $data['trade_name'],
                mustBeNotNull: true,
                mustBeUnique: false
            );
        }

        if (isset($data['state_registration'])) {

            $client->state_registration = $this->treatStateRegistration->execute(
                $this->model,
                'state_registration',
                $data['state_registration'],
                mustBeNotNull: false,
                mustBeUnique: true,
                ignoredId: $client->id);
        }

        if (isset($data['phone'])) {
            $client->phone = $this->treatPhone->execute($data['phone']);
        }

        if (isset($data['email'])) {
            $client->email = $this->treatEmail->execute(
                $this->model,
                'email',
                $data['email'],
                mustBeNotNull: false,
                mustBeUnique: false);
        }

        $client->save();

        return $client;

    }
}
