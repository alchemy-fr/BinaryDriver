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

use Alchemy\BinaryDriver\Exception\InvalidArgumentException;
use Symfony\Component\Process\Process;

class ProcessBuilderFactory implements ProcessBuilderFactoryInterface
{
    /**
     * The binary path
     *
     * @var String
     */
    protected $binary;

    /**
     * The timeout for the generated processes
     *
     * @var null|float
     */
    private $timeout;

    /**
     * Constructor
     *
     * @param string $binary The path to the binary
     *
     * @throws InvalidArgumentException In case binary path is invalid
     */
    public function __construct(string $binary)
    {
        $this->useBinary($binary);
    }

    /**
     * @inheritDoc
     */
    public function getBinary() : string
    {
        return $this->binary;
    }

    /**
     * @inheritDoc
     */
    public function useBinary(string $binary) : ProcessBuilderFactoryInterface
    {
        if (!is_executable($binary)) {
            throw new InvalidArgumentException(sprintf('`%s` is not an executable binary', $binary));
        }

        $this->binary = $binary;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setTimeout(float $timeout = null) : ProcessBuilderFactoryInterface
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTimeout() : ?float
    {
        return $this->timeout;
    }

    /**
     * @inheritDoc
     */
    public function create($arguments = []) : Process
    {
        if (null === $this->binary) {
            throw new InvalidArgumentException('No binary set');
        }

        if (!is_array($arguments)) {
            $arguments = (array)$arguments;
        }

        array_unshift($arguments, $this->binary);

        $process = new Process($arguments, null, null, null, $this->timeout);
        $process->inheritEnvironmentVariables();

        return $process;
    }
}
