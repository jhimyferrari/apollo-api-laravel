<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\PurchaseReportSummaryRequest;
use App\Http\Resources\Reports\PurchaseSummaryReportResource;
use App\Services\Reports\PurchaseReportService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PurchaseReportController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PurchaseReportService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('abilities:'.PermissionType::PURCHASE_ORDER_READ->value),
        ];
    }

    /**
     * Display summary purchase report.
     */
    public function summary(PurchaseReportSummaryRequest $request): PurchaseSummaryReportResource
    {
        $result = $this->service->summary($request->validated());

        return PurchaseSummaryReportResource::make($result);
    }
}
