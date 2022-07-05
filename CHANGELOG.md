CHANGELOG
=========

This changelog references the relevant changes (bug and security fixes)

* 0.4.3 (2022-07-05)

    * change [All] Added PHP 8.1 compatibility

* 0.4.2 (2021-08-09)

    * change [Handler] Added additional checkDatabase Parameter in PdoHandler

* 0.4.1 (2021-08-04)

    * change [Handler] Unnecessary row check at checkDatabase has been removed from PdoHandler

* 0.4.0 (2021-04-23)

    * feature [Token] / [TokenInterface] Token and Tokeninterface can now hold meta information

* 0.3.0 (2021-04-19)

    * change [Token] / [TokenInterface] Methods setRefreshToken and setExpires can now be null
    * change [Token] / [TokenInterface] Owner Id cannot be empty ('', '0')

* 0.2.1 (2021-04-14)

    * change [TokenInterface] Removed getValues method
    * change [TokenInterface] Added toArray

* 0.2.0 (2021-04-14)

    * feature [TokenInterface] Added additional getValues method
    * change [TokenInterface] Implements JsonSerializable and Serializable

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
