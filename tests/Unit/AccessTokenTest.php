<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Lcobucci\JWT\Token\RegisteredClaims;
use OpenIDConnectClient\AccessToken;
use PHPUnit\Framework\TestCase;

final class AccessTokenTest extends TestCase
{
    private const TEST_TIME = 1636070000;

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

    private const DEFAULT_ARGUMENTS = [
        'access_token' => 'some access token',
        'resource_owner_id' => 'some resource_owner_id',
        'refresh_token' => 'some refresh_token',
        'expires_in' => 123,
        'id_token' => self::ID_TOKEN,
        'random_key_123' => 'some random value',
    ];

    private AccessToken $token;

    protected function setUp(): void
    {
        parent::setUp();

        AccessToken::setTimeNow(self::TEST_TIME);
        $this->token = new AccessToken(self::DEFAULT_ARGUMENTS);
    }

    public function testBareConstructor(): void
    {
        $token = new AccessToken(['access_token' => 'something']);
        self::assertSame('something', (string)$token);
        self::assertNull($token->getIdToken());
        self::assertSame(['access_token' => 'something'], $token->jsonSerialize());
    }

    public function testConstructorWithInvalidAccessTokenThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AccessToken([]);
    }

    public function testConstructorWithInvalidExpiresInThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AccessToken(['access_token' => 'something', 'expires_in' => 'invalid']);
    }

    public function testConstructorWithInvalidIdTokenThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AccessToken(['access_token' => 'something', 'id_token' => 'invalid']);
    }

    public function testDefaultGettersWithValidValues(): void
    {
        self::assertSame(self::DEFAULT_ARGUMENTS['access_token'], $this->token->getToken());
        self::assertSame(self::DEFAULT_ARGUMENTS['access_token'], (string)$this->token);
        self::assertSame(self::DEFAULT_ARGUMENTS['resource_owner_id'], $this->token->getResourceOwnerId());
        self::assertSame(self::DEFAULT_ARGUMENTS['refresh_token'], $this->token->getRefreshToken());
        self::assertSame(self::TEST_TIME + self::DEFAULT_ARGUMENTS['expires_in'], $this->token->getExpires());
        self::assertSame(['random_key_123' => self::DEFAULT_ARGUMENTS['random_key_123']], $this->token->getValues());
    }

    public function testJsonSerializeResponse(): void
    {
        $expectedSerializedResponse = self::DEFAULT_ARGUMENTS;
        $expectedSerializedResponse['expires'] = self::TEST_TIME + self::DEFAULT_ARGUMENTS['expires_in'];
        unset($expectedSerializedResponse['expires_in']);
        $actualSerializedResponse = $this->token->jsonSerialize();
        ksort($expectedSerializedResponse);
        ksort($actualSerializedResponse);
        self::assertSame($expectedSerializedResponse, $actualSerializedResponse);
    }

    public function testGetIdToken(): void
    {
        $idToken = $this->token->getIdToken();

        self::assertNotNull($idToken);
        self::assertSame(self::ID_TOKEN, $idToken->toString());

        $claims = $idToken->claims();
        self::assertSame('some jti', $claims->get(RegisteredClaims::ID));
        self::assertSame('https://server.example.com', $claims->get(RegisteredClaims::ISSUER));
        self::assertSame('some subject', $claims->get(RegisteredClaims::SUBJECT));
        self::assertSame(['some audience'], $claims->get(RegisteredClaims::AUDIENCE));
        self::assertSame(self::TEST_TIME + 123, $claims->get(RegisteredClaims::EXPIRATION_TIME)->getTimestamp());
        self::assertSame('some nonce', $claims->get('nonce'));
        self::assertSame('Jane Doe', $claims->get('name'));
        self::assertSame('janedoe@example.com', $claims->get('email'));
        self::assertFalse($claims->has('unknown'));
        self::assertNull($claims->get('unknown'));

        $dateTimeZone = new DateTimeZone('Etc/Greenwich');
        self::assertFalse($idToken->isExpired(new DateTimeImmutable('@' . (self::TEST_TIME + 1), $dateTimeZone)));
        self::assertFalse($idToken->isExpired(new DateTimeImmutable('@' . (self::TEST_TIME + 122), $dateTimeZone)));
        self::assertTrue($idToken->isExpired(new DateTimeImmutable('@' . (self::TEST_TIME + 123), $dateTimeZone)));
        self::assertTrue($idToken->isExpired(new DateTimeImmutable('@' . (self::TEST_TIME + 124), $dateTimeZone)));

        self::assertTrue($idToken->isPermittedFor('some audience'));
        self::assertTrue($idToken->isIdentifiedBy('some jti'));
        self::assertFalse($idToken->isIdentifiedBy('some other jti'));
        self::assertTrue($idToken->hasBeenIssuedBy('https://server.example.com'));
    }
}
