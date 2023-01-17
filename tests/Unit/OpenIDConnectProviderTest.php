<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Unit;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use JsonException;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\RegisteredClaims;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Grant\GrantFactory;
use League\OAuth2\Client\OptionProvider\PostAuthOptionProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\RequestFactory;
use OpenIDConnectClient\Exception\InvalidConfigurationException;
use OpenIDConnectClient\Exception\InvalidTokenException;
use OpenIDConnectClient\OpenIDConnectProvider;
use OpenIDConnectClient\Validator\ValidatorChain;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use stdClass;

final class OpenIDConnectProviderTest extends TestCase
{
    /**
     * {
     * "jti": "some jti",
     * "iss": "https://server.example.com",
     * "sub": "some subject",
     * "aud": "some audience",
     * "nonce": "some nonce",
     * "exp": 1636070123,
     * "iat": 1636069000,
     * "name": "Jane Doe",
     * "email": "janedoe@example.com"
     * }
     */
    // phpcs:ignore Generic.Files.LineLength.TooLong
    private const ID_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJzb21lIGp0aSIsImlzcyI6Imh0dHBzOi8vc2VydmVyLmV4YW1wbGUuY29tIiwic3ViIjoic29tZSBzdWJqZWN0IiwiYXVkIjoic29tZSBhdWRpZW5jZSIsIm5vbmNlIjoic29tZSBub25jZSIsImV4cCI6MTYzNjA3MDEyMywiaWF0IjoxNjM2MDY5MDAwLCJuYW1lIjoiSmFuZSBEb2UiLCJlbWFpbCI6ImphbmVkb2VAZXhhbXBsZS5jb20ifQ.0Q_NCJzfbBzhcwXkcDkwNfjEr3pvENC7zPkmK-IDEGQ';

    private OpenIDConnectProvider $provider;

    /** @var MockObject&Signer */
    private MockObject $signer;

    /** @var MockObject&GrantFactory */
    private MockObject $grantFactory;

    /** @var MockObject&RequestFactory */
    private MockObject $requestFactory;

    /** @var MockObject&HttpClient */
    private MockObject $httpClient;

    /** @var MockObject&PostAuthOptionProvider */
    private MockObject $optionProvider;

    /** @var MockObject&ValidatorChain */
    private MockObject $validatorChain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signer = $this->createMock(Signer::class);
        $this->grantFactory = $this->createMock(GrantFactory::class);
        $this->requestFactory = $this->createMock(RequestFactory::class);
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->optionProvider = $this->createMock(PostAuthOptionProvider::class);
        $this->validatorChain = $this->createMock(ValidatorChain::class);

