# Phalcon JWT
JWT session drop-in for Phalcon 2.

## Installation

## Usage

### Loading the JWT session service
```php
$jwtKey = 'c8cb6ae1fb193e1e9d3d2d6553479755bbe59e34e2b965629ee4346e4c4902646c93ccd6cd7fd6d2392f300d251632e64bf1a1c260adf1b7219e8caa6dc7d27e';
$di = new FactoryDefault();

// Load the Jwt session
$di->setShared('session', function () use ($config) {
    $session = new Jwt(['key' => $jwtKey]);
    $session->start();

    return $session;
});
```

### Starting a new session
```php
// In your login controller/action
$session = $this->session;
$session->set('sub', $userId);
$session->write();
```

### Accessing an active session via the user's JWT cookie
```php
// Usually in a security plugin
if ($sub = $this->session->get('sub')) {
    if ($user = Users::findFirstById($sub)) {
        $this->getDi()->setShared('user', $user);
    } else {
        $this->getDi()->getShared('session')->destroy();
    }
}
```

### Ending the session
```php
// Logging the user out
$this->session->destroy();
```

## Generating a secret key

Easily done from a PHP prompt.

```
php -a
echo bin2hex(openssl_random_pseudo_bytes(64));
c8cb6ae1fb193e1e9d3d2d6553479755bbe59e34e2b965629ee4346e4c4902646c93ccd6cd7fd6d2392f300d251632e64bf1a1c260adf1b7219e8caa6dc7d27e
```

Then in your code:
```php
// In the real world, this would go in your application configuration.
$jwtKey = 'c8cb6ae1fb193e1e9d3d2d6553479755bbe59e34e2b965629ee4346e4c4902646c93ccd6cd7fd6d2392f300d251632e64bf1a1c260adf1b7219e8caa6dc7d27e';
```

## About JWTs
Phalcon JWT uses the Firebase JWT library. To learn more about it and JSON Web Tokens in general, visit:
https://github.com/firebase/php-jwt
