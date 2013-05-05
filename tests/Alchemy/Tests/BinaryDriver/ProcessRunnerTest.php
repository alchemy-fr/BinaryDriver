<?php

namespace Alchemy\Tests\BinaryDriver;

use Alchemy\BinaryDriver\ProcessRunner;
use Alchemy\BinaryDriver\BinaryDriverTestCase;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;

class ProcessRunnerTest extends BinaryDriverTestCase
{
    public function getProcessRunner($logger)
    {
        return new ProcessRunner($logger, 'test-runner');
    }

    public function testRunSuccessFullProcess()
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $process = $this->createProcessMock(1, true, '--helloworld--', "Kikoo Romain");

        $logger
            ->expects($this->never())
            ->method('error');
        $logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->assertEquals('Kikoo Romain', $runner->run($process, false));
    }

    public function testRunSuccessFullProcessBypassingErrors()
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $process = $this->createProcessMock(1, true, '--helloworld--', "Kikoo Romain");

        $logger
            ->expects($this->never())
            ->method('error');
        $logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->assertEquals('Kikoo Romain', $runner->run($process, true));
    }

    public function testRunFailingProcess()
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $process = $this->createProcessMock(1, false, '--helloworld--');

        $logger
            ->expects($this->once())
            ->method('error');
        $logger
            ->expects($this->once())
            ->method('info');

        try {
            $runner->run($process, false);
            $this->fail('An exception should have been raised');
        } catch (ExecutionFailureException $e) {

        }
    }

    public function testRunFailingProcessWithException()
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $exception = new ProcessRuntimeException('Process Failed');
        $process = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();
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
            $runner->run($process, false);
            $this->fail('An exception should have been raised');
        } catch (ExecutionFailureException $e) {
            $this->assertEquals($exception, $e->getPrevious());
        }
    }

    public function testRunfailingProcessBypassingErrors()
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $process = $this->createProcessMock(1, false, '--helloworld--', 'Hello output');

        $logger
            ->expects($this->once())
            ->method('error');
        $logger
            ->expects($this->once())
            ->method('info');

        $this->assertNull($runner->run($process, true));
    }

    public function testRunFailingProcessWithExceptionBypassingErrors()
    {
        $logger = $this->createLoggerMock();
        $runner = $this->getProcessRunner($logger);

        $exception = new ProcessRuntimeException('Process Failed');
        $process = $this->getMockBuilder('Symfony\Component\Process\Process')
            ->disableOriginalConstructor()
            ->getMock();
        $process->expects($this->once())
            ->method('run')
            ->will($this->throwException($exception));

        $logger
            ->expects($this->once())
            ->method('error');
        $logger
            ->expects($this->once())
            ->method('info');

        $this->assertNull($runner->run($process, true));
    }
}
