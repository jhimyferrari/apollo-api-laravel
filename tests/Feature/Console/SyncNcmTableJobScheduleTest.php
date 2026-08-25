<?php

use App\Jobs\SyncNcmTableJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;

describe('SyncNcmTableJob Schedule', function () {
    it('dispatches SyncNcmTableJob when the daily schedule window hits', function () {

        $this->travelTo(now()->addDay()->startOfDay());
        Bus::fake();

        Artisan::call('schedule:run');

        Bus::assertDispatched(SyncNcmTableJob::class);
    });

    it('does not dispatch SyncNcmTableJob outside the scheduled window', function () {
        Bus::fake();

        $this->travelTo(now()->startOfDay()->addHours(12));

        Artisan::call('schedule:run');

        Bus::assertNotDispatched(SyncNcmTableJob::class);
    });

    it('dispatches SyncNcmTableJob again on the following day', function () {
        Bus::fake();

        $this->travelTo(now()->startOfDay());
        Artisan::call('schedule:run');
        Bus::assertDispatchedTimes(SyncNcmTableJob::class, 1);

        $this->travelTo(now()->addDay()->startOfDay());
        Artisan::call('schedule:run');
        Bus::assertDispatchedTimes(SyncNcmTableJob::class, 2);
    });

});
