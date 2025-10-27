<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Tests\Model;

use InvalidArgumentException;
use Izzle\TokenHandler\Model\Token;
use Izzle\TokenHandler\Model\TokenInterface;
use Izzle\TokenHandler\Tests\Test;
use JsonSerializable;
use Serializable;

class TokenTest extends Test
{
    public function testTokenImplementsTokenInterface(): void
    {
        $token = new Token();
        self::assertInstanceOf(TokenInterface::class, $token);
    }

    /**
     * @throws \JsonException
     */
    public function testTokenImplementsJsonSerializable(): void
    {
        $token = new Token();
        $token->setOwnerId('13AB')
            ->setToken('12345')
            ->setExpires(2524608000)
            ->setMeta(['foo' => 'bar', 'x' => 3]);

        self::assertInstanceOf(JsonSerializable::class, $token);
        $json = file_get_contents(__DIR__ . '/../Mocks/token.json');
        $data = json_decode($json);

        self::assertEquals(json_encode($data), json_encode($token, JSON_THROW_ON_ERROR));
    }

    public function testTokenImplementsSerializable(): void
    {
        $token = new Token();
        self::assertInstanceOf(Serializable::class, $token);
    }

    public function testTokenCanLoadedFromArray(): void
    {
        $token = new Token();
        $token->fromArray(json_decode($this->loadMock(), true));

        self::assertEquals('12345', $token->getToken());
        self::assertEquals(2524608000, $token->getExpires());
        self::assertEquals('13AB', $token->getOwnerId());
        self::assertEquals(['foo' => 'bar', 'x' => 3], $token->getMeta());

        // OwnerId can't be "0"
        $this->expectException(InvalidArgumentException::class);
        $data = json_decode($this->loadMock(), true);
        $data[Token::PROP_OWNER_ID] = '0';
        $token->fromArray($data);

        $this->expectException(InvalidArgumentException::class);
        $token->fromArray([
            Token::PROP_TOKEN => '2342'
        ]);

        $this->expectException(InvalidArgumentException::class);
        $token->fromArray([
            Token::PROP_TOKEN => '2342',
            Token::PROP_OWNER_ID => '234'
        ]);
    }

    public function testTokenCanConstructedFromArray(): void
    {
        $token = new Token(json_decode($this->loadMock(), true));

        self::assertEquals('12345', $token->getToken());
        self::assertEquals(2524608000, $token->getExpires());
        self::assertEquals('13AB', $token->getOwnerId());
        self::assertEquals(['foo' => 'bar', 'x' => 3], $token->getMeta());

        $this->expectException(InvalidArgumentException::class);
        new Token([
            Token::PROP_TOKEN => '2342'
        ]);

        $this->expectException(InvalidArgumentException::class);
        new Token([
            Token::PROP_TOKEN => '2342',
            Token::PROP_OWNER_ID => '234'
        ]);
    }

    public function testTokenCanBeJsonSerialized(): void
    {
        $token = $this->getToken();
        $json = json_encode($token, JSON_THROW_ON_ERROR, 512);
        $t = new Token(json_decode($json, true));

        self::assertEquals('12345', $t->getToken());
        self::assertEquals(2524608000, $t->getExpires());
        self::assertEquals('13AB', $t->getOwnerId());
        self::assertEquals(['foo' => 'bar', 'x' => 3], $t->getMeta());
    }

    public function testTokenCanBeSerialized(): void
    {
        $token = $this->getToken();
        $serialized = serialize($token);
        $t = unserialize($serialized);

        self::assertEquals('12345', $t->getToken());
        self::assertEquals(2524608000, $t->getExpires());
        self::assertEquals('13AB', $t->getOwnerId());
        self::assertEquals(['foo' => 'bar', 'x' => 3], $t->getMeta());
    }

    public function testTokenCanExpire(): void
    {
        $token = $this->getToken();
        self::assertFalse($token->hasExpired());

        $token = $this->getToken('token_expired.json');
        self::assertTrue($token->hasExpired());

        $token->setExpires(time());
        self::assertFalse($token->hasExpired());

        $token->setExpires(time() - 1);
        self::assertTrue($token->hasExpired());

        $token->setExpires(null);
        self::assertFalse($token->hasExpired());
    }

    public function testTokenCanNotExpire(): void
    {
        $token = new Token([
            Token::PROP_TOKEN => '12345',
            Token::PROP_OWNER_ID => 'foobar'
        ]);

        self::assertNull($token->getExpires());
        self::assertFalse($token->hasExpired());
    }

    public function testTokenCanHasToArrayMethod(): void
    {
        $token = new Token([
            Token::PROP_TOKEN => '12345',
            Token::PROP_OWNER_ID => 'foobar'
        ]);

        $data = $token->toArray();

        foreach (
            [
                Token::PROP_TOKEN,
                Token::PROP_REFRESH_TOKEN,
                Token::PROP_EXPIRES,
                Token::PROP_OWNER_ID,
                Token::PROP_META
            ] as $key) {
            self::assertArrayHasKey($key, $data);
        }

        self::assertEquals('12345', $data[Token::PROP_TOKEN]);
        self::assertEquals('foobar', $data[Token::PROP_OWNER_ID]);
    }

    public function testOwnerIdCannotBeEmpty(): void
    {
        $token = new Token([
            Token::PROP_TOKEN => '12345',
            Token::PROP_OWNER_ID => 'foobar'
        ]);

        $this->expectException(InvalidArgumentException::class);
        $token->setOwnerId('0');

        $this->expectException(InvalidArgumentException::class);
        $token->setOwnerId('');
    }

    public function testRefreshTokenCanBeNull(): void
    {
        $token = new Token([
            Token::PROP_TOKEN => '12345',
            Token::PROP_OWNER_ID => 'foobar'
        ]);

        $token->setRefreshToken(null);
        self::assertNull($token->getRefreshToken());
    }

    public function testTokenCanHoldMetaInformation(): void
    {
        $token = new Token([
            Token::PROP_TOKEN => '12345',
            Token::PROP_OWNER_ID => 'foobar'
        ]);

        $token->setMeta(['foo' => 'bar']);

        self::assertEquals(['foo' => 'bar'], $token->getMeta());
    }
}
