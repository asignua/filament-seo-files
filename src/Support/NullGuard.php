<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;

/**
 * A guard nobody is ever logged in to. {@see PublicContext} makes it the default guard while
 * a public file is built: hiding the user of a `SessionGuard` is not enough, because it reads
 * the login id from the session again on the next `user()` call.
 */
final class NullGuard implements Guard
{
    public function check(): bool
    {
        return false;
    }

    public function guest(): bool
    {
        return true;
    }

    public function user(): ?Authenticatable
    {
        return null;
    }

    public function id(): int|string|null
    {
        return null;
    }

    /**
     * @param array<string, mixed> $credentials
     */
    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function hasUser(): bool
    {
        return false;
    }

    public function setUser(Authenticatable $user): static
    {
        return $this;
    }
}
