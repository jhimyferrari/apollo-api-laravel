<?php

namespace App\Http\Controllers\api\v1;

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
            new Middleware('abilities:client.create', only: ['store']),
            new Middleware('abilities:client.show', only: ['index', 'show']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return ClientResource::collection(Client::with('address')->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        $data = $this->clientService->create($request->validated());

        return $this->success($data, 'Client created succesfully.', 201);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        //
    }
}
