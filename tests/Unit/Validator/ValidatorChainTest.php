<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Unit\Validator;

use InvalidArgumentException;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\Plain;
use OpenIDConnectClient\Validator\ValidatorChain;
use OpenIDConnectClient\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ValidatorChainTest extends TestCase
{
    private ValidatorChain $chain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chain = new ValidatorChain();
    }

    public function testApplyValidators(): void
    {
        $firstValidator = $this->createMock(ValidatorInterface::class);
        $secondValidator = $this->createMock(ValidatorInterface::class);
        $thirdValidator = $this->createMock(ValidatorInterface::class);
        $lastValidator = $this->createMock(ValidatorInterface::class);

        $firstValidator
            ->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('v1');

        $secondValidator
            ->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('v2');

        $thirdValidator
            ->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('v3');

        $lastValidator
            ->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('vx');

        $validators = [$secondValidator, $thirdValidator];

        $this->chain->addValidator($firstValidator);
        $this->chain->setValidators($validators);
        $this->chain->addValidator($lastValidator);

        self::assertFalse($this->chain->hasValidator('v1'));
        self::assertTrue($this->chain->hasValidator('v2'));
        self::assertTrue($this->chain->hasValidator('v3'));
        self::assertTrue($this->chain->hasValidator('vx'));
        self::assertFalse($this->chain->hasValidator('xx'));

        self::assertSame($secondValidator, $this->chain->getValidator('v2'));
        self::assertSame($thirdValidator, $this->chain->getValidator('v3'));
        self::assertSame($lastValidator, $this->chain->getValidator('vx'));
    }

    public function testSetValidatorsWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->chain->setValidators([new stdClass()]);
    }

    public function testHasValidatorThrowsExceptionForEmptyParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->chain->hasValidator('');
    }

    public function testGetValidatorThrowsExceptionForEmptyParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->chain->getValidator('');
    }

    public function testExecuteValidValidators(): void
    {
        $firstValidator = $this->createMock(ValidatorInterface::class);
        $secondValidator = $this->createMock(ValidatorInterface::class);

        $firstValidator
            ->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('v1');

        $secondValidator
            ->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('v2');

        $firstValidator
            ->expects(self::atLeastOnce())
            ->method('isRequired')
            ->willReturn(true);

        $secondValidator
            ->expects(self::atLeastOnce())
            ->method('isRequired')
            ->willReturn(false);

        $firstValidator
            ->expects(self::once())
            ->method('isValid')
            ->with(self::identicalTo('value 1'), self::identicalTo('some value 1'))
            ->willReturn(true);

        $secondValidator
            ->expects(self::once())
            ->method('isValid')
            ->with(self::identicalTo('value 2'), self::identicalTo('some value 2'))
            ->willReturn(true);

        $this->chain->addValidator($firstValidator);
        $this->chain->addValidator($secondValidator);

        $token = $this->createMock(Plain::class);
        $claims = $this->createMock(DataSet::class);
        $token
            ->expects(self::once())
            ->method('claims')
            ->willReturn($claims);

        $claims
            ->expects(self::exactly(3))
            ->method('has')
            ->willReturnMap(
                [
                    ['v1', true],
                    ['v2', true],
                ],
            );

        $claims
            ->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['v1', null, 'some value 1'],
                    ['v2', null, 'some value 2'],
                ],
            );

        $claims = [
            'v1' => 'value 1',
            'v2' => 'value 2',
            'unused' => 'something unused',
        ];

        self::assertTrue($this->chain->validate($claims, $token));
        self::assertCount(0, $this->chain->getMessages());
    }

    public function testExecuteInValidValidators(): void
    {
        $firstValidator = $this->createMock(ValidatorInterface::class);
        $secondValidator = $this->createMock(ValidatorInterface::class);
        $thirdValidator = $this->createMock(ValidatorInterface::class);
        $fourthValidator = $this->createMock(ValidatorInterface::class);

        $firstValidator
            ->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('v1');

        $secondValidator
            ->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('v2');

        $thirdValidator
            ->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('v3');

        $fourthValidator
            ->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn('v4');

        $firstValidator
            ->expects(self::atLeastOnce())
            ->method('isRequired')
            ->willReturn(true);

        $secondValidator
            ->expects(self::atLeastOnce())
            ->method('isRequired')
            ->willReturn(false);

        $firstValidator
            ->expects(self::never())
            ->method('isValid');

        $secondValidator
            ->expects(self::once())
            ->method('isValid')
            ->with(self::identicalTo('value 2'), self::identicalTo('some value 2'))
            ->willReturn(false);

        $secondValidator
            ->expects(self::once())
            ->method('getMessage')
            ->willReturn('some error message');

        $thirdValidator
            ->expects(self::never())
            ->method('isValid');

        $fourthValidator
            ->expects(self::never())
            ->method('isValid');

        $this->chain->addValidator($firstValidator);
        $this->chain->addValidator($secondValidator);
        $this->chain->addValidator($thirdValidator);
        $this->chain->addValidator($fourthValidator);

        $token = $this->createMock(Plain::class);
        $claims = $this->createMock(DataSet::class);
        $token
            ->expects(self::once())
            ->method('claims')
            ->willReturn($claims);

        $claims
            ->expects(self::exactly(3))
            ->method('has')
            ->willReturnMap(
                [
                    ['v1', false],
                    ['v2', true],
                    ['v4', false],
                ],
            );

        $claims
            ->expects(self::once())
            ->method('get')
            ->willReturnMap(
                [
                    ['v2', null, 'some value 2'],
                ],
            );

        $claims = [
            'v1' => 'value 1',
            'v2' => 'value 2',
            'v4' => 'value 4',
        ];

        self::assertFalse($this->chain->validate($claims, $token));

        $messages = $this->chain->getMessages();
        self::assertCount(2, $messages);
        self::assertSame('Missing required value for claim v1', $messages['v1']);
        self::assertSame('some error message', $messages['v2']);
    }
}
