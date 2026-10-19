<?php

namespace App\Enums\User;

enum SocialProviderEnum: string
{
    case Google = 'google';
    case Facebook = 'facebook';

    public function label(): string
    {
        return match ($this) {
            self::Google => __('Google'),
            self::Facebook => __('Facebook'),
        };
    }

    public function column(): string
    {
        return "{$this->value}_id";
    }

    public function isConfigured(): bool
    {
        return filled(config("services.{$this->value}.client_id"))
            && filled(config("services.{$this->value}.client_secret"));
    }
}
