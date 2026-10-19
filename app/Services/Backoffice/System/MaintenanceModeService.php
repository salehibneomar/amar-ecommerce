<?php

namespace App\Services\Backoffice\System;

use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Support\Facades\Date;

class MaintenanceModeService implements MaintenanceMode
{
    public function __construct(
        private readonly SystemSettingService $systemSettingService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function activate(array $payload): void
    {
        $secret = $payload['secret'] ?? null;

        $this->systemSettingService->saveMaintenance(true, secret: is_string($secret) ? $secret : null);
    }

    public function deactivate(): void
    {
        $this->systemSettingService->saveMaintenance(false);
    }

    public function active(): bool
    {
        return $this->systemSettingService->isMaintenanceActive();
    }

    /**
     * @return array{secret: string|null, status: int, retry: int|null}
     */
    public function data(): array
    {
        $endsAt = $this->systemSettingService->maintenanceEndsAt();

        return [
            'secret' => $this->systemSettingService->maintenanceSecret(),
            'status' => 503,
            'retry' => $endsAt ? max(1, (int) ceil(Date::now()->diffInSeconds($endsAt))) : null,
        ];
    }
}
