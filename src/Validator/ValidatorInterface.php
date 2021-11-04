<?php

/**
 * @author Steve Rhoades <sedonami@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace OpenIDConnectClient\Validator;

interface ValidatorInterface
{
    public function getName();

    public function isValid($expectedValue, $actualValue);

    public function getMessage();
}
