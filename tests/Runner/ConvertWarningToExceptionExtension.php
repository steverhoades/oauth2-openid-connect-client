<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Runner;

use PHPUnit\Runner\AfterTestWarningHook;

final class ConvertWarningToExceptionExtension implements AfterTestWarningHook
{
    public function executeAfterTestWarning(string $test, string $message, float $time): void
    {
        echo PHP_EOL . $message . ' in ' . $test . PHP_EOL;
        exit(1);
    }
}
