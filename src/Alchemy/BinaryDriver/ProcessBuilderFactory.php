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
     * @var integer|float
     */
    private $timeout;

    /**
     * Constructor
     *
     * @param String $binary The path to the binary
     *
     * @throws InvalidArgumentException In case binary path is invalid
     */
    public function __construct($binary)
    {
        $this->useBinary($binary);
    }

    /**
     * @inheritdoc
     */
    public function getBinary()
    {
        return $this->binary;
    }

    /**
     * @inheritdoc
     */
    public function useBinary($binary)
    {
        if (!is_executable($binary)) {
            throw new InvalidArgumentException(sprintf('`%s` is not an executable binary', $binary));
        }

        $this->binary = $binary;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @inheritdoc
     */
    public function create($arguments = [])
    {
        if (null === $this->binary) {
            throw new InvalidArgumentException('No binary set');
        }

        if (!is_array($arguments)) {
            $arguments = [$arguments];
        }

        array_unshift($arguments, $this->binary);
        if (method_exists('Symfony\Component\Process\ProcessUtils', 'escapeArgument')) {
            $script = implode(' ', array_map(['Symfony\Component\Process\ProcessUtils', 'escapeArgument'], $arguments));
        } else {
            $script = $arguments;
        }

        $env = array_replace($_ENV, $_SERVER);
        $env = array_filter($env, function ($value) {
            return !is_array($value);
        });

        return new Process($script, null, $env, null, $this->timeout);
    }
}
