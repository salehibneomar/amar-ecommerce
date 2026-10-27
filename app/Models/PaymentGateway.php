<?php

namespace App\Models;

use App\Enums\Payment\PaymentGatewayEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Traits\Cache\ClearsFrontendCacheTrait;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentGatewayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property PaymentGatewayEnum $gateway
 * @property bool $is_enabled
 * @property int $sort_order
 * @property array<string, string|null>|null $settings
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class PaymentGateway extends Model
{
    /** @use HasFactory<PaymentGatewayFactory> */
    use ClearsFrontendCacheTrait, HasFactory;

    protected $fillable = [
        'gateway',
        'is_enabled',
        'sort_order',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gateway' => PaymentGatewayEnum::class,
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    /**
     * @return list<FrontendCacheKeyEnum>
     */
    public function frontendCacheKeys(): array
    {
        return [FrontendCacheKeyEnum::PaymentGateways];
    }
}
