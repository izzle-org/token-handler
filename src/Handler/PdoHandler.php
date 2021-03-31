<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Handler;

use Exception;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Izzle\TokenHandler\Exceptions\JsonException;
use Izzle\TokenHandler\Model\Token;
use Izzle\TokenHandler\Model\TokenInterface;
use PDO;
use PDOException;

class PdoHandler implements HandlerInterface
{
    public const COLUMN_OWNER_ID = 'owner_id';
    public const COLUMN_ACCESS_TOKEN = 'access_token';
    public const COLUMN_EXPIRES = 'expires';
    public const COLUMN_LOCKED = 'is_locked';
    public const COLUMN_ERROR_COUNT = 'error_count';

    /**
     * @var PDO
     */
    protected PDO $pdo;

    /**
     * @var EncrypterContract
     */
    protected EncrypterContract $encrypter;

    /**
     * @var string
     */
    protected string $table;

    /**
     * @var string[]
     */
    protected array $columns = [
        self::COLUMN_OWNER_ID,
        self::COLUMN_ACCESS_TOKEN,
        self::COLUMN_EXPIRES,
        self::COLUMN_LOCKED,
        self::COLUMN_ERROR_COUNT,
    ];

    /**
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * @return EncrypterContract
     */
    public function getEncrypter(): EncrypterContract
    {
        return $this->encrypter;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * PdoHandler constructor.
     * @param PDO $pdo
     * @param EncrypterContract $encrypter
     * @param array $options
     */
    public function __construct(PDO $pdo, EncrypterContract $encrypter, array $options = [])
    {
        $this->pdo = $pdo;
        $this->encrypter = $encrypter;

        $this->configure($options);
        $this->checkDatabase();
    }

    /**
     * @param string|null $ownerId
     * @return TokenInterface|null
     */
    public function loadToken(?string $ownerId): ?TokenInterface
    {
        $stmt = $this->pdo->prepare('
            SELECT ' . implode(', ', $this->columns) . '
            FROM `' . $this->table . '`
            WHERE ' . self::COLUMN_OWNER_ID . ' = :ownerId
                AND ' . self::COLUMN_LOCKED . ' = :locked
        ');

        $isLocked = false;

        $stmt->bindParam(':ownerId', $ownerId);
        $stmt->bindParam(':locked', $isLocked, PDO::PARAM_BOOL);
        if (!$stmt->execute()) {
            throw new PDOException(implode(PHP_EOL, $this->pdo->errorInfo()), $this->pdo->errorCode());
        }

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data === false) {
            return null;
        }

        return $this->dataToToken($data);
    }

    /**
     * @param string[] $ownerIds
     * @return TokenInterface[]
     */
    public function loadTokens(array $ownerIds): array
    {
        if (count($ownerIds) === 0) {
            return [];
        }

        $arrayParam = static function (array $ids, string $prefix = 'id') {
            $params = [];
            foreach ($ids as $i => $id) {
                $params[] = sprintf(':%s%s', $prefix, $i);
            }

            return $params;
        };

        $stmt = $this->pdo->prepare('
            SELECT ' . implode(', ', $this->columns) . '
            FROM `' . $this->table . '`
            WHERE ' . self::COLUMN_OWNER_ID . ' in (' . implode(',', $arrayParam($ownerIds)) . ')
                AND ' . self::COLUMN_LOCKED . ' = :locked
        ');

        $isLocked = false;

        foreach ($arrayParam($ownerIds) as $i => $param) {
            $stmt->bindParam($param, $ownerIds[$i]);
        }

        $stmt->bindParam(':locked', $isLocked, PDO::PARAM_BOOL);
        if (!$stmt->execute()) {
            throw new PDOException(implode(PHP_EOL, $this->pdo->errorInfo()), $this->pdo->errorCode());
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($data === false) {
            return [];
        }

        $tokens = [];
        foreach ($data as $d) {
            $token = $this->dataToToken($d);
            if ($token === null) {
                continue;
            }

            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * @param int $seconds
     * @return TokenInterface[]
     */
    public function loadTokensThatExpiresIn(int $seconds): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ' . implode(', ', $this->columns) . '
            FROM `' . $this->table . '`
            WHERE expires <= :expires
                AND ' . self::COLUMN_LOCKED . ' = :locked
        ');

        $expires = time() + $seconds;
        $isLocked = false;

        $stmt->bindParam(':expires', $expires, PDO::PARAM_INT);
        $stmt->bindParam(':locked', $isLocked, PDO::PARAM_BOOL);
        if (!$stmt->execute()) {
            throw new PDOException(implode(PHP_EOL, $this->pdo->errorInfo()), $this->pdo->errorCode());
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($data === false) {
            return [];
        }

        $tokens = [];
        foreach ($data as $d) {
            $token = $this->dataToToken($d);
            if ($token === null) {
                continue;
            }

            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * @param TokenInterface $token
     * @param string|null $ownerId
     * @return bool
     * @throws JsonException
     * @throws Exception
     * @throws PDOException
     */
    public function saveToken(TokenInterface $token, ?string $ownerId): bool
    {
        if ($ownerId === null) {
            return true;
        }

        $stmt = $this->pdo->prepare('
            REPLACE INTO `' . $this->table . '` VALUES (:ownerId, :token, :expires, :locked, :error)
        ');

        $json = json_encode($token);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonException(json_last_error_msg(), json_last_error());
        }

        $t = $this->encrypter->encrypt($json, false);

        $expires = $token->getExpires();
        $locked = false;
        $errorCount = 0;

        $stmt->bindParam(':ownerId', $ownerId);
        $stmt->bindParam(':token', $t);
        $stmt->bindParam(':expires', $expires, PDO::PARAM_INT);
        $stmt->bindParam(':locked', $locked, PDO::PARAM_BOOL);
        $stmt->bindParam(':error', $errorCount, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            throw new PDOException(implode(PHP_EOL, $this->pdo->errorInfo()), $this->pdo->errorCode());
        }

        return true;
    }

    /**
     * @param string|null $ownerId
     * @return bool
     * @throws PDOException
     */
    public function removeToken(?string $ownerId): bool
    {
        if ($ownerId === null) {
            return true;
        }

        $stmt = $this->pdo->prepare('
            DELETE FROM `' . $this->table . '`
            WHERE ' . self::COLUMN_OWNER_ID . ' = :ownerId
        ');

        $stmt->bindParam(':ownerId', $ownerId);

        if (!$stmt->execute()) {
            throw new PDOException(implode(PHP_EOL, $this->pdo->errorInfo()), $this->pdo->errorCode());
        }

        return true;
    }

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function lockToken(?string $ownerId): bool
    {
        return $this->updateLock($ownerId, true);
    }

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function unlockToken(?string $ownerId): bool
    {
        return $this->updateLock($ownerId, false);
    }

    /**
     * @param string|null $ownerId
     * @return bool
     */
    public function isLocked(?string $ownerId): bool
    {
        if ($ownerId === null) {
            return false;
        }

        $stmt = $this->pdo->prepare('
            SELECT ' . self::COLUMN_LOCKED . '
            FROM `' . $this->table . '`
            WHERE ' . self::COLUMN_OWNER_ID . ' = :ownerId
        ');

        $stmt->bindParam(':ownerId', $ownerId);
        if (!$stmt->execute()) {
            throw new PDOException(implode(PHP_EOL, $this->pdo->errorInfo()), $this->pdo->errorCode());
        }

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data === false) {
            return false;
        }

        return (bool) $data[self::COLUMN_LOCKED];
    }

    /**
     * @param string|null $ownerId
     * @param bool $isLocked
     * @return bool
     * @throws PDOException
     */
    protected function updateLock(?string $ownerId, bool $isLocked): bool
    {
        if ($ownerId === null) {
            return false;
        }

        $stmt = $this->pdo->prepare('
            UPDATE `' . $this->table . '`
            SET ' . self::COLUMN_LOCKED . ' = :locked
            WHERE ' . self::COLUMN_OWNER_ID . ' = :ownerId
        ');

        $stmt->bindParam(':locked', $isLocked, PDO::PARAM_BOOL);
        $stmt->bindParam(':ownerId', $ownerId);

        if (!$stmt->execute()) {
            throw new PDOException(implode(PHP_EOL, $this->pdo->errorInfo()), $this->pdo->errorCode());
        }

        return true;
    }

    /**
     * @param array $options
     */
    protected function configure(array $options): void
    {
        $this->table = empty($options['table']) ? 'auth-tokens' : $options['table'];
    }

    /**
     * @return bool
     * @throws PDOException
     */
    protected function checkDatabase(): bool
    {
        $rows = $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS `' . $this->table . '` (
                `' . self::COLUMN_OWNER_ID . '` VARCHAR(255) NOT NULL,
                `' . self::COLUMN_ACCESS_TOKEN . '` TEXT NOT NULL,
                `' . self::COLUMN_EXPIRES . '` BIGINT NULL DEFAULT NULL,
                `' . self::COLUMN_LOCKED . '` TINYINT(1) NOT NULL,
                `' . self::COLUMN_ERROR_COUNT . '` TINYINT NOT NULL,
                PRIMARY KEY (`' . self::COLUMN_OWNER_ID . '`)
            );
        '); // ENGINE = InnoDB CHARSET=utf8mb4 COLLATE utf8mb4_general_ci please :)
        // INDEX(`' . self::COLUMN_LOCKED . '`,`' . self::COLUMN_EXPIRES . '`)

        if ($rows === false) {
            throw new PDOException(sprintf('Could not verify table %s. Please check your connection.', $this->table));
        }

        return true;
    }

    /**
     * @param array $data
     * @return TokenInterface|null
     */
    protected function dataToToken(array $data): ?TokenInterface
    {
        $json = $this->encrypter->decrypt($data[self::COLUMN_ACCESS_TOKEN], false);
        if ($json === null) {
            return null;
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return new Token($data);
    }
}
