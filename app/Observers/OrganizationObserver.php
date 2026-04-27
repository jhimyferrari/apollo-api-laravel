<?php

namespace App\Observers;

use App\Actions\CreateOrganizationSequencialNumbers;
use App\Models\Organization;

class OrganizationObserver
{
    public function __construct(
        protected CreateOrganizationSequencialNumbers $createSequencialNumbers
    ) {}

    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $organization): void
    {
        $this->createSequencialNumbers->execute($organization);
    }

    /**
     * Handle the Organization "updated" event.
     */
    public function updated(Organization $organization): void
    {
        //
    }

    /**
     * Handle the Organization "deleted" event.
     */
    public function deleted(Organization $organization): void
    {
        //
    }

    /**
     * Handle the Organization "restored" event.
     */
    public function restored(Organization $organization): void
    {
        //
    }

    /**
     * Handle the Organization "force deleted" event.
     */
    public function forceDeleted(Organization $organization): void
    {
        //
    }
}
