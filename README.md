# OAuth 2.0 OpenID Connect Client

This package uses the PHP League's [OAuth2 Client](https://github.com/thephpleague/oauth2-client) and this [JWT Token Library](https://github.com/lcobucci/jwt) to provide an OAuth2 OpenID Connect client.

## Requirements

The following versions of PHP are supported.

* PHP 7.4
* PHP 8.0
* PHP 8.1

## Usage
You may test your OpenID Connect Client against [bshaffer's demo oauth2 server](https://github.com/bshaffer/oauth2-demo-php).
```php
<?php
$signer   = new \Lcobucci\JWT\Signer\Rsa\Sha256();
$provider = new \OpenIDConnectClient\OpenIDConnectProvider([
        'clientId'                => 'demoapp',
        'clientSecret'            => 'demopass',
        // the issuer of the identity token (id_token) this will be compared with what is returned in the token.
        'idTokenIssuer'           => 'brentertainment.com',
        // Your server
        'redirectUri'             => 'http://example.com/your-redirect-url/',
        'urlAuthorize'            => 'http://brentertainment.com/oauth2/lockdin/authorize',
        'urlAccessToken'          => 'http://brentertainment.com/oauth2/lockdin/token',
        'urlResourceOwnerDetails' => 'http://brentertainment.com/oauth2/lockdin/resource',
        // Find the public key here: https://github.com/bshaffer/oauth2-demo-php/blob/master/data/pubkey.pem
        // to test against brentertainment.com
        'publicKey'                 => 'file:///myproj/data/public.key',
    ],
    [
        'signer' => $signer
    ]
);

// send the authorization request
if (empty($_GET['code'])) {
    $redirectUrl = $provider->getAuthorizationUrl();
    header(sprintf('Location: %s', $redirectUrl), true, 302);
    return;
}

// receive authorization response
try {
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
} catch (\OpenIDConnectClient\Exception\InvalidTokenException $e) {
    $errors = $provider->getValidatorChain()->getMessages();
    return;
}

$accessToken    = $token->getToken();
$refreshToken   = $token->getRefreshToken();
$expires        = $token->getExpires();
$hasExpired     = $token->hasExpired();
$idToken        = $token->getIdToken();
$email          = $idToken->claims()->get('email', false);
$allClaims      = $idToken->claims();

```

### Run the Example
An example client has been provided and can be found in the /example directory of this repository.  To run the example you can utilize PHPs built-in web server.
```bash
$ php -S localhost:8081 client.php
```
Then open this link: [http://localhost:8081/](http://localhost:8081/)

This should send you to bshaffer's OAuth2 Live OpenID Connect Demo site.

### Token Verification
The id_token is verified using the lcobucci/jwt library.  You will need to pass the appropriate signer and publicKey to the OpenIdConnectProvider.


## Install

Via Composer

``` bash
$ composer require steverhoades/oauth2-openid-connect-client
```

## Clock difference tolerance in nbf

Some clock difference can be tolerated between the IdP and the SP by using the `nbfToleranceSeconds` option in the
`getAccessToken` method call.

```php
<?php

...
// receive authorization response
try {
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code'],
        //adds 60 seconds to currentTime to tolerate 1 minute difference in clocks between IdP and SP
        'nbfToleranceSeconds' => 60
    ]);
} catch (\OpenIDConnectClient\Exception\InvalidTokenException $e) {
    $errors = $provider->getValidatorChain()->getMessages();
    return;
}

```


## License

The MIT License (MIT). Please see [License File](https://github.com/steverhoades/oauth2-openid-connect-client/blob/master/LICENSE) for more information.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

## TODO
- [ ] add support for OpenID Connect [Authentication Request Parameters](http://openid.net/specs/openid-connect-core-1_0.html#AuthRequest)
- [x] add tests
- [ ] check implicit and hybrid flow support
- [x] example endpoints showing usage
