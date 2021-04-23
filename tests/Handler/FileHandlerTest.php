<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Tests\Handler;

use Illuminate\Contracts\Encryption\DecryptException;
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

        self::assertInstanceOf(FileHandler::class, $fileHandler);
    }

    public function testInstantiationMustThrow(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FileHandler('', new Encrypter(self::$key));
    }

    public function testCanGetEncrypter(): void
    {
        $handler = self::getFileHandler();
        self::assertNotNull($handler->getEncrypter());
    }

    public function testCanGetPath(): void
    {
        $handler = self::getFileHandler();
        self::assertNotNull($handler->getPath());
        self::assertIsString($handler->getPath());
        self::assertFileExists($handler->getPath());
    }

    public function testCanSaveToken(): void
    {
        $fileHandler = self::getFileHandler();
        $token = $this->getToken();

        self::assertTrue($fileHandler->saveToken($token, self::$ownerId));
        self::assertFileExists($fileHandler->getPath() . DIRECTORY_SEPARATOR . self::$ownerId);
    }

    public function testCanLoadToken(): void
    {
        $fileHandler = self::getFileHandler();
        $fileHandler->saveToken($this->getToken(), self::$ownerId);

        $token = $fileHandler->loadToken(self::$ownerId);

        self::assertInstanceOf(TokenInterface::class, $token);
        self::assertEquals($this->getToken(), $token);

        self::assertEquals('12345', $token->getToken());
        self::assertEquals(2524608000, $token->getExpires());
        self::assertEquals('13AB', $token->getOwnerId());
        self::assertEquals(['foo' => 'bar', 'x' => 3], $token->getMeta());
    }

    public function testCanRemoveToken(): void
    {
        $fileHandler = self::getFileHandler();
        $fileHandler->saveToken($this->getToken(), self::$ownerId);

        self::assertTrue($fileHandler->removeToken(self::$ownerId));
        self::assertFileDoesNotExist($fileHandler->getPath() . DIRECTORY_SEPARATOR . self::$ownerId);
    }

    public function testCanLoadTokens(): void
    {
        $handler = self::getFileHandler();

        $handler->saveToken($this->getToken(), self::$ownerId);
        $handler->saveToken($this->getToken(), '5');

        $tokens = $handler->loadTokens([self::$ownerId, '5']);
        self::assertCount(2, $tokens);
    }

    public function testCanLockToken(): void
    {
        $fileHandler = self::getFileHandler();

        $fileHandler->saveToken($this->getToken(), self::$ownerId);
        self::assertTrue($fileHandler->lockToken(self::$ownerId));
        self::assertNull($fileHandler->loadToken(self::$ownerId));
        self::assertTrue($fileHandler->unlockToken(self::$ownerId));
        self::assertEquals($this->getToken(), $fileHandler->loadToken(self::$ownerId));
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
        self::assertIsArray($tokens);
        self::assertCount(3, $tokens);
    }

    /**
     * @depends testCanSaveToken
     */
    public function testCanLoadEmptyFiles(): void
    {
        $handler = self::getFileHandler();

        file_put_contents($handler->filePath(self::$ownerId), '');

        $accessToken = $handler->loadToken(self::$ownerId);

        self::assertNull($accessToken);

        file_put_contents($handler->filePath(self::$ownerId), '{}');

        $this->expectException(DecryptException::class);
        $handler->loadToken(self::$ownerId);
    }
}
