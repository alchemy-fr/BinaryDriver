<?php
declare (strict_types = 1);

/*
 * This file is part of Alchemy\BinaryDriver.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\BinaryDriver\Listeners;

use Evenement\EventEmitter;
use Symfony\Component\Process\Process;

class DebugListener extends EventEmitter implements ListenerInterface
{
    private $prefixOut;
    private $prefixErr;
    private $eventOut;
    private $eventErr;

    public function __construct(string $prefixOut = '[OUT] ', string $prefixErr = '[ERROR] ', string $eventOut = 'debug', string $eventErr = 'debug')
    {
        $this->prefixOut = $prefixOut;
        $this->prefixErr = $prefixErr;
        $this->eventOut = $eventOut;
        $this->eventErr = $eventErr;
    }

    /**
     * @inheritDoc
     */
    public function handle(string $type, string $data) : void
    {
        if (Process::ERR === $type) {
            $this->emitLines($this->eventErr, $this->prefixErr, $data);
        } elseif (Process::OUT === $type) {
            $this->emitLines($this->eventOut, $this->prefixOut, $data);
        }
    }

    /**
     * @inheritDoc
     */
    public function forwardedEvents() : array
    {
        return array_unique([$this->eventErr, $this->eventOut]);
    }

    private function emitLines(string $event, string $prefix, string $lines) : void
    {
        foreach (explode("\n", $lines) as $line) {
            $this->emit($event, [$prefix . $line]);
        }
    }
}
