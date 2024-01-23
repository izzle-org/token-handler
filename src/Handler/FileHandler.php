<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Handler;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Contracts\Encryption\EncryptException;
use InvalidArgumentException;
use Izzle\TokenHandler\Model\Token;
use Izzle\TokenHandler\Model\TokenInterface;

class FileHandler implements HandlerInterface
{
    /**
     * @var string
     */
    protected string $path;

    /**
     * @var EncrypterContract
     */
    protected EncrypterContract $encrypter;

    /**
     * FileHandler constructor.
     * @param string $path
     * @param EncrypterContract $encrypter
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $path,
        EncrypterContract $encrypter
    ) {
        if (!is_writable($path)) {
            throw new InvalidArgumentException(sprintf('Path (%s) is not writable', $path));
        }

        $this->path = $path;
        $this->encrypter = $encrypter;
    }

    /**
     * @param string|null $ownerId
     * @throws DecryptException
     * @return TokenInterface|null
     */
    public function loadToken(?string $ownerId): ?TokenInterface
    {
        if ($this->isLocked($ownerId)) {
            return null;
        }

        if (!file_exists($this->filePath($ownerId))) {
            return null;
        }

        $content = file_get_contents($this->filePath($ownerId));
        if (empty($content)) {
            return null;
        }

        $json = $this->encrypter->decrypt($content, false);
        if ($json === null) {
            return null;
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return new Token($data);
    }

    /**
     * @param string[] $ownerIds
     * @return array
     */
    public function loadTokens(array $ownerIds): array
    {
        $tokens = [];

        foreach ($ownerIds as $ownerId) {
            if ($this->isLocked($ownerId)) {
                continue;
            }

            $token = $this->loadToken($ownerId);
            if ($token === null) {
                continue;
            }

            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * @param int $seconds - Expires in
     * @return TokenInterface[]
     */
    public function loadTokensThatExpiresIn(int $seconds): array
    {
        $tokens = [];
        foreach (glob(sprintf('%s%s%s', $this->path, DIRECTORY_SEPARATOR, '*')) as $file) {
            if (is_dir($file)) {
                continue;
            }

            $ownerId = pathinfo($file, PATHINFO_FILENAME);
            if ($this->isLocked($ownerId)) {
                continue;
            }

            $token = $this->loadToken(pathinfo($file, PATHINFO_FILENAME));
            if ($token === null) {
                continue;
            }

            if ($token->getExpires() !== null && $token->getExpires() <= time() + $seconds) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    /**
     * @param TokenInterface $token
     * @param string|null $ownerId
     * @throws EncryptException
     * @return bool
     */
    public function saveToken(TokenInterface $token, ?string $ownerId): bool
    {
        $json = json_encode($token);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        $content = $this->encrypter->encrypt($json, false);
        if ($content === '') {
            return false;
        }

        return file_put_contents($this->filePath($ownerId), $content) !== false;
    }

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function removeToken(?string $ownerId): bool
    {
        if (!file_exists($this->filePath($ownerId))) {
            return true;
        }

        return unlink($this->filePath($ownerId));
    }

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function lockToken(?string $ownerId): bool
    {
        return file_put_contents($this->getLockPath($ownerId), '') !== false;
    }

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function unlockToken(?string $ownerId): bool
    {
        if (!file_exists($this->getLockPath($ownerId))) {
            return true;
        }

        return unlink($this->getLockPath($ownerId));
    }

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function isLocked(?string $ownerId): bool
    {
        return file_exists($this->getLockPath($ownerId));
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return EncrypterContract
     */
    public function getEncrypter(): EncrypterContract
    {
        return $this->encrypter;
    }

    /**
     * @param string|null $ownerId
     * @return string
     */
    public function filePath(?string $ownerId): string
    {
        return sprintf('%s%s%s', $this->path, DIRECTORY_SEPARATOR, $ownerId ?? 'token');
    }

    /**
     * @param string|null $ownerId
     * @return string
     */
    public function getLockPath(?string $ownerId): string
    {
        $file = 'token.locked';
        if ($ownerId !== null) {
            $file = $ownerId . '.locked';
        }

        return sprintf('%s%s%s', $this->path, DIRECTORY_SEPARATOR, $file);
    }
}
