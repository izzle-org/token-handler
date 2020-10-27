<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Model;

use Izzle\TokenHandler\Exceptions\UnserializeException;
use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;
use Serializable;

/**
 * Class Token
 * @package Izzle\TokenHandler\Model
 */
class Token implements TokenInterface, JsonSerializable, Serializable
{
    public const PROP_TOKEN = 'token';
    public const PROP_REFRESH_TOKEN = 'refreshToken';
    public const PROP_EXPIRES = 'expires';
    public const PROP_OWNER_ID = 'ownerId';

    /**
     * @var string
     */
    protected string $token;

    /**
     * @var string|null
     */
    protected ?string $refreshToken = null;

    /**
     * @var int
     */
    protected int $expires;

    /**
     * @var string
     */
    protected string $ownerId;

    /**
     * @inheritDoc
     */
    public function getToken(): string
    {
        return $this->{self::PROP_TOKEN};
    }

    /**
     * @inheritDoc
     */
    public function setToken(string $token): self
    {
        $this->{self::PROP_TOKEN} = $token;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRefreshToken(): string
    {
        return $this->{self::PROP_REFRESH_TOKEN};
    }

    /**
     * @inheritDoc
     */
    public function setRefreshToken(string $refreshToken): self
    {
        $this->{self::PROP_REFRESH_TOKEN} = $refreshToken;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getExpires(): int
    {
        return $this->{self::PROP_EXPIRES};
    }

    /**
     * @inheritDoc
     */
    public function setExpires(int $expires): self
    {
        $this->{self::PROP_EXPIRES} = $expires;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOwnerId(): string
    {
        return $this->{self::PROP_OWNER_ID};
    }

    /**
     * @inheritDoc
     */
    public function setOwnerId(string $ownerId): self
    {
        $this->{self::PROP_OWNER_ID} = $ownerId;

        return $this;
    }

    /**
     * Token constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->fromArray($data);
        }
    }

    /**
     * @param array $data
     * @return $this
     */
    public function fromArray(array $data): self
    {
        foreach ([self::PROP_TOKEN, self::PROP_EXPIRES, self::PROP_OWNER_ID] as $prop) {
            if (empty($data[$prop])) {
                throw new InvalidArgumentException('Required option not passed: ' . $prop);
            }

            $this->{$prop} = $data[$prop];
        }

        if (!empty($data[self::PROP_REFRESH_TOKEN])) {
            $this->{self::PROP_REFRESH_TOKEN} = $data[self::PROP_REFRESH_TOKEN];
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $data = [];
        foreach ([self::PROP_TOKEN, self::PROP_REFRESH_TOKEN, self::PROP_EXPIRES, self::PROP_OWNER_ID] as $prop) {
            $data[$prop] = $this->{$prop};
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * @inheritDoc
     * @throws UnserializeException
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized, [true]);
        if ($data === false) {
            throw new UnserializeException(sprintf('Could not unserialize data from %s', get_class($this)));
        }

        $this->fromArray($data);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @inheritdoc
     */
    public function hasExpired(): bool
    {
        $expires = $this->getExpires();

        if (empty($expires)) {
            throw new RuntimeException('"expires" is not set on the token');
        }

        return $expires < time();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getToken();
    }
}
