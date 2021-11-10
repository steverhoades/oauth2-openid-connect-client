<?php

declare(strict_types=1);

namespace OpenIDConnectClient\Tests\Runner;

use DG\BypassFinals;
use PHPUnit\Runner\BeforeFirstTestHook;

final class BypassFinalExtension implements BeforeFirstTestHook
{
    public function executeBeforeFirstTest(): void
    {
        BypassFinals::enable();
    }
}
