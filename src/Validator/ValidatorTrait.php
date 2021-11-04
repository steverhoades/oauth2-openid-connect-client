<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Validator;

use Webmozart\Assert\Assert;

/**
 * @implements ValidatorInterface
 */
trait ValidatorTrait
{
    private string $name;
    private ?string $message = null;
    private bool $required;

    public function __construct(string $name, bool $required = false)
    {
        Assert::notEmpty(trim($name));

        $this->name = $name;
        $this->required = $required;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
