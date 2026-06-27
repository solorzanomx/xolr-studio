<?php

declare(strict_types=1);

namespace App\Services\LipSync;

use App\Models\TalkingRender;

interface LipSyncContract
{
    /**
     * Envía un job de lip sync al proveedor.
     * Devuelve el service_job_id asignado.
     */
    public function submit(TalkingRender $render): string;

    /**
     * Consulta el estado del job.
     */
    public function status(TalkingRender $render): LipSyncStatusResult;

    /**
     * Cancela el job si está en progreso.
     */
    public function cancel(TalkingRender $render): bool;

    /**
     * Costo estimado antes de enviar (para mostrar en UI).
     */
    public function estimateCost(string $quality, float $durationSeconds): float;
}
