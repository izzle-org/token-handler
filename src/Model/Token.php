<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Model;

use Izzle\TokenHandler\Exceptions\UnserializeException;
use InvalidArgumentException;

/**
 * Class Token
 * @package Izzle\TokenHandler\Model
 */
class Token implements TokenInterface
{
    public const PROP_TOKEN = 'token';
    public const PROP_REFRESH_TOKEN = 'refreshToken';
    public const PROP_EXPIRES = 'expires';
    public const PROP_OWNER_ID = 'ownerId';
    public const PROP_META = 'meta';

    /**
     * @var string
     */
    protected string $token;

    /**
     * @var string|null
     */
    protected ?string $refreshToken = null;

    /**
     * @var int|null
     */
    protected ?int $expires = null;

    /**
     * @var string
     */
    protected string $ownerId;

    /**
     * @var array
     */
    protected array $meta = [];

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
    public function getRefreshToken(): ?string
    {
        return $this->{self::PROP_REFRESH_TOKEN};
    }

    /**
     * @inheritDoc
     */
    public function setRefreshToken(?string $refreshToken): self
    {
        $this->{self::PROP_REFRESH_TOKEN} = $refreshToken;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getExpires(): ?int
    {
        return $this->{self::PROP_EXPIRES};
    }

    /**
     * @inheritDoc
     */
    public function setExpires(?int $expires): self
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
        if (empty($ownerId)) {
            throw new InvalidArgumentException('Required ownerId cannot be empty!');
        }

        $this->{self::PROP_OWNER_ID} = $ownerId;

        return $this;
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        return $this->{self::PROP_META};
    }

    /**
     * @param array $meta
     * @return $this
     */
    public function setMeta(array $meta): self
    {
        $this->{self::PROP_META} = $meta;

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
     * @throws InvalidArgumentException
     */
    public function fromArray(array $data): self
    {
        foreach ([self::PROP_TOKEN, self::PROP_OWNER_ID] as $prop) {
            if (empty($data[$prop])) {
                throw new InvalidArgumentException('Required option not passed: ' . $prop);
            }

            $this->{$prop} = $data[$prop];
        }

        // Can be null
        if (isset($data[self::PROP_EXPIRES])) {
            $this->{self::PROP_EXPIRES} = $data[self::PROP_EXPIRES] !== null ?
                (int) $data[self::PROP_EXPIRES] :
                $data[self::PROP_EXPIRES];
        }

        if (!empty($data[self::PROP_REFRESH_TOKEN])) {
            $this->{self::PROP_REFRESH_TOKEN} = $data[self::PROP_REFRESH_TOKEN];
        }

        if (!empty($data[self::PROP_META])) {
            $this->{self::PROP_META} = $data[self::PROP_META];
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $data = [];
        foreach (
            [
                self::PROP_TOKEN,
                self::PROP_REFRESH_TOKEN,
                self::PROP_EXPIRES,
                self::PROP_OWNER_ID,
                self::PROP_META
            ] as $prop) {
            $data[$prop] = $this->{$prop};
        }

        return $data;
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array $data
     * @return void
     * @throws InvalidArgumentException
     */
    public function __unserialize(array $data): void
    {
        $this->fromArray($data);
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
     * @throws InvalidArgumentException
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
    #[\ReturnTypeWillChange]
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

        if ($expires === null) {
            return false;
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
