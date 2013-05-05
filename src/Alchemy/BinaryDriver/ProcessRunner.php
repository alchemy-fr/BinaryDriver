<?php

/*
 * This file is part of Alchemy\BinaryDriver.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\BinaryDriver;

use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class ProcessRunner implements ProcessRunnerInterface
{
    private $logger;
    private $name;

    public function __construct(LoggerInterface $logger, $name)
    {
        $this->logger = $logger;
        $this->name = $name;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Executes a process, logs events
     *
     * @param Process $process
     * @param Boolean $bypassErrors Set to true to disable throwing ExecutionFailureExceptions
     *
     * @return string The Process output
     *
     * @throws ExecutionFailureException in case of process failure.
     */
    public function run(Process $process, $bypassErrors)
    {
        $this->logger->info(sprintf(
            '%s running command %s', $this->name, $process->getCommandLine()
        ));

        try {
            $process->run();
        } catch (RuntimeException $e) {
            if (!$bypassErrors) {
                $this->doExecutionFailure($process->getCommandLine(), $e);
            }
        }

        if (!$bypassErrors && !$process->isSuccessful()) {
            $this->doExecutionFailure($process->getCommandLine());
        } elseif (!$process->isSuccessful()) {
            $this->logger->error(sprintf(
                '%s failed to execute command %s', $this->name, $process->getCommandLine()
            ));

            return;
        } else {
            $this->logger->info(sprintf('%s executed command successfully', $this->name));

            return $process->getOutput();
        }
    }

    private function doExecutionFailure($command, \Exception $e = null)
    {
        $this->logger->error(sprintf(
            '%s failed to execute command %s', $this->name, $command
        ));
        throw new ExecutionFailureException(sprintf(
            '%s failed to execute command %s', $this->name, $command
        ), $e ? $e->getCode() : null, $e ?: null);
    }
}

