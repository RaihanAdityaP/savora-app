<?php

namespace App\Services;

use Throwable;

class UserSettingsService
{
    public function __construct(private SupabaseService $supabase) {}

    public function defaults(): array
    {
        return [
            'theme'             => 'light',
            'language'          => 'en',
            'font_size'         => 14,
            'notify_likes'      => true,
            'notify_comments'   => true,
            'notify_follows'    => true,
            'allow_analytics'   => true,
            'profile_public'    => true,
            'auto_save_drafts'  => true,
        ];
    }

    public function get(?string $userId): array
    {
        $defaults = $this->defaults();

        if (! $userId) {
            return $defaults;
        }

        try {
            $rows = $this->supabase->select('user_settings', ['*'], ['user_id' => $userId]);
            return ! empty($rows) ? array_merge($defaults, $rows[0]) : $defaults;
        } catch (Throwable) {
            return $defaults;
        }
    }

    public function enabled(?string $userId, string $key): bool
    {
        $settings = $this->get($userId);
        return (bool) ($settings[$key] ?? $this->defaults()[$key] ?? false);
    }

    public function notificationEnabledForType(?string $userId, string $type): bool
    {
        $key = $this->notificationPreferenceKey($type);

        if ($key === null) {
            return true;
        }

        return $this->enabled($userId, $key);
    }

    public function notificationPreferenceKey(string $type): ?string
    {
        return match ($type) {
            'new_like'     => 'notify_likes',
            'new_comment'  => 'notify_comments',
            'new_follower' => 'notify_follows',
            default        => null,
        };
    }
}
