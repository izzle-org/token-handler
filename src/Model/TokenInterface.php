<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Model;

/**
 * Interface TokenInterface
 * @package Izzle\TokenHandler\Model
 */
interface TokenInterface
{
    /**
     * @return string
     */
    public function getToken(): string;

    /**
     * @param string $token
     * @return $this
     */
    public function setToken(string $token): self;

    /**
     * @return int
     */
    public function getExpires(): int;

    /**
     * @param int $expires
     * @return $this
     */
    public function setExpires(int $expires): self;

    /**
     * @return string
     */
    public function getOwnerId(): string;

    /**
     * @param string $ownerId
     * @return $this
     */
    public function setOwnerId(string $ownerId): self;

    /**
     * @return bool
     */
    public function hasExpired(): bool;

    /**
     * @return string
     */
    public function __toString();
}
