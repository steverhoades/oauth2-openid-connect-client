<?php
/**
 * @author Steve Rhoades <sedonami@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace OpenIDConnectClient\Validator;

use Lcobucci\JWT\Token;

class ValidatorChain
{
    /**
     * @var array
     */
    protected $validators = [];

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @param ValidatorInterface[] $validators
     * @return $this
     */
    public function setValidators(array $validators)
    {
        $this->validators = [];

        foreach ($validators as $validator) {
            $this->addValidator($validator);
        }

        return $this;
    }

    /**
     * @param string $claim
     * @param ValidatorInterface $validator
     * @return $this
     */
    public function addValidator(ValidatorInterface $validator)
    {
        $this->validators[$validator->getName()] = $validator;

        return $this;
    }

    /**
     * @param array $data
     * @param Token $token
     * @return bool
     */
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

    /**
     * @param $name
     * @return bool
     */
    public function hasValidator($name)
    {
        return !empty($this->validators[$name]);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getValidator($name)
    {
        return $this->validators[$name];
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
