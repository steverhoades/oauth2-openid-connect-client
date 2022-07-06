<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Validator;

use Lcobucci\JWT\Token\Plain;
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

    public function validate(array $data, Plain $token): bool
    {
        $valid = true;
        $claims = $token->claims();

        foreach ($this->validators as $claim => $validator) {
            if ($validator->isRequired() && !$claims->has($claim)) {
                $valid = false;
                $this->messages[$claim] = sprintf('Missing required value for claim %s', $claim);
                continue;
            }

            if (!isset($data[$claim]) || !$data[$claim] || !$claims->has($claim)) {
                continue;
            }

            // All timestamps will be converted to DateTimeImmutable
            // Convert them back to unix timestamp here so we can compare as numbers
            $claimValue = $claims->get($claim);
            if ($claimValue instanceof \DateTimeInterface) {
                $claimValue = $claimValue->getTimestamp();
            }

            if (!$validator->isValid($data[$claim], $claimValue)) {
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
