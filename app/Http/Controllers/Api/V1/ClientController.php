<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ClientController extends Controller implements HasMiddleware
{
    public function __construct(
        protected ClientService $clientService
    ) {}

    public static function middleware()
    {
        return [
            new Middleware('abilities:'.PermissionType::CLIENT_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::CLIENT_READ->value, only: ['index', 'show']),
            new Middleware('abilities:'.PermissionType::CLIENT_UPDATE->value, only: ['update']),
            new Middleware('abilities:'.PermissionType::CLIENT_DELETE->value, only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return ClientResource::collection(Client::with('addresses')->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        $newClient = $this->clientService->createWithOrganization($request->validated(), Auth()->user());

        return $this->success($newClient, 'Client created succesfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        return ClientResource::make($client);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        $this->clientService->update($client, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $this->clientService->delete($client);

        return response()->noContent();
    }
}
