<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Client\IndexClientRequest;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\ClientResource;
use App\Models\Address;
use App\Models\Client;
use App\Services\AddressService;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ClientController extends Controller implements HasMiddleware
{
    public function __construct(
        protected ClientService $clientService,
        protected AddressService $addressService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('abilities:'.PermissionType::CLIENT_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::CLIENT_READ->value, only: ['index', 'show', 'showAddresses']),
            new Middleware('abilities:'.PermissionType::CLIENT_UPDATE->value, only: ['update']),
            new Middleware(['abilities:'.PermissionType::CLIENT_DELETE->value], only: ['destroy']),
            new Middleware(['ability:'.PermissionType::CLIENT_CREATE->value.','.PermissionType::CLIENT_UPDATE->value], only: ['storeAddress', 'setDefaultAddress']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexClientRequest $request): AnonymousResourceCollection
    {
        $query = Client::query()->with('addresses');
        $request->applyPartnerIndex($query);

        return ClientResource::collection($query->paginate($request->perPage()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $newClient = $this->clientService->create($request->validated(), Auth()->user());

        return $this->success($newClient, __('messages.created', ['resource' => __('validation.resources.client')]), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client): ClientResource
    {
        return ClientResource::make($client);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client): Response
    {
        $this->clientService->update($client, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client): Response
    {
        $this->clientService->delete($client);

        return response()->noContent();
    }

    public function storeAddress(StoreAddressRequest $request, Client $client): JsonResponse
    {
        $newAddress = $this->addressService->create($client, $request->validated());

        return $this->success($newAddress, __('messages.created', ['resource' => __('validation.resources.address')]), 201);
    }

    public function setDefaultAddress(Client $client, Address $address): Response
    {
        $this->addressService->setDefault($client, $address);

        return response()->noContent();
    }

    public function showAddresses(Client $client): AnonymousResourceCollection
    {
        return AddressResource::collection($client->addresses()->with('city.uf')->paginate(5));
    }
}
