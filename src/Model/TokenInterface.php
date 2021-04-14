<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Model;

use JsonSerializable;
use Serializable;

/**
 * Interface TokenInterface
 * @package Izzle\TokenHandler\Model
 */
interface TokenInterface extends JsonSerializable, Serializable
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
     * @return string|null
     */
    public function getRefreshToken(): ?string;

    /**
     * @param string $refreshToken
     * @return $this
     */
    public function setRefreshToken(string $refreshToken): self;

    /**
     * @return int|null
     */
    public function getExpires(): ?int;

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
     * Returns additional vendor values stored in the token.
     *
     * @return array
     */
    public function getValues(): array;
}
