<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/11/16
 * Time: 1:19 PM
 */

namespace OpenIDConnectClient\Validator;


class GreaterOrEqualsTo implements ValidatorInterface
{
    use ValidatorTrait;

    public function isValid($expectedValue, $actualValue)
    {
        if ($actualValue >= $expectedValue) {
            return true;
        }

        $this->message = sprintf("%s is invalid as it is not greater than %s", $actualValue, $expectedValue);
        return false;
    }
}
