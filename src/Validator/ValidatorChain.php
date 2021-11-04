<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Validator;

use Lcobucci\JWT\Token;
use OpenIDConnectClient\Exception\UnknownValidatorRequestedException;
use Webmozart\Assert\Assert;

final class ValidatorChain
{
    /** @var ValidatorInterface[] */
    private array $validators = [];

    /** @var string[] */
    private array $messages = [];

    /**
     * @param ValidatorInterface[] $validators
     */
    public function setValidators(array $validators): self
    {
        Assert::allIsInstanceOf($validators, ValidatorInterface::class);

        $this->validators = [];

        foreach ($validators as $validator) {
            $this->addValidator($validator);
        }

        return $this;
    }

    public function addValidator(ValidatorInterface $validator): self
    {
        $this->validators[$validator->getName()] = $validator;

        return $this;
    }

    public function validate(array $data, Token $token): bool
    {
        $valid = true;
        foreach ($this->validators as $claim => $validator) {
            if ($validator->isRequired() && $token->hasClaim($claim) === false) {
                $valid = false;
                $this->messages[$claim] = sprintf('Missing required value for claim %s', $claim);
                continue;
            } elseif (empty($data[$claim]) || $token->hasClaim($claim) === false) {
                continue;
            }

            if (!$validator->isValid($data[$claim], $token->getClaim($claim))) {
                $valid = false;
                $this->messages[$claim] = $validator->getMessage();
            }
        }

        return $valid;
    }

    public function hasValidator(string $name): bool
    {
        Assert::notEmpty(trim($name));

        return isset($this->validators[$name]);
    }

    public function getValidator(string $name): ValidatorInterface
    {
        Assert::notEmpty(trim($name));

        $validator = $this->validators[$name] ?? null;

        if (!$validator) {
            $message = sprintf('Validator with name "%s" is not registered', $name);
            throw new UnknownValidatorRequestedException($message);
        }

        return $validator;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
