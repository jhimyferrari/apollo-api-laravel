<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Interfaces\Models\Addressable;
use App\Models\Address;
use App\Models\Client;
use App\Models\Seller;
use App\Models\Supplier;
use App\Services\AddressService;
use Illuminate\Support\Facades\Auth;

/**
 * Because of this model's polymorphic relationship, authorization
 * middleware couldn't be applied statically on the routes (e.g.
 * `abilities:client.update`), since the required permission depends on
 * the concrete type of the `addressable` model (Client, Supplier, Seller,
 * etc.).
 *
 * For this reason, permission checking was implemented dynamically
 * through the internal method {@see self::verifyPermission()}, which
 * resolves the correct ability at runtime based on the `addressable`
 * class and the operation being performed (update, destroy, show).
 */
class AddressController extends Controller
{
    public function __construct(
        private readonly AddressService $service,
    ) {}

    /**
     * Display the specified resource.
     */
    public function show(Address $address)
    {

        $this->verifyPermission($address->addressable, __FUNCTION__);

        return AddressResource::make($address);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAddressRequest $request, Address $address)
    {

        $this->verifyPermission($address->addressable, __FUNCTION__);
        $this->service->update($address, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Address $address)
    {
        $this->verifyPermission($address->addressable, __FUNCTION__);
        $this->service->delete($address);

        return response()->noContent();
    }

    private function verifyPermission(Addressable $addressable, string $operation): void
    {
        $permissionMap = [
            Client::class => [
                'update' => PermissionType::CLIENT_UPDATE,
                'destroy' => PermissionType::CLIENT_DELETE,
                'show' => PermissionType::CLIENT_READ,
            ],
            Supplier::class => [
                'update' => PermissionType::SUPPLIER_UPDATE,
                'destroy' => PermissionType::SUPPLIER_DELETE,
                'show' => PermissionType::SUPPLIER_READ,
            ],
            Seller::class => [
                'update' => PermissionType::SELLER_UPDATE,
                'destroy' => PermissionType::SELLER_DELETE,
                'show' => PermissionType::SELLER_READ,
            ],
        ];

        $addressableClass = $addressable::class;

        if (! isset($permissionMap[$addressableClass][$operation])) {
            abort(404);
        }

        $permission = $permissionMap[$addressableClass][$operation];

        if (! Auth::user()->tokenCan($permission->value)) {
            abort(404);
        }
    }
}
