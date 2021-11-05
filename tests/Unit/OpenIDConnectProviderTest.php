<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Unit;

use InvalidArgumentException;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token\RegisteredClaims;
use OpenIDConnectClient\OpenIDConnectProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

final class OpenIDConnectProviderTest extends TestCase
{
    private OpenIDConnectProvider $provider;

    /** @var MockObject&Signer */
    private MockObject $signer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signer = $this->createMock(Signer::class);
        $this->provider = new OpenIDConnectProvider(
            [
                'urlAuthorize' => 'url-auth',
                'urlAccessToken' => 'some urlAccessToken',
                'urlResourceOwnerDetails' => 'some urlResourceOwnerDetails',
                'publicKey' => ['some publicKey', 'some publicKey 2'],
                'idTokenIssuer' => 'some idTokenIssuer',
                'scopes' => ['scope_1', 'scope_2'],
            ],
            [
                'signer' => $this->signer,
            ],
        );
    }

    /**
     * @dataProvider invalidConstructorArgumentsProvider
     */
    public function testConstructorWithoutSignerThrowsException(array $options, array $collaborators): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->provider = new OpenIDConnectProvider($options, $collaborators);
    }

    public function testGetPublicKey(): void
    {
        $key = $this->provider->getPublicKey();
        self::assertSame('some publicKey', $key[0]->contents());
        self::assertSame('some publicKey 2', $key[1]->contents());
    }

    public function testGetAccessToken(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function testGetValidatorChain(): void
    {
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

    public function testGetAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();

        self::assertMatchesRegularExpression(
            '/url-auth\?state=[\da-f]+&scope=scope_1%20scope_2%20openid&response_type=code&approval_prompt=auto/',
            $url,
        );
    }

    public function invalidConstructorArgumentsProvider(): iterable
    {
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
                'signer' => new stdClass(),
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
                'signer' => new stdClass(),
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
                'signer' => new stdClass(),
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
                'signer' => new stdClass(),
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
                'signer' => new stdClass(),
            ],
        ];
    }
}
