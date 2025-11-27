# CodeAuth PHP SDK

Offical CodeAuth SDK. For more info, check the docs on the [official website](https://docs.codeauth.com).

## Install
`composer require codeauth/codeauth-sdk`

## Sample Usage
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use CodeAuth\CodeAuth;

// Initialize SDK
CodeAuth::Initialize("<your project endpoint>", "<your project id>");

// Send code
$r1 = CodeAuth::SignInEmail("<user email>");
print_r($r1);
```


