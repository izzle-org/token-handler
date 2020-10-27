<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Tests\Handler;

use Illuminate\Encryption\Encrypter;
use Izzle\TokenHandler\Handler\PdoHandler;
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
            'table' => 'test'
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
        
        $this->assertInstanceOf(PdoHandler::class, $handler);
    }
    
    public function testCanBeInstantiatedWithOptions(): void
    {
        $handler = new PdoHandler(self::$db, new Encrypter(self::$key), [
            'table' => 'foobar'
        ]);
        
        $this->assertInstanceOf(PdoHandler::class, $handler);
        $this->assertEquals($handler->getTable(), 'foobar');
    }
    
    public function testCanGetEncrypter(): void
    {
        $handler = self::getPdoHandler();
        $this->assertNotNull($handler->getEncrypter());
    }
    
    public function testCanGetDatabase(): void
    {
        $handler = self::getPdoHandler();
        $this->assertNotNull($handler->getPdo());
    }
    
    public function testCanSaveToken(): void
    {
        $handler = self::getPdoHandler();
        $token = $this->getToken();
        
        $handler->saveToken($token, self::$ownerId);
        
        $this->assertTrue($handler->saveToken($token, self::$ownerId));
    }
    
    public function testCanLoadToken(): void
    {
        $handler = self::getPdoHandler();
        $handler->saveToken($this->getToken(), self::$ownerId);
        
        $token = $handler->loadToken(self::$ownerId);
        
        $this->assertInstanceOf(TokenInterface::class, $token);
        $this->assertEquals($this->getToken(), $token);
    }
    
    public function testCanRemoveToken(): void
    {
        $handler = self::getPdoHandler();
        $this->assertTrue($handler->removeToken(self::$ownerId));
    }
    
    public function testCanLoadTokens(): void
    {
        $handler = self::getPdoHandler();
        
        $handler->saveToken($this->getToken(), self::$ownerId);
        $handler->saveToken($this->getToken(), '5');
        
        $tokens = $handler->loadTokens([self::$ownerId, '5']);
        $this->assertCount(2, $tokens);
    }
    
    public function testCanLockToken(): void
    {
        $handler = self::getPdoHandler();
        
        $handler->saveToken($this->getToken(), self::$ownerId);
        $this->assertTrue($handler->lockToken(self::$ownerId));
        $this->assertNull($handler->loadToken(self::$ownerId));
        $this->assertTrue($handler->unlockToken(self::$ownerId));
        $this->assertEquals($this->getToken(), $handler->loadToken(self::$ownerId));
    }
}
