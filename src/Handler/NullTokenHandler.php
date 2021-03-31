<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Handler;

use Izzle\TokenHandler\Model\TokenInterface;

class NullTokenHandler implements HandlerInterface
{
    /**
     * @param string|null $ownerId
     * @return TokenInterface|null
     */
    public function loadToken(?string $ownerId): ?TokenInterface
    {
        return null;
    }

    /**
     * @param string[] $ownerIds
     * @return array
     */
    public function loadTokens(array $ownerIds): array
    {
        return [];
    }

    /**
     * @param int $seconds - Expires in
     * @return array
     */
    public function loadTokensThatExpiresIn(int $seconds): array
    {
        return [];
    }

    /**
     * @param TokenInterface $token
     * @param string|null $ownerId
     * @return bool
     */
    public function saveToken(TokenInterface $token, ?string $ownerId): bool
    {
        return true;
    }

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function removeToken(?string $ownerId): bool
    {
        return true;
    }

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function lockToken(?string $ownerId): bool
    {
        return true;
    }

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function unlockToken(?string $ownerId): bool
    {
        return true;
    }

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function isLocked(?string $ownerId): bool
    {
        return false;
    }
}
