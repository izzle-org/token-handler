<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Tests\Handler;

use Izzle\TokenHandler\Handler\NullTokenHandler;
use Izzle\TokenHandler\Model\Token;
use Izzle\TokenHandler\Model\TokenInterface;
use Izzle\TokenHandler\Tests\Test;

class NullHandlerTest extends Test
{
    public function testCanBeInstantiated(): void
    {
        $handler = new NullTokenHandler();

        self::assertInstanceOf(NullTokenHandler::class, $handler);
    }

    public function testCanSaveToken(): void
    {
        $handler = new NullTokenHandler();

        self::assertTrue($handler->saveToken($this->getToken(), self::$ownerId));
    }

    public function testCanLoadToken(): void
    {
        $handler = new NullTokenHandler();

        $token = $handler->loadToken(self::$ownerId);

        self::assertNull($token);
    }

    public function testCanRemoveToken(): void
    {
        $handler = new NullTokenHandler();

        self::assertTrue($handler->removeToken(self::$ownerId));
    }

    public function testCanLoadTokens(): void
    {
        $handler = new NullTokenHandler();

        $tokens = $handler->loadTokens([self::$ownerId, '5']);
        self::assertCount(0, $tokens);
    }

    public function testCanLockToken(): void
    {
        $handler = new NullTokenHandler();

        self::assertTrue($handler->lockToken(self::$ownerId));
        self::assertNull($handler->loadToken(self::$ownerId));
        self::assertTrue($handler->unlockToken(self::$ownerId));
    }
}
