<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Tests\Handler;

use InvalidArgumentException;
use Illuminate\Encryption\Encrypter;
use Izzle\TokenHandler\Handler\FileHandler;
use Izzle\TokenHandler\Model\Token;
use Izzle\TokenHandler\Model\TokenInterface;
use Izzle\TokenHandler\Tests\Test;

class FileHandlerTest extends Test
{
    /**
     * @return string
     */
    protected static function getPath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tokenHandlerTest';
    }

    /**
     * @return FileHandler
     */
    protected static function getFileHandler(): FileHandler
    {
        return new FileHandler(self::getPath(), new Encrypter(self::$key));
    }

    public static function setUpBeforeClass(): void
    {
        if (!is_dir(self::getPath())) {
            @mkdir(self::getPath());
        }
    }

    public static function tearDownAfterClass(): void
    {
        $fileHandler = self::getFileHandler();
        $fileHandler->unlockToken(self::$ownerId);
        $fileHandler->removeToken(self::$ownerId);

        @rmdir(self::getPath());
    }

    public function testCanBeInstantiated(): void
    {
        $fileHandler = new FileHandler(self::getPath(), new Encrypter(self::$key));

        $this->assertInstanceOf(FileHandler::class, $fileHandler);
    }

    public function testInstantiationMustThrow(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FileHandler('', new Encrypter(self::$key));
    }

    public function testCanGetEncrypter(): void
    {
        $handler = self::getFileHandler();
        $this->assertNotNull($handler->getEncrypter());
    }

    public function testCanGetPath(): void
    {
        $handler = self::getFileHandler();
        $this->assertNotNull($handler->getPath());
        $this->assertIsString($handler->getPath());
        $this->assertFileExists($handler->getPath());
    }

    public function testCanSaveToken(): void
    {
        $fileHandler = self::getFileHandler();
        $token = $this->getToken();

        $this->assertTrue($fileHandler->saveToken($token, self::$ownerId));
        $this->assertFileExists($fileHandler->getPath() . DIRECTORY_SEPARATOR . self::$ownerId);
    }

    public function testCanLoadToken(): void
    {
        $fileHandler = self::getFileHandler();
        $fileHandler->saveToken($this->getToken(), self::$ownerId);

        $token = $fileHandler->loadToken(self::$ownerId);

        $this->assertInstanceOf(TokenInterface::class, $token);
        $this->assertEquals($this->getToken(), $token);
    }

    public function testCanRemoveToken(): void
    {
        $fileHandler = self::getFileHandler();
        $fileHandler->saveToken($this->getToken(), self::$ownerId);

        $this->assertTrue($fileHandler->removeToken(self::$ownerId));
        $this->assertFileNotExists($fileHandler->getPath() . DIRECTORY_SEPARATOR . self::$ownerId);
    }

    public function testCanLoadTokens(): void
    {
        $handler = self::getFileHandler();

        $handler->saveToken($this->getToken(), self::$ownerId);
        $handler->saveToken($this->getToken(), '5');

        $tokens = $handler->loadTokens([self::$ownerId, '5']);
        $this->assertCount(2, $tokens);
    }

    public function testCanLockToken(): void
    {
        $fileHandler = self::getFileHandler();

        $fileHandler->saveToken($this->getToken(), self::$ownerId);
        $this->assertTrue($fileHandler->lockToken(self::$ownerId));
        $this->assertNull($fileHandler->loadToken(self::$ownerId));
        $this->assertTrue($fileHandler->unlockToken(self::$ownerId));
        $this->assertEquals($this->getToken(), $fileHandler->loadToken(self::$ownerId));
    }

    public function testCanLoadTokensThatExpiresIn(): void
    {
        $handler = self::getFileHandler();

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
        $this->assertIsArray($tokens);
        $this->assertCount(3, $tokens);
    }
}