        $this->provider = new OpenIDConnectProvider(
            [
                'clientId' => 'some clientId',
                'clientSecret' => 'some clientSecret',
                'redirectUri' => 'some redirectUri',
                'state' => 'some state',
                'urlAuthorize' => 'url-auth',
                'urlAccessToken' => 'url-accessToken',
                'urlResourceOwnerDetails' => 'url-resourceOwnerDetails',
                'publicKey' => ['some publicKey', 'some publicKey 2'],
                'idTokenIssuer' => 'some idTokenIssuer',
                'scopes' => ['scope_1', 'scope_2'],
            ],
            [
                'signer' => $this->signer,
                'grantFactory' => $this->grantFactory,
                'requestFactory' => $this->requestFactory,
                'httpClient' => $this->httpClient,
                'optionProvider' => $this->optionProvider,
                'validatorChain' => $this->validatorChain,
            ],
        );
    }

    /**
     * @dataProvider invalidConstructorArgumentsProvider
     */
    public function testConstructorWithInvalidArgumentsThrowsException(array $options, array $collaborators): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->provider = new OpenIDConnectProvider($options, $collaborators);
    }

    public function testPropertyGetters(): void
    {
        self::assertSame($this->grantFactory, $this->provider->getGrantFactory());
        self::assertSame($this->requestFactory, $this->provider->getRequestFactory());
        self::assertSame($this->httpClient, $this->provider->getHttpClient());
        self::assertSame($this->optionProvider, $this->provider->getOptionProvider());
        self::assertSame('url-auth', $this->provider->getBaseAuthorizationUrl());
        self::assertSame('url-accessToken', $this->provider->getBaseAccessTokenUrl([]));
        self::assertSame(
            'url-resourceOwnerDetails',
            $this->provider->getResourceOwnerDetailsUrl($this->createMock(AccessToken::class)),
        );
        self::assertSame(['scope_1', 'scope_2', 'openid'], $this->provider->getDefaultScopes());
        self::assertSame('some state', $this->provider->getState());

        $otherGrantFactory = $this->createMock(GrantFactory::class);
        $otherRequestFactory = $this->createMock(RequestFactory::class);
        $otherHttpClient = $this->createMock(HttpClient::class);
        $otherOptionProvider = $this->createMock(PostAuthOptionProvider::class);

        $this->provider->setGrantFactory($otherGrantFactory);
        $this->provider->setRequestFactory($otherRequestFactory);
        $this->provider->setHttpClient($otherHttpClient);
        $this->provider->setOptionProvider($otherOptionProvider);

        self::assertSame($otherGrantFactory, $this->provider->getGrantFactory());
        self::assertSame($otherRequestFactory, $this->provider->getRequestFactory());
        self::assertSame($otherHttpClient, $this->provider->getHttpClient());
        self::assertSame($otherOptionProvider, $this->provider->getOptionProvider());
    }

    public function testGetPublicKey(): void
    {
        $key = $this->provider->getPublicKey();
        self::assertSame('some publicKey', $key[0]->contents());
        self::assertSame('some publicKey 2', $key[1]->contents());
    }

    public function testGetAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();

        self::assertMatchesRegularExpression(
            '/url-auth\?state=[\da-f]+&scope=scope_1%20scope_2%20openid&response_type=code&approval_prompt=auto/',
            $url,
        );
    }

    /**
     * @throws JsonException
     */
    public function testGetAccessToken(): void
    {
        $grant = $this->createMock(AbstractGrant::class);
        $options = ['required-parameter' => 'some-value'];

        // AbstractProvider::verifyGrant
        $this->mockParentClassForAccessToken($grant, $options);

        // OpenIDConnectProvider::getAccessToken
        $this->signer
            ->expects(self::once())
            ->method('algorithmId')
            ->willReturn('HS256');

        $this->signer
            ->expects(self::once())
            ->method('verify')
            ->with(
                self::callback(static function (string $hash): bool {
                    return base64_encode($hash) === '0Q/NCJzfbBzhcwXkcDkwNfjEr3pvENC7zPkmK+IDEGQ=';
                }),
                self::identicalTo(implode('.', array_slice(explode('.', self::ID_TOKEN), 0, 2))),
                self::callback(static function (Key $key): bool {
                    return $key->contents() === 'some publicKey';
                }),
            )
            ->willReturn(true);

        $this->validatorChain
            ->expects(self::once())
            ->method('validate')
            ->with(
                self::callback(static function (array $data): bool {
                    self::assertSame('some idTokenIssuer', $data[RegisteredClaims::ISSUER]);
                    self::assertSame('some clientId', $data[RegisteredClaims::AUDIENCE]);
                    self::assertArrayHasKey(RegisteredClaims::EXPIRATION_TIME, $data);
                    self::assertArrayHasKey(RegisteredClaims::ISSUED_AT, $data);
                    self::assertArrayHasKey('auth_time', $data);
                    self::assertArrayHasKey('nbf', $data);

                    return true;
                }),
                self::isInstanceOf(Token::class),
            )
            ->willReturn(true);

        $this->provider->getAccessToken($grant, $options);
    }

    /**
     * @throws JsonException
     */
    public function testGetAccessTokenThrowsExceptionForInvalidKey(): void
    {
        $grant = $this->createMock(AbstractGrant::class);
        $options = ['required-parameter' => 'some-value'];

        // AbstractProvider::verifyGrant
        $this->mockParentClassForAccessToken($grant, $options);

        // OpenIDConnectProvider::getAccessToken
        $this->signer
            ->expects(self::exactly(2))
            ->method('algorithmId')
            ->willReturn('HS256');

        $this->signer
            ->expects(self::exactly(2))
            ->method('verify')
            ->willReturn(false);

        $this->validatorChain
            ->expects(self::never())
            ->method('validate');

        $this->expectException(InvalidTokenException::class);

        $this->provider->getAccessToken($grant, $options);
    }

    /**
     * @throws JsonException
     */
    public function testGetAccessTokenThrowsExceptionForInvalidChain(): void
    {
        $grant = $this->createMock(AbstractGrant::class);
        $options = ['required-parameter' => 'some-value'];

        // AbstractProvider::verifyGrant
        $this->mockParentClassForAccessToken($grant, $options);

        // OpenIDConnectProvider::getAccessToken
        $this->signer
            ->expects(self::once())
            ->method('algorithmId')
            ->willReturn('HS256');

        $this->signer
            ->expects(self::once())
            ->method('verify')
            ->willReturn(true);

        $this->validatorChain
            ->expects(self::once())
            ->method('validate')
            ->willReturn(false);

        $this->expectException(InvalidTokenException::class);

        $this->provider->getAccessToken($grant, $options);
    }

    public function testDefaultValidatorChain(): void
    {
        $this->provider = new OpenIDConnectProvider(
            [
                'clientId' => 'some clientId',
                'clientSecret' => 'some clientSecret',
                'redirectUri' => 'some redirectUri',
                'state' => 'some state',
                'urlAuthorize' => 'url-auth',
                'urlAccessToken' => 'url-accessToken',
                'urlResourceOwnerDetails' => 'url-resourceOwnerDetails',
                'publicKey' => ['some publicKey', 'some publicKey 2'],
                'idTokenIssuer' => 'some idTokenIssuer',
                'scopes' => ['scope_1', 'scope_2'],
            ],
            [
                'signer' => $this->signer,
                'grantFactory' => $this->grantFactory,
                'requestFactory' => $this->requestFactory,
                'httpClient' => $this->httpClient,
                'optionProvider' => $this->optionProvider,
            ],
        );

        $chain = $this->provider->getValidatorChain();

        self::assertTrue($chain->hasValidator('azp'));
        self::assertTrue($chain->hasValidator('nonce'));
        self::assertTrue($chain->hasValidator(RegisteredClaims::AUDIENCE));
        self::assertTrue($chain->hasValidator(RegisteredClaims::EXPIRATION_TIME));
        self::assertTrue($chain->hasValidator(RegisteredClaims::ID));
        self::assertTrue($chain->hasValidator(RegisteredClaims::ISSUED_AT));
        self::assertTrue($chain->hasValidator(RegisteredClaims::ISSUER));
        self::assertTrue($chain->hasValidator(RegisteredClaims::NOT_BEFORE));
        self::assertTrue($chain->hasValidator(RegisteredClaims::SUBJECT));
    }

    /**
     * @dataProvider invalidConfigurationDiscoveryResponsesProvider
     */
    public function testConfigurationDiscoveryInvalidResponse(string $responseBody, string $exceptionMessageRegex): void
    {
        $this->mockParentClassForConfigurationDiscovery($responseBody);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches($exceptionMessageRegex);
        $this->provider->discoverConfiguration("https://google.com/", []);
    }

    public function testConfigurationDiscoveryMissingScopes(): void
    {
        $this->mockParentClassForConfigurationDiscovery(json_encode([
            'issuer' => 'some issuer',
            'authorization_endpoint' => 'authz endpoint',
            'token_endpoint' => 'token endpoint',
            'scopes_supported' => [ 'supported' ]
        ]));

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Scope not supported is not supported .*/');
        $this->provider->discoverConfiguration("https://google.com", ['scopes' => ['supported', 'not supported']]);
    }

    public function invalidConfigurationDiscoveryResponsesProvider(): iterable
    {
        yield 'not JSON' => [
            'string',
            '/Invalid response received from discovery. Expected JSON./'
        ];
        yield 'not JSON array' => [
            json_encode('a'),
            '/Invalid response received from discovery. Expected JSON./'
        ];
        yield 'missing issuer' => [
            json_encode([
                //'issuer' => 'some issuer',
                'authorization_endpoint' => 'authz endpoint',
                'token_endpoint' => 'token endpoint',
                'userinfo_endpoint' => 'userinfo endpoint',
                'jwks_uri' => 'some uri'
            ]),
            '/Required parameter issuer.*/'
        ];
        yield 'missing authorization_endpoint' => [
            json_encode([
                'issuer' => 'some issuer',
                //'authorization_endpoint' => 'authz endpoint',
                'token_endpoint' => 'token endpoint',
                'userinfo_endpoint' => 'userinfo endpoint',
                'jwks_uri' => 'some uri'
            ]),
            '/Required parameter authorization_endpoint.*/'
        ];
        yield 'missing token_endpoint' => [
            json_encode([
                'issuer' => 'some issuer',
                'authorization_endpoint' => 'authz endpoint',
                //'token_endpoint' => 'token endpoint',
                'userinfo_endpoint' => 'userinfo endpoint',
                'jwks_uri' => 'some uri'
            ]),
            '/Required parameter token_endpoint.*/'
        ];
        yield 'missing jwks_uri' => [
            json_encode([
                'issuer' => 'some issuer',
                'authorization_endpoint' => 'authz endpoint',
                'token_endpoint' => 'token endpoint',
                'userinfo_endpoint' => 'userinfo endpoint',
                //'jwks_uri' => 'some uri'
            ]),
            '/Required parameter jwks_uri.*/'
        ];
    }

    public function invalidConstructorArgumentsProvider(): iterable
    {
        $signer = $this->createMock(Signer::class);

        yield 'signer is missing' => [
            [
                'urlAuthorize' => 'url-auth',
                'urlAccessToken' => 'some urlAccessToken',
                'urlResourceOwnerDetails' => 'some urlResourceOwnerDetails',
                'publicKey' => ['some publicKey', 'some publicKey 2'],
                'idTokenIssuer' => 'some idTokenIssuer',
            ],
            [
                //'signer' => $this->signer,
            ],
        ];

        yield 'signer is wrong type' => [
            [
                'urlAuthorize' => 'url-auth',
                'urlAccessToken' => 'some urlAccessToken',
                'urlResourceOwnerDetails' => 'some urlResourceOwnerDetails',
                'publicKey' => ['some publicKey', 'some publicKey 2'],
                'idTokenIssuer' => 'some idTokenIssuer',
            ],
            [
                'signer' => new stdClass(),
            ],
        ];

        yield 'urlAuthorize is missing' => [
            [
                //'urlAuthorize' => 'url-auth',
                'urlAccessToken' => 'some urlAccessToken',
                'urlResourceOwnerDetails' => 'some urlResourceOwnerDetails',
                'publicKey' => ['some publicKey', 'some publicKey 2'],
                'idTokenIssuer' => 'some idTokenIssuer',
            ],
            [
                'signer' => $signer,
            ],
        ];

        yield 'urlAccessToken is missing' => [
            [
                'urlAuthorize' => 'url-auth',
                //'urlAccessToken' => 'some urlAccessToken',
                'urlResourceOwnerDetails' => 'some urlResourceOwnerDetails',
                'publicKey' => ['some publicKey', 'some publicKey 2'],
                'idTokenIssuer' => 'some idTokenIssuer',
            ],
            [
                'signer' => $signer,
            ],
        ];

        yield 'urlResourceOwnerDetails is missing' => [
            [
                'urlAuthorize' => 'url-auth',
                'urlAccessToken' => 'some urlAccessToken',
                //'urlResourceOwnerDetails' => 'some urlResourceOwnerDetails',
                'publicKey' => ['some publicKey', 'some publicKey 2'],
                'idTokenIssuer' => 'some idTokenIssuer',
            ],
            [
                'signer' => $signer,
            ],
        ];

        yield 'publicKey is missing' => [
            [
                'urlAuthorize' => 'url-auth',
                'urlAccessToken' => 'some urlAccessToken',
                'urlResourceOwnerDetails' => 'some urlResourceOwnerDetails',
                //'publicKey' => ['some publicKey', 'some publicKey 2'],
                'idTokenIssuer' => 'some idTokenIssuer',
            ],
            [
                'signer' => $signer,
            ],
        ];

        yield 'idTokenIssuer is missing' => [
            [
                //'urlAuthorize' => 'url-auth',
                'urlAccessToken' => 'some urlAccessToken',
                'urlResourceOwnerDetails' => 'some urlResourceOwnerDetails',
                'publicKey' => ['some publicKey', 'some publicKey 2'],
                //'idTokenIssuer' => 'some idTokenIssuer',
            ],
            [
                'signer' => $signer,
            ],
        ];
    }

    private function mockParentClassForConfigurationDiscovery(string $responseBody): void
    {
        $request = $this->createMock(Request::class);
        $this->requestFactory
            ->expects(self::once())
            ->method('getRequestWithOptions')
            ->willReturn($request);

        // AbstractProvider::getParsedResponse
        $response = $this->createMock(ResponseInterface::class);
        $this->httpClient
            ->expects(self::once())
            ->method('send')
            ->willReturn($response);

        $response
            ->expects(self::once())
            ->method('getBody')
            ->willReturn($responseBody);
    }

    /**
     * @throws JsonException
     */
    private function mockParentClassForAccessToken(MockObject $grant, array $options): void
    {
        $this->grantFactory
            ->expects(self::once())
            ->method('checkGrant')
            ->with(self::identicalTo($grant));

        $params = [
            'client_id' => 'some clientId',
            'client_secret' => 'some clientSecret',
            'redirect_uri' => 'some redirectUri',
        ];

        $newParams = [
            'client_id' => 'some clientId',
            'client_secret' => 'some clientSecret',
            'redirect_uri' => 'some redirectUri',
            'grant_type' => 'authorization_code',
        ];

        // AbstractProvider::getAccessToken
        $grant
            ->expects(self::once())
            ->method('prepareRequestParameters')
            ->with(self::identicalTo($params), self::identicalTo($options))
            ->willReturn($newParams);

        $newOptions = [
            'headers' => ['header_key_1' => 'header_value_1', 'header_key_2' => 'header_value_2'],
            'key_1' => 'value_1',
            'key_2' => 'value_2',
            'body' => ['body_key_1' => 'body_value_1', 'body_key_2' => 'body_value_2'],
        ];

        // AbstractProvider::getAccessTokenRequest
        $this->optionProvider
            ->expects(self::once())
            ->method('getAccessTokenOptions')
            ->with(self::identicalTo('POST'), self::identicalTo($newParams))
            ->willReturn($newOptions);

        $request = $this->createMock(Request::class);
        $this->requestFactory
            ->expects(self::once())
            ->method('getRequestWithOptions')
            ->with(self::identicalTo('POST'), self::identicalTo('url-accessToken'), $newOptions)
            ->willReturn($request);

        // AbstractProvider::getParsedResponse
        $response = $this->createMock(ResponseInterface::class);
        $this->httpClient
            ->expects(self::once())
            ->method('send')
            ->with(self::identicalTo($request))
            ->willReturn($response);

        $responseBody = json_encode(
            ['access_token' => 'some access-token', 'id_token' => self::ID_TOKEN],
            JSON_THROW_ON_ERROR,
        );
        $response
            ->expects(self::once())
            ->method('getBody')
            ->willReturn($responseBody);

        $response
            ->expects(self::once())
            ->method('getHeader')
            ->with(self::identicalTo('content-type'))
            ->willReturn('some content-type');
    }
}
