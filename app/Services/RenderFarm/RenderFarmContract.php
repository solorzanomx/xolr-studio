<?php

declare(strict_types=1);

namespace App\Services\RenderFarm;

use App\Models\Render;

interface RenderFarmContract
{
    /**
     * Submit a render job to the GPU farm.
     * Returns the remote job_id assigned by the provider.
     */
    public function submit(Render $render): string;

    /**
     * Poll the current status of a job.
     * Returns a normalized status: queued | processing | completed | failed
     */
    public function status(Render $render): RenderStatusResult;

    /**
     * Cancel a running job.
     */
    public function cancel(Render $render): bool;
}
