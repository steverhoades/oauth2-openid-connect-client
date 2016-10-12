<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/12/16
 * Time: 12:33 PM
 */

namespace OpenIDConnectClient\Validator;


class NotEmpty implements ValidatorInterface
{
    use ValidatorTrait;

    public function isValid($expectedValue, $actualValue)
    {
        $valid = !empty($actualValue);
        if (!$valid) {
            $this->message = sprintf("%s is required and cannot be empty", $this->getName());
        }

        return $valid;
    }
}
