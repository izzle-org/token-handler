<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Tests\Handler;

use Illuminate\Encryption\Encrypter;
use Izzle\TokenHandler\Handler\PdoHandler;
use Izzle\TokenHandler\Model\Token;
use Izzle\TokenHandler\Model\TokenInterface;
use Izzle\TokenHandler\Tests\Test;
use PDO;


class PdoHandlerTest extends Test
{
    /**
     * @var PDO
     */
    protected static $db;

    /**
     * @return PdoHandler
     */
    protected static function getPdoHandler(): PdoHandler
    {
        return new PdoHandler(self::$db, new Encrypter(self::$key), [
            'table' => 'test',
        ]);
    }

    public static function setUpBeforeClass(): void
    {
        self::$db = new PDO('sqlite::memory:');
    }

    public static function tearDownAfterClass(): void
    {
        $handler = self::getPdoHandler();
        $handler->unlockToken(self::$ownerId);
        $handler->removeToken(self::$ownerId);

        self::$db = null;
    }

    public function testCanBeInstantiated(): void
    {
        $handler = new PdoHandler(self::$db, new Encrypter(self::$key));

        self::assertInstanceOf(PdoHandler::class, $handler);
    }

    public function testCanBeInstantiatedWithOptions(): void
    {
        $handler = new PdoHandler(self::$db, new Encrypter(self::$key), [
            'table' => 'foobar',
        ]);

        self::assertInstanceOf(PdoHandler::class, $handler);
        self::assertEquals($handler->getTable(), 'foobar');
    }

    public function testCanGetEncrypter(): void
    {
        $handler = self::getPdoHandler();
        self::assertNotNull($handler->getEncrypter());
    }

    public function testCanGetDatabase(): void
    {
        $handler = self::getPdoHandler();
        self::assertNotNull($handler->getPdo());
    }

    public function testCanSaveToken(): void
    {
        $handler = self::getPdoHandler();
        $token = $this->getToken();

        $handler->saveToken($token, self::$ownerId);

        self::assertTrue($handler->saveToken($token, self::$ownerId));

        // Nullable expire time
        $token = new Token([
            Token::PROP_TOKEN => '12345',
            Token::PROP_OWNER_ID => '13',
        ]);

        $handler->saveToken($token, self::$ownerId);
    }

    public function testCanLoadToken(): void
    {
        $handler = self::getPdoHandler();
        $handler->saveToken($this->getToken(), self::$ownerId);

        $token = $handler->loadToken(self::$ownerId);

        self::assertInstanceOf(TokenInterface::class, $token);
        self::assertEquals($this->getToken(), $token);

        self::assertEquals('12345', $token->getToken());
        self::assertEquals(2524608000, $token->getExpires());
        self::assertEquals('13AB', $token->getOwnerId());
        self::assertEquals(['foo' => 'bar', 'x' => 3], $token->getMeta());
    }

    public function testCanRemoveToken(): void
    {
        $handler = self::getPdoHandler();
        self::assertTrue($handler->removeToken(self::$ownerId));
    }

    public function testCanLoadTokens(): void
    {
        $handler = self::getPdoHandler();

        $handler->saveToken($this->getToken(), self::$ownerId);
        $handler->saveToken($this->getToken(), '5');

        $tokens = $handler->loadTokens([self::$ownerId, '5']);
        self::assertCount(2, $tokens);
    }

    public function testCanLockToken(): void
    {
        $handler = self::getPdoHandler();

        $handler->saveToken($this->getToken(), self::$ownerId);
        self::assertTrue($handler->lockToken(self::$ownerId));
        self::assertNull($handler->loadToken(self::$ownerId));
        self::assertTrue($handler->unlockToken(self::$ownerId));
        self::assertEquals($this->getToken(), $handler->loadToken(self::$ownerId));
    }

    public function testCanLoadTokensThatExpiresIn(): void
    {
        $handler = self::getPdoHandler();

        $json = $this->loadMock('tokens.json');

        foreach (json_decode($json, true, 512, JSON_THROW_ON_ERROR) as $data) {
            $token = new Token($data);

            $handler->saveToken($token, $token->getOwnerId());
        }

        // Not Expired but will expire in less than 300 seconds
        $token = new Token([
            Token::PROP_TOKEN => 'foo',
            Token::PROP_OWNER_ID => 'bar',
            Token::PROP_EXPIRES => time() + 255
        ]);

        $handler->saveToken($token, $token->getOwnerId());

        $tokens = $handler->loadTokensThatExpiresIn(300);
        self::assertIsArray($tokens);
        self::assertCount(3, $tokens);
    }
}
