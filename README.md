# OAuth 2.0 OpenID Connect Client

This an experimental package that attempts to provide an OpenID Connect client.  The library sits on top of The PHP League OAuth2 Client and leverages the JWT Token Library available from lcobucci/jwt to handle id_token verification.

## Requirements

The following versions of PHP are supported.

* PHP 5.5
* PHP 5.6
* PHP 7.0

## Usage
```php
        $signer = new Sha256();

        return new OpenIdConnectProvider([
                'clientId'                => 'demoapp',    // The client ID assigned to you by the provider
                'clientSecret'            => 'demopass',   // The client password assigned to you by the provider
                'redirectUri'             => 'http://example.com/your-redirect-url/',
                'urlAuthorize'            => 'http://brentertainment.com/oauth2/lockdin/authorize',
                'urlAccessToken'          => 'http://brentertainment.com/oauth2/lockdin/token',
                'urlResourceOwnerDetails' => 'http://brentertainment.com/oauth2/lockdin/resource',
                'publicKey'                 => 'file://' . UNITY_INSTALL_ROOT . '/unity_applications/unity/config/public.key',
            ],
            [
                'signer' => $signer
            ]
        );
```

### Token Verification
The id_token is verified using the lcobucci/jwt library.  You will need to pass the appropriate signer and publicKey to the OpenIdConnectProvider.


## Install

Via Composer

``` bash
$ composer require steverhoades/oauth2-openid-connect-client
```

## License

The MIT License (MIT). Please see [License File](https://github.com/steverhoades/oauth2-openid-connect-client/blob/master/LICENSE) for more information.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

## TODO
- [ ] add support for OpenID Connect [Authentication Request Parameters](http://openid.net/specs/openid-connect-core-1_0.html#AuthRequest)
- [ ] add tests
- [ ] check implicit and hybrid flow support
