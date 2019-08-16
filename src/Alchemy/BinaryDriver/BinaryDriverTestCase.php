<?php
declare (strict_types = 1);

namespace Alchemy\BinaryDriver;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use PHPUnit\Framework\TestCase;

/**
 * Convenient PHPUnit methods for testing BinaryDriverInterface implementations.
 */
class BinaryDriverTestCase extends TestCase
{
    /**
     * @return ProcessBuilderFactoryInterface|MockObject
     */
    public function createProcessBuilderFactoryMock() : ProcessBuilderFactoryInterface
    {
        return $this->createMock(ProcessBuilderFactoryInterface::class);
    }

    /**
     * @param integer $runs        The number of runs expected
     * @param bool $success     True if the process expects to be successfull
     * @param string  $commandLine The commandline executed
     * @param string  $output      The process output
     * @param string  $error       The process error output
     * @param bool $enableCallback
     *
     * @return Process|MockObject
     */
    public function createProcessMock(int $runs = 1, bool $success = true, ?string $commandLine = null, ?string $output = null, string $error = null, bool $enableCallback = false) : Process
    {
        $process = $this->createMock(Process::class);

        $builder = $process->expects($this->exactly($runs))
            ->method('run');

        if (true === $enableCallback) {
            $builder->with($this->isInstanceOf('Closure'));
        }

        $process->expects($this->any())
            ->method('isSuccessful')
            ->willReturn($success);

        foreach ([
            'getOutput' => $output,
            'getErrorOutput' => $error,
            'getCommandLine' => $commandLine,
        ] as $command => $value) {
            $process
                ->expects($this->any())
                ->method($command)
                ->willReturn($value);
        }

        return $process;
    }

    /**
     * @return LoggerInterface|MockObject
     */
    public function createLoggerMock() : LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return ConfigurationInterface|MockObject
     */
    public function createConfigurationMock() : ConfigurationInterface
    {
        return $this->createMock(ConfigurationInterface::class);
    }
}
