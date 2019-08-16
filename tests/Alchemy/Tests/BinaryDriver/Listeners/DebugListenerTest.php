<?php declare(strict_types=1);

namespace Alchemy\Tests\BinaryDriver\Listeners;

use Alchemy\BinaryDriver\Listeners\DebugListener;
use Symfony\Component\Process\Process;
use PHPUnit\Framework\TestCase;

class DebugListenerTest extends TestCase
{
    public function testHandle() : void
    {
        $listener = new DebugListener();

        $lines = [];
        $listener->on('debug', function ($line) use (&$lines) {
            $lines[] = $line;
        });
        $listener->handle(Process::ERR, "first line\nsecond line");
        $listener->handle(Process::OUT, "cool output");
        $listener->handle('unknown', "lalala");
        $listener->handle(Process::OUT, "another output\n");

        $expected = [
            '[ERROR] first line',
            '[ERROR] second line',
            '[OUT] cool output',
            '[OUT] another output',
            '[OUT] ',
        ];

        $this->assertEquals($expected, $lines);
    }
}
