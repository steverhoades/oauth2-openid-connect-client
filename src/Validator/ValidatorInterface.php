<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/11/16
 * Time: 1:15 PM
 */

namespace OpenIDConnectClient\Validator;


interface ValidatorInterface
{
    public function getName();
    public function isValid($expectedValue, $actualValue);
    public function getMessage();
}
