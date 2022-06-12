<?php

declare(strict_types=1);

namespace OpenIDConnectClient;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token\Plain;
use League\OAuth2\Client\Token\AccessToken as LeagueAccessToken;
use OpenIDConnectClient\Exception\InvalidTokenException;

final class AccessToken extends LeagueAccessToken
{
    private ?Plain $idToken;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->idToken = null;

        if (isset($this->values['id_token'])) {
            // Signature is validated outside, this just parses the token
            $token = Configuration::forUnsecuredSigner()
                ->parser()
                ->parse($this->values['id_token']);

            if (!$token instanceof Plain) {
                throw new InvalidTokenException('Received wrong token type (expected Plain)');
            }
            $this->idToken = $token;

            unset($this->values['id_token']);
        }
    }

    public function getIdToken(): ?Plain
    {
        return $this->idToken ?? null;
    }

    public function jsonSerialize(): array
    {
        $parameters = parent::jsonSerialize();
        if (isset($this->idToken)) {
            $parameters['id_token'] = $this->idToken->toString();
        }

        return $parameters;
    }
}
