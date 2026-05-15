<?php

namespace App\Enum;

enum PermissionType: string
{
    case USER_CREATE = 'user.create';
    case USER_READ = 'user.view';
    case USER_UPDATE = 'user.update';
    case USER_DELETE = 'user.delete';

    case CLIENT_CREATE = 'client.create';
    case CLIENT_READ = 'client.view';
    case CLIENT_UPDATE = 'client.update';
    case CLIENT_DELETE = 'client.delete';

    case SELLER_CREATE = 'seller.create';
    case SELLER_READ = 'seller.view';
    case SELLER_UPDATE = 'seller.update';
    case SELLER_DELETE = 'seller.delete';

    case SUPPLIER_CREATE = 'supplier.create';
    case SUPPLIER_READ = 'supplier.view';
    case SUPPLIER_UPDATE = 'supplier.update';
    case SUPPLIER_DELETE = 'supplier.delete';

    case BRAND_CREATE = 'brand.create';
    case BRAND_READ = 'brand.view';
    case BRAND_UPDATE = 'brand.update';
    case BRAND_DELETE = 'brand.delete';

    case CATEGORY_CREATE = 'category.create';
    case CATEGORY_READ = 'category.view';
    case CATEGORY_UPDATE = 'category.update';
    case CATEGORY_DELETE = 'category.delete';

    public static function byModel(string $model): array
    {
        return array_filter(
            self::cases(),
            fn (self $case) => str_starts_with($case->value, $model.'.')
        );
    }
}
