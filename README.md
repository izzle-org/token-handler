# Izzle Simple Token Handler PHP Lib

## Installation

> Using npm:
 ```shell
 $ composer require izzle/token-handler
 ```

## Usage

### Save Token
```php
use Izzle\TokenHandler\Model\Token;
use Illuminate\Encryption\Encrypter;
use Izzle\TokenHandler\Handler\FileHandler;

// Owner Id for this token. Must be unique. Ex. a User Id.
$ownerId = 'someOwnerId';

// Encrypter with random 16 Key for Cipher AES-128-CBC
// Cipher AES-256-CBC will need a 32 Char Key
$encrypter = new Encrypter('01234567890123456');

$token = (new Token())->setToken('someToken')
    ->setExpires(time() + 3600) // Ex. expires in 60 Minutes
    ->setOwnerId($ownerId);

$tokenHandler = new FileHandler(sys_get_temp_dir(), $encrypter);
$tokenHandler->saveToken($token, $ownerId);
```

### Load Token
```php
use Illuminate\Encryption\Encrypter;
use Izzle\TokenHandler\Handler\FileHandler;

// Owner Id for this token. Must be unique. Ex. a User Id.
$ownerId = 'someOwnerId';

// Encrypter with random 16 Key for Cipher AES-128-CBC
// Cipher AES-256-CBC will need a 32 Char Key
$encrypter = new Encrypter('01234567890123456');

$tokenHandler = new FileHandler(sys_get_temp_dir(), $encrypter);
$token = $tokenHandler->loadToken($ownerId);
```

## License

The Lumen framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
