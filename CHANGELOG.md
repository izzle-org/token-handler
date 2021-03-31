CHANGELOG
=========

This changelog references the relevant changes (bug and security fixes)

* 0.1.1 (2021-03-31)

    * feature [Handler] Added NullHandler
    * change [FileHandler] Hardened FileHandler in case of empty token files

* 0.1.0 (2021-01-14)

    * feature [Global] Added PHP 8.0 support

* 0.0.4 (2020-10-28)

    * feature [PdoHandler] The Pdo handler will now throw more PDO Exceptions on error

* 0.0.3 (2020-10-28)

    * bug [FileHandler] The File handler will now load expired tokens correctly
    * feature [PdoHandler] The Pdo handler will now throw PDO Exceptions on error

* 0.0.2 (2020-10-27)

    * feature [Token] Token property 'expires' can now be null (never expires)

* 0.0.1 (2020-10-27)
    
    * feature [Global] Added alpha version
