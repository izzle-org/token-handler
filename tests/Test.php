<?php declare(strict_types=1);

namespace Izzle\TokenHandler\Tests;

use Izzle\TokenHandler\Model\Token;
use Izzle\TokenHandler\Model\TokenInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class Test
 * @package Izzle\TokenHandler\Tests
 */
abstract class Test extends TestCase
{
    /**
     * @var string
     */
    protected static string $key = '0123456789ABCDEF';
    
    /**
     * @var string
     */
    protected static string $ownerId = '13AB';
    
    protected function getToken(string $file = 'token.json'): TokenInterface
    {
        return new Token(
            json_decode($this->loadMock($file), true, 512, JSON_THROW_ON_ERROR)
        );
    }
    
    /**
     * @param string $file
     * @return string
     */
    protected function loadMock(string $file = 'token.json'): string
    {
        return file_get_contents(sprintf('%s%s%s', $this->getMockDirectory(), DIRECTORY_SEPARATOR, $file));
    }
    
    /**
     * @return string
     */
    protected function getMockDirectory(): string
    {
        return sprintf('%s%s%s', __DIR__, DIRECTORY_SEPARATOR, 'Mocks');
    }
}
