<?php

declare(strict_types=1);

namespace OpenIDConnectClient;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser;
use League\OAuth2\Client\Token\AccessToken as LeagueAccessToken;

final class AccessToken extends LeagueAccessToken
{
    private Token $idToken;

    public function __construct($options = [])
    {
        parent::__construct($options);

        if (!empty($this->values['id_token'])) {
            $this->idToken = (new Parser())->parse($this->values['id_token']);
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
        if ($this->idToken) {
            $parameters['id_token'] = $this->idToken->toString();
        }

        return $parameters;
    }
}
