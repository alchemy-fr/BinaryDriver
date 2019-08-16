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

namespace Alchemy\BinaryDriver;

use Alchemy\BinaryDriver\Exception\ExecutableNotFoundException;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Alchemy\BinaryDriver\Listeners\Listeners;
use Alchemy\BinaryDriver\Listeners\ListenerInterface;
use Evenement\EventEmitter;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

abstract class AbstractBinary extends EventEmitter implements BinaryInterface
{
    /** @var ConfigurationInterface */
    protected $configuration;

    /** @var ProcessBuilderFactoryInterface */
    protected $factory;

    /** @var ProcessRunner */
    private $processRunner;

    /** @var Listeners */
    private $listenersManager;

    public function __construct(ProcessBuilderFactoryInterface $factory, LoggerInterface $logger, ConfigurationInterface $configuration)
    {
        $this->factory = $factory;
        $this->configuration = $configuration;
        $this->processRunner = new ProcessRunner($logger, $this->getName());
        $this->listenersManager = new Listeners();
        $this->applyProcessConfiguration();
    }

    /**
     * @inheritDoc
     */
    public function listen(ListenerInterface $listener) : BinaryInterface
    {
        $this->listenersManager->register($listener, $this);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function unlisten(ListenerInterface $listener) : BinaryInterface
    {
        $this->listenersManager->unregister($listener);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConfiguration() : ConfigurationInterface
    {
        return $this->configuration;
    }

    /**
     * @inheritDoc
     */
    public function setConfiguration(ConfigurationInterface $configuration) : ConfigurationAwareInterface
    {
        $this->configuration = $configuration;
        $this->applyProcessConfiguration();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getProcessBuilderFactory() : ProcessBuilderFactoryInterface
    {
        return $this->factory;
    }

    /**
     * @inheritDoc
     *
     * @return ProcessBuilderFactoryAwareInterface
     */
    public function setProcessBuilderFactory(ProcessBuilderFactoryInterface $factory) : ProcessBuilderFactoryAwareInterface
    {
        $this->factory = $factory;
        $this->applyProcessConfiguration();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getProcessRunner() : ProcessRunnerInterface
    {
        return $this->processRunner;
    }

    /**
     * @inheritDoc
     */
    public function setProcessRunner(ProcessRunnerInterface $runner) : void
    {
        $this->processRunner = $runner;
    }

    /**
     * @inheritDoc
     */
    public function command($command, bool $bypassErrors = false, $listeners = null) : string
    {
        if (!is_array($command)) {
            $command = (array)$command;
        }

        return $this->run($this->factory->create($command), $bypassErrors, $listeners);
    }

    /**
     * @inheritDoc
     */
    public static function load($binaries, ? LoggerInterface $logger = null, $configuration = []) : BinaryInterface
    {
        $finder = new ExecutableFinder();
        $binary = null;
        $binaries = is_array($binaries) ? $binaries : (array)$binaries;

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
                'Executable not found, proposed : %s',
                implode(', ', $binaries)
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
    abstract public function getName() : string;

    /**
     * Executes a process, logs events
     *
     * @param Process                 $process
     * @param bool                 $bypassErrors Set to true to disable throwing ExecutionFailureExceptions
     * @param ListenerInterface|array $listeners    A listener or an array of listener to register for this unique run
     *
     * @return string The Process output
     *
     * @throws ExecutionFailureException in case of process failure.
     */
    protected function run(Process $process, $bypassErrors = false, $listeners = null) : string
    {
        if (null !== $listeners) {
            if (!is_array($listeners)) {
                $listeners = [$listeners];
            }

            $listenersManager = clone $this->listenersManager;

            foreach ($listeners as $listener) {
                $listenersManager->register($listener, $this);
            }
        } else {
            $listenersManager = $this->listenersManager;
        }

        return $this->processRunner->run($process, $listenersManager->storage, $bypassErrors);
    }

    private function applyProcessConfiguration() : self
    {
        if ($this->configuration->has('timeout')) {
            $this->factory->setTimeout($this->configuration->get('timeout'));
        }

        return $this;
    }
}
