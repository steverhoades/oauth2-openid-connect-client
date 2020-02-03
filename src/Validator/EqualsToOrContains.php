<?php
/**
 * @author automatix
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace OpenIDConnectClient\Validator;


class EqualsToOrContains implements ValidatorInterface
{
    use ValidatorTrait;

    public function isValid($expectedValue, $actualValue)
    {
        $valid = false;
        if (! is_array($actualValue)) {
            $valid = $expectedValue === $actualValue;
            if (! $valid) {
                $this->message = sprintf("%s is invalid as it does not equal expected %s", $actualValue, $expectedValue);
            }
        } else {
            $valid = in_array($expectedValue, $actualValue);
            if (! $valid) {
                $this->message = sprintf("The value is invalid as the given array does not contain expected %s", $expectedValue);
            }
        }

        return $valid;
    }
}
