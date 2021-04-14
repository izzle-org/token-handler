<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Handler;

use Izzle\TokenHandler\Model\TokenInterface;

/**
 * Interface HandlerInterface
 * @package Izzle\TokenHandler\Handler
 */
interface HandlerInterface
{
    /**
     * @param string|null $ownerId
     * @return TokenInterface|null
     */
    public function loadToken(?string $ownerId): ?TokenInterface;

    /**
     * @param string[] $ownerIds
     * @return array
     */
    public function loadTokens(array $ownerIds): array;

    /**
     * @param int $seconds - Expires in
     * @return array
     */
    public function loadTokensThatExpiresIn(int $seconds): array;

    /**
     * @param TokenInterface $token
     * @param string|null $ownerId
     * @return bool
     */
    public function saveToken(TokenInterface $token, ?string $ownerId): bool;

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function removeToken(?string $ownerId): bool;

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function lockToken(?string $ownerId): bool;

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function unlockToken(?string $ownerId): bool;

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function isLocked(?string $ownerId): bool;
}
