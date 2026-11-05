<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\SalesReportByProductRequest;
use App\Http\Requests\Report\SalesReportBySellerRequest;
use App\Http\Requests\Report\SalesReportSummaryRequest;
use App\Http\Resources\Reports\SalesByProductReportResource;
use App\Http\Resources\Reports\SalesBySellerReportResource;
use App\Http\Resources\Reports\SalesSummaryReportResource;
use App\Services\Reports\SalesReportService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SalesReportController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SalesReportService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('abilities:'.PermissionType::SALES_ORDER_READ->value),
        ];
    }

    /**
     * Display summary sales report.
     */
    public function summary(SalesReportSummaryRequest $request): SalesSummaryReportResource
    {
        $result = $this->service->summary($request->validated());

        return SalesSummaryReportResource::make($result);
    }

    /**
     * Display sales report grouped by product.
     */
    public function byProduct(SalesReportByProductRequest $request): SalesByProductReportResource
    {
        $result = $this->service->getByProduct($request->validated());

        return SalesByProductReportResource::make($result);
    }

    /**
     * Display sales report grouped by seller.
     */
    public function bySeller(SalesReportBySellerRequest $request): SalesBySellerReportResource
    {
        $result = $this->service->getBySeller($request->validated());

        return SalesBySellerReportResource::make($result);
    }
}
