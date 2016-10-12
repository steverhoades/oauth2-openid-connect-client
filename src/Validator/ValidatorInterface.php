<?php
/**
 * @author Steve Rhoades <sedonami@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace OpenIDConnectClient\Validator;


interface ValidatorInterface
{
    public function getName();
    public function isValid($expectedValue, $actualValue);
    public function getMessage();
}
