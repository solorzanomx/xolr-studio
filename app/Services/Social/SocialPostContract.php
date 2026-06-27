<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Models\SocialPost;

interface SocialPostContract
{
    public function publish(SocialPost $post): string;
    public function delete(string $platformPostId): void;
    public function supportsType(string $postType): bool;
}
