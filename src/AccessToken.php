<?php

declare(strict_types=1);

namespace OpenIDConnectClient;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;
use League\OAuth2\Client\Token\AccessToken as LeagueAccessToken;

final class AccessToken extends LeagueAccessToken
{
    private Token $idToken;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (isset($this->values['id_token'])) {
            // Signature is validated outside, this just parses the token
            $this->idToken = Configuration::forUnsecuredSigner()
                ->parser()
                ->parse($this->values['id_token']);
            unset($this->values['id_token']);
        }
    }

    public function getIdToken(): ?Token
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
