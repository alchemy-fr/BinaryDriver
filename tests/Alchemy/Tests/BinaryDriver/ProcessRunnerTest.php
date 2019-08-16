<?php declare (strict_types = 1);

/*
 * This file is part of Alchemy\BinaryDriver.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\BinaryDriver;

use Alchemy\BinaryDriver\ProcessRunner;
use Alchemy\BinaryDriver\BinaryDriverTestCase;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Alchemy\BinaryDriver\Listeners\ListenerInterface;
use Evenement\EventEmitter;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;
use Symfony\Component\Process\Process;

class ProcessRunnerTest extends BinaryDriverTestCase
{
    public function getProcessRunner($logger) : ProcessRunner
    {
        return new ProcessRunner($logger, 'test-runner');
    }

    public function testRunSuccessFullProcess() : void
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $process = $this->createProcessMock(1, true, '--helloworld--', "Kikoo Romain", null, true);

        $logger
            ->expects($this->never())
            ->method('error');
        $logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->assertEquals('Kikoo Romain', $runner->run($process, new \SplObjectStorage(), false));
    }

    public function testRunSuccessFullProcessBypassingErrors() : void
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $process = $this->createProcessMock(1, true, '--helloworld--', "Kikoo Romain", null, true);

        $logger
            ->expects($this->never())
            ->method('error');
        $logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->assertEquals('Kikoo Romain', $runner->run($process, new \SplObjectStorage(), true));
    }

    public function testRunFailingProcess() : void
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $process = $this->createProcessMock(1, false, '--helloworld--', null, null, true);

        $logger
            ->expects($this->once())
            ->method('error');
        $logger
            ->expects($this->once())
            ->method('info');

        try {
            $runner->run($process, new \SplObjectStorage(), false);
            $this->fail('An exception should have been raised');
        } catch (ExecutionFailureException $e) {

        }
    }

    public function testRunFailingProcessWithException() : void
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $exception = new ProcessRuntimeException('Process Failed');
        $process = $this->createMock(Process::class);
        $process->expects($this->once())
            ->method('run')
            ->will($this->throwException($exception));

        $logger
            ->expects($this->once())
            ->method('error');
        $logger
            ->expects($this->once())
            ->method('info');

        try {
            $runner->run($process, new \SplObjectStorage(), false);
            $this->fail('An exception should have been raised');
        } catch (ExecutionFailureException $e) {
            $this->assertEquals($exception, $e->getPrevious());
        }
    }

    public function testRunfailingProcessBypassingErrors() : void
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $process = $this->createProcessMock(1, false, '--helloworld--', 'Hello output', null, true);

        $logger
            ->expects($this->once())
            ->method('error');
        $logger
            ->expects($this->once())
            ->method('info');

        $this->assertSame('', $runner->run($process, new \SplObjectStorage(), true));
    }

    public function testRunFailingProcessWithExceptionBypassingErrors() : void
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $exception = new ProcessRuntimeException('Process Failed');
        $process = $this->createMock(Process::class);
        $process->expects($this->once())
            ->method('run')
            ->will($this->throwException($exception));

        $logger
            ->expects($this->once())
            ->method('error');
        $logger
            ->expects($this->once())
            ->method('info');

        $this->assertSame('', $runner->run($process, new \SplObjectStorage(), true));
    }

    public function testRunSuccessFullProcessWithHandlers() : void
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        /** @var null|\Closure $capturedCallback */
        $capturedCallback = null;

        $process = $this->createProcessMock(1, true, '--helloworld--', "Kikoo Romain", null, true);
        $process->expects($this->once())
            ->method('run')
            ->with($this->isInstanceOf('Closure'))
            ->willReturnCallback(
                function ($callback) use (&$capturedCallback) {
                    $capturedCallback = $callback;
                }
            );

        $logger
            ->expects($this->never())
            ->method('error');
        $logger
            ->expects($this->exactly(2))
            ->method('info');

        $listener = new TestListener();
        $storage = new \SplObjectStorage();
        $storage->attach($listener);

        $capturedType = $capturedData = null;

        $listener->on('received', function ($type, $data) use (&$capturedType, &$capturedData) {
            $capturedData = $data;
            $capturedType = $type;
        });

        $this->assertEquals('Kikoo Romain', $runner->run($process, $storage, false));

        $type = 'err';
        $data = 'data';

        $this->assertNotNull($capturedCallback);
        $capturedCallback($type, $data);

        $this->assertEquals($data, $capturedData);
        $this->assertEquals($type, $capturedType);
    }
}

class TestListener extends EventEmitter implements ListenerInterface
{
    /**
     * @inheritDoc
     */
    public function handle(string $type, string $data) : void
    {
        $this->emit('received', [$type, $data]);
    }

    /**
     * @inheritDoc
     */
    public function forwardedEvents() : array
    {
        return [];
    }
}
