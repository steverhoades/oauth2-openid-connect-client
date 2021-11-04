<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Validator;

use InvalidArgumentException;

interface ValidatorInterface
{
    public function getName(): string;

    /**
     * @param mixed $expectedValue
     * @param mixed $actualValue
     * @throws InvalidArgumentException
     */
    public function isValid($expectedValue, $actualValue): bool;

    public function getMessage(): ?string;

    public function isRequired(): bool;
}
