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
        $this->assertInstanceOf(TokenInterface::class, $token);
    }
    
    public function testTokenImplementsJsonSerializable(): void
    {
        $token = new Token();
        $this->assertInstanceOf(JsonSerializable::class, $token);
    }
    
    public function testTokenImplementsSerializable(): void
    {
        $token = new Token();
        $this->assertInstanceOf(Serializable::class, $token);
    }
    
    public function testTokenCanLoadedFromArray(): void
    {
        $token = new Token();
        $token->fromArray(json_decode($this->loadMock(), true));
        
        $this->assertEquals('12345', $token->getToken());
        $this->assertEquals(2524608000, $token->getExpires());
        $this->assertEquals('13AB', $token->getOwnerId());
        
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
    
        $this->assertEquals('12345', $token->getToken());
        $this->assertEquals(2524608000, $token->getExpires());
        $this->assertEquals('13AB', $token->getOwnerId());
    
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
    
        $this->assertEquals('12345', $t->getToken());
        $this->assertEquals(2524608000, $t->getExpires());
        $this->assertEquals('13AB', $t->getOwnerId());
    }
    
    public function testTokenCanBeSerialized(): void
    {
        $token = $this->getToken();
        $serialized = serialize($token);
        $t = unserialize($serialized);
        
        $this->assertEquals('12345', $t->getToken());
        $this->assertEquals(2524608000, $t->getExpires());
        $this->assertEquals('13AB', $t->getOwnerId());
    }
}
