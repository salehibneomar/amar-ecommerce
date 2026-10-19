<?php

declare(strict_types=1);

namespace App\Resolvers\AiAssistant;

use App\Interfaces\AiAssistant\AiAssistantProviderInterface;
use App\Services\AiAssistant\AiAssistantCacheService;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class AiAssistantProviderResolver
{
    public function __construct(private readonly Container $container) {}

    public function resolve(bool $cached = true): AiAssistantProviderInterface
    {
        $provider = aiAssistantProvider();
        $class = aiAssistantProviderConfig($provider, 'class');
        $instance = $class ? $this->container->make($class) : null;

        if (! $instance instanceof AiAssistantProviderInterface) {
            throw new InvalidArgumentException("No assistant provider is configured for [{$provider}].");
        }

        if (! $cached || aiAssistantCacheMinutes() === 0) {
            return $instance;
        }

        return new AiAssistantCacheService($instance, implode('|', [
            $provider,
            aiAssistantProviderConfig($provider, 'model'),
            aiAssistantProviderConfig($provider, 'embedding_model'),
            aiAssistantProviderConfig($provider, 'embedding_dimensions'),
        ]));
    }
}
