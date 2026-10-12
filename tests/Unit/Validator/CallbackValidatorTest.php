<?php

declare(strict_types=1);

namespace Tests\Unit\Validator;

use ChernegaSergiy\TerminalUi\Validator\CallbackValidator;
use ChernegaSergiy\TerminalUi\Validator\ValidatorInterface;
use PHPUnit\Framework\TestCase;

final class CallbackValidatorTest extends TestCase
{
    public function testImplementsValidatorInterface() : void
    {
        $validator = new CallbackValidator(static fn (string $v) : ?string => null);

        $this->assertInstanceOf(ValidatorInterface::class, $validator);
    }

    public function testValidateReturnsNullOnSuccess() : void
    {
        $validator = new CallbackValidator(static function (string $value) : ?string {
            return strlen($value) >= 3 ? null : 'Too short';
        });

        $this->assertNull($validator->validate('valid'));
    }

    public function testValidateReturnsErrorMessageOnFailure() : void
    {
        $validator = new CallbackValidator(static function (string $value) : ?string {
            return strlen($value) >= 5 ? null : 'Minimum 5 characters required';
        });

        $this->assertSame('Minimum 5 characters required', $validator->validate('abc'));
    }
}
