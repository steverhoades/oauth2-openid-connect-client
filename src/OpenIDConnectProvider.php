<?php

declare(strict_types=1);

namespace OpenIDConnectClient;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\GenericProvider;
use OpenIDConnectClient\Exception\InvalidTokenException;
use OpenIDConnectClient\Validator\EqualsTo;
use OpenIDConnectClient\Validator\EqualsToOrContains;
use OpenIDConnectClient\Validator\GreaterOrEqualsTo;
use OpenIDConnectClient\Validator\LesserOrEqualsTo;
use OpenIDConnectClient\Validator\NotEmpty;
use OpenIDConnectClient\Validator\ValidatorChain;
use Webmozart\Assert\Assert;

final class OpenIDConnectProvider extends GenericProvider
{
    private ValidatorChain $validatorChain;
    private Signer $signer;

    /** @var string|array<string> */
    protected $publicKey;
    protected string $idTokenIssuer;

    /**
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        Assert::keyExists($collaborators, 'signer');
        Assert::isInstanceOf($collaborators['signer'], Signer::class);

        $this->signer = $collaborators['signer'];

        if (!isset($collaborators['validatorChain'])) {
            $collaborators['validatorChain'] = new ValidatorChain();
        }
        $this->validatorChain = $collaborators['validatorChain'];
        $this->validatorChain->setValidators(
            [
                new NotEmpty('iat', true),
                new GreaterOrEqualsTo('exp', true),
                new EqualsTo('iss', true),
                new EqualsToOrContains('aud', true),
                new NotEmpty('sub', true),
                new LesserOrEqualsTo('nbf'),
                new EqualsTo('jti'),
                new EqualsTo('azp'),
                new EqualsTo('nonce'),
            ],
        );

        if (empty($options['scopes'])) {
            $options['scopes'] = [];
        } elseif (!is_array($options['scopes'])) {
            $options['scopes'] = [$options['scopes']];
        }

        if (!in_array('openid', $options['scopes'])) {
            $options['scopes'][] = 'openid';
        }

        parent::__construct($options, $collaborators);
    }

    /**
     * Returns all options that are required.
     *
     * @return array<string>
     */
    protected function getRequiredOptions(): array
    {
        $options = parent::getRequiredOptions();
        $options[] = 'publicKey';
        $options[] = 'idTokenIssuer';

        return $options;
    }

    /**
     * @return Key[]
     */
    public function getPublicKey(): array
    {
        if (is_array($this->publicKey)) {
            $self = $this;
            return array_map(
                static function (string $key) use ($self): Key {
                    return $self->constructKey($key);
                },
                $this->publicKey,
            );
        }

        return [$this->constructKey($this->publicKey)];
    }

    private function constructKey(string $content): Key
    {
        if (strpos($content, 'file://') === 0) {
            return InMemory::file($content);
        }

        return InMemory::plainText($content);
    }

