<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Unit\Validator;

use InvalidArgumentException;
use OpenIDConnectClient\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractValidatorTester extends TestCase
{
    protected static string $classUnderTest = 'please provide your the FQN of the class to test';

    abstract public function isValidArgumentExpectationsProvider(): iterable;

    abstract public function isValidInvalidArgumentProvider(): iterable;

    protected function setUp(): void
    {
        parent::setUp();

        $message = sprintf('Given $classUnderTest should an class FQN of a %s', ValidatorInterface::class);
        self::assertTrue(is_a(static::$classUnderTest, ValidatorInterface::class, true), $message);
    }

    /**
     * @dataProvider invalidConstructorParametersProvider
     */
    public function testConstructorWithInvalidArgumentsThrowsException(string $name, bool $required): void
    {
        $this->expectException(InvalidArgumentException::class);

        $classUnderTest = static::$classUnderTest;
        new $classUnderTest($name, $required);
    }

    /**
     * @dataProvider validConstructorParametersProvider
     */
    public function testGetters(string $name, bool $required): void
    {
        $classUnderTest = static::$classUnderTest;
        /** @var ValidatorInterface $validator */
        $validator = new $classUnderTest($name, $required);

        self::assertSame($name, $validator->getName());
        self::assertSame($required, $validator->isRequired());
        self::assertNull($validator->getMessage());
    }

    /**
     * @dataProvider isValidArgumentExpectationsProvider
     */
    public function testIsValidExpectations($originalExpectedValue, $receivedValue, bool $expectsIsValid): void
    {
        $classUnderTest = static::$classUnderTest;

        /** @var ValidatorInterface $validator */
        $validator = new $classUnderTest('name', true);
        $isValid = $validator->isValid($originalExpectedValue, $receivedValue);

        self::assertSame($expectsIsValid, $isValid);
        self::assertSame($expectsIsValid, $validator->getMessage() === null);
    }

    /**
     * @dataProvider isValidInvalidArgumentProvider
     */
    public function testIsValidWithInvalidParameters($originalExpectedValue, $receivedValue): void
    {
        $classUnderTest = static::$classUnderTest;

        /** @var ValidatorInterface $validator */
        $validator = new $classUnderTest('name', true);

        $this->expectException(InvalidArgumentException::class);

        $validator->isValid($originalExpectedValue, $receivedValue);
    }

    public function validConstructorParametersProvider(): iterable
    {
        yield ['a', true];
        yield ['name 1', true];
        yield ['name-2', false];
    }

    public function invalidConstructorParametersProvider(): iterable
    {
        yield ['', true];
        yield [' ', true];
        yield ["\n", true];
        yield ["\t \n", true];
    }
}
