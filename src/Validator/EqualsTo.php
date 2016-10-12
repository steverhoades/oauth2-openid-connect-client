<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/11/16
 * Time: 1:15 PM
 */

namespace OpenIDConnectClient\Validator;


class EqualsTo implements ValidatorInterface
{
    use ValidatorTrait;

    public function isValid($expectedValue, $actualValue)
    {
        if ($expectedValue === $actualValue) {
            return true;
        }

        $this->message = sprintf("%s is invalid as it does not equal expected %s", $actualValue, $expectedValue);
        return false;
    }
}