    /**
     * Requests an access token using a specified grant and option set.
     *
     * @param mixed $grant
     * @param array $options
     * @return AccessToken
     */
    public function getAccessToken($grant, array $options = [])
    {
        $accessToken = parent::getAccessToken($grant, $options);
        if (!$accessToken instanceof AccessToken) {
            throw new InvalidTokenException('Received wrong access token type');
        }

        $token = $accessToken->getIdToken();

        // id_token is empty.
        if ($token === null) {
            $message = 'Expected an id_token but did not receive one from the authorization server.';
            throw new InvalidTokenException($message);
        }

        // If the ID Token is received via direct communication between the Client and the Token Endpoint
        // (which it is in this flow), the TLS server validation MAY be used to validate the issuer in place of checking
        // the token signature. The Client MUST validate the signature of all other ID Tokens according to JWS [JWS]
        // using the algorithm specified in the JWT alg Header Parameter. The Client MUST use the keys provided by
        // the Issuer.
        //
        // The alg value SHOULD be the default of RS256 or the algorithm sent by the Client in the
        // id_token_signed_response_alg parameter during Registration.
        $verified = false;
        foreach ($this->getPublicKey() as $key) {
            if ($this->validateSignature($token, $key) !== false) {
                $verified = true;
                break;
            }
        }

        if (!$verified) {
            $message = 'Received an invalid id_token from authorization server.';
            throw new InvalidTokenException($message);
        }

        // validations
        // @see http://openid.net/specs/openid-connect-core-1_0.html#IDTokenValidation
        // validate the iss (issuer)
        // - The Issuer Identifier for the OpenID Provider (which is typically obtained during Discovery)
        // MUST exactly match the value of the iss (issuer) Claim.
        // validate the aud
        // - The Client MUST validate that the aud/audience Claim contains its client_id value registered at the Issuer
        // identified by the iss (issuer) Claim as an audience. The aud (audience) Claim MAY contain an array with more
        // than one element. The ID Token MUST be rejected if the ID Token does not list the Client as a valid audience,
        // or if it contains additional audiences not trusted by the Client.
        // - If a nonce value was sent in the Authentication Request, a nonce Claim MUST be present and its value
        // checked to verify that it is the same value as the one that was sent in the Authentication Request.
        // The Client SHOULD check the nonce value for replay attacks.
        // The precise method for detecting replay attacks is Client specific.
        // - If the auth_time Claim was requested, either through a specific request for this Claim or by using
        // the max_age parameter, the Client SHOULD check the auth_time Claim value and request re-authentication if it
        // determines too much time has elapsed since the last End-User authentication.
        // - The nbf time should be in the future. An option of nbfToleranceSeconds can be sent and it will be added to
        // the currentTime in order to accept some difference in clocks
        // TODO
        // If the acr Claim was requested, the Client SHOULD check that the asserted Claim Value is appropriate.
        // The meaning and processing of acr Claim Values is out of scope for this specification.
        $currentTime = time();
        $nbfToleranceSeconds = isset($options['nbfToleranceSeconds']) ? (int)$options['nbfToleranceSeconds'] : 0;
        $data = [
            'iss' => $this->getIdTokenIssuer(),
            'exp' => $currentTime,
            'auth_time' => $currentTime,
            'iat' => $currentTime,
            'nbf' => $currentTime + $nbfToleranceSeconds,
            'aud' => $this->clientId,
        ];

        // If the ID Token contains multiple audiences, the Client SHOULD verify that an azp Claim is present.
        // If an azp (authorized party) Claim is present,
        // the Client SHOULD verify that its client_id is the Claim Value.
        if ($token->claims()->has('azp')) {
            $data['azp'] = $this->clientId;
        }

        if ($this->validatorChain->validate($data, $token) === false) {
            throw new InvalidTokenException('The id_token did not pass validation.');
        }

        return $accessToken;
    }

    protected function validateSignature(Token $token, Key $key): bool
    {
        $validatorConfig = Configuration::forAsymmetricSigner($this->signer, $key, $key);
        $validatorConfig->setValidationConstraints(new SignedWith($this->signer, $key));
        $validator = $validatorConfig->validator();

        return $validator->validate($token, ...$validatorConfig->validationConstraints());
    }

    /**
     * Overload parent as OpenID Connect specification states scopes shall be separated by spaces
     */
    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    public function getValidatorChain(): ValidatorChain
    {
        return $this->validatorChain;
    }

    /**
     * Get the issuer of the OpenID Connect id_token
     */
    protected function getIdTokenIssuer(): string
    {
        return $this->idTokenIssuer;
    }

    /**
     * Creates an access token from a response.
     *
     * The grant that was used to fetch the response can be used to provide
     * additional context.
     *
     * @param array $response
     */
    protected function createAccessToken(array $response, AbstractGrant $grant): AccessToken
    {
        return new AccessToken($response);
    }
}
