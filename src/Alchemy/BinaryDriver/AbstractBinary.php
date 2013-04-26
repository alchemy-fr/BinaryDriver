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

use Alchemy\BinaryDriver\Exception\ExecutableNotFoundException;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException;

abstract class AbstractBinary implements BinaryInterface
{
    /** @var ConfigurationInterface */
    protected $configuration;

    /** @var ProcessBuilderFactoryInterface */
    protected $factory;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(ProcessBuilderFactoryInterface $factory, LoggerInterface $logger, ConfigurationInterface $configuration)
    {
        $this->factory = $factory;
        $this->logger = $logger;
        $this->configuration = $configuration;
        $this->applyProcessConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     *
     * @return BinaryInterface
     */
    public function setConfiguration(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
        $this->applyProcessConfiguration();

        return $this;
    }

    /**
     * Get the current logger.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     *
     * @return BinaryInterface
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessBuilderFactory()
    {
        return $this->factory;
    }

    /**
     * {@inheritdoc}
     *
     * @return BinaryInterface
     */
    public function setProcessBuilderFactory(ProcessBuilderFactoryInterface $factory)
    {
        $this->factory = $factory;
        $this->applyProcessConfiguration();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function load($binaries, LoggerInterface $logger = null, $configuration = array())
    {
        $finder = new ExecutableFinder();
        $binary = null;
        $binaries = is_array($binaries) ? $binaries : array($binaries);

        foreach ($binaries as $candidate) {
            if (file_exists($candidate) && is_executable($candidate)) {
                $binary = $candidate;
                break;
            }
            if (null !== $binary = $finder->find($candidate)) {
                break;
            }
        }

        if (null === $binary) {
            throw new ExecutableNotFoundException(sprintf(
                'Executable not found, proposed : %s', implode(', ', $binaries)
            ));
        }

        if (null === $logger) {
            $logger = new Logger(__NAMESPACE__ . ' logger');
            $logger->pushHandler(new NullHandler());
        }

        $configuration = $configuration instanceof ConfigurationInterface ? $configuration : new Configuration($configuration);

        return new static(new ProcessBuilderFactory($binary), $logger, $configuration);
    }

    /**
     * Returns the name of the driver
     *
     * @return string
     */
    abstract public function getName();

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
    protected function run(Process $process, $bypassErrors = false)
    {
        $this->logger->info(sprintf(
            '%s running command %s', $this->getName(), $process->getCommandLine()
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
                '%s failed to execute command %s', $this->getName(), $process->getCommandLine()
            ));

            return;
        } else {
            $this->logger->info(sprintf('%s executed command successfully', $this->getName()));

            return $process->getOutput();
        }
    }

    private function applyProcessConfiguration()
    {
        if ($this->configuration->has('timeout')) {
            $this->factory->setTimeout($this->configuration->get('timeout'));
        }

        return $this;
    }

    private function doExecutionFailure($command, \Exception $e = null)
    {
        $this->logger->error(sprintf(
            '%s failed to execute command %s', $this->getName(), $command
        ));
        throw new ExecutionFailureException(sprintf(
            '%s failed to execute command %s', $this->getName(), $command
        ), $e ? $e->getCode() : null, $e ?: null);
    }
}
