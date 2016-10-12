<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/10/16
 * Time: 2:56 PM
 */

namespace OpenIDConnectClient;

use Lcobucci\JWT\Parser;

class AccessToken extends \League\OAuth2\Client\Token\AccessToken
{
    protected $idToken;

    public function __construct($options = [])
    {
        parent::__construct($options);

        if (!empty($this->values['id_token'])) {
            $this->idToken = (new Parser())->parse($this->values['id_token']);
            unset($this->values['id_token']);
        }
    }

    public function getIdToken()
    {
        return $this->idToken;
    }
}
