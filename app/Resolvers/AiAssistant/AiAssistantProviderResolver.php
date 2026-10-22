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

    public function chat(bool $cached = true): AiAssistantProviderInterface
    {
        return $this->resolve('chat', aiAssistantChatConfig(...), $cached);
    }

    public function embedding(bool $cached = true): AiAssistantProviderInterface
    {
        return $this->resolve('embedding', aiAssistantEmbeddingConfig(...), $cached);
    }

    /**
     * @param  callable(string): mixed  $config
     */
    private function resolve(string $job, callable $config, bool $cached): AiAssistantProviderInterface
    {
        $driver = (string) $config('driver');
        $class = aiAssistantDriverClass($driver);
        $instance = $class ? $this->container->make($class) : null;

        if (! $instance instanceof AiAssistantProviderInterface) {
            throw new InvalidArgumentException("No assistant driver is configured for [{$driver}].");
        }

        if (! $cached || aiAssistantCacheMinutes() === 0) {
            return $instance;
        }

        return new AiAssistantCacheService($instance, implode('|', [
            $job,
            $driver,
            $config('url'),
            $config('model'),
            $config('dimensions'),
        ]));
    }
}
