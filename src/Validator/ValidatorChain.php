<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/11/16
 * Time: 1:07 PM
 */

namespace OpenIDConnectClient\Validator;

use Lcobucci\JWT\Token;

class ValidatorChain
{
    protected $validators;

    protected $messages = [];

    public function setValidators($validators)
    {
        $this->validators = $validators;
    }

    public function addValidator($claim, $validator)
    {
        $this->validators[$claim] = $validator;
    }

    public function isValid(array $data, Token $token)
    {
        $valid = true;
        foreach ($data as $claim => $claimValue) {
            if (!$token->hasClaim($claim) || !$this->hasValidator($claim)) {
                continue;
            }

            $validator = $this->getValidator($claim);
            if (!$validator->isValid($claimValue, $token->getClaim($claim))) {
                $valid = false;
                $this->messages[] = $validator->getMessage();
            }
        }

        return $valid;
    }

    public function hasValidator($name)
    {
        return !empty($this->validators[$name]);
    }

    public function getValidator($name)
    {
        return $this->validators[$name];
    }

    public function getMessages()
    {
        return $this->messages;
    }
}
