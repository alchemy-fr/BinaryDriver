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

interface ProcessBuilderFactoryInterface
{
    /**
     * Returns a new instance of Symfony Process
     *
     * @param string|array $arguments An argument or an array of arguments
     *
     * @return Process
     *
     * @throws InvalidArgumentException
     */
    public function create($arguments = []) : Process;

    /**
     * Returns the path to the binary that is used
     *
     * @return string
     */
    public function getBinary() : string;

    /**
     * Sets the path to the binary
     *
     * @param string $binary A path to a binary
     *
     * @return ProcessBuilderFactoryInterface
     *
     * @throws InvalidArgumentException In case binary is not executable
     */
    public function useBinary(string $binary) : ProcessBuilderFactoryInterface;

    /**
     * Set the default timeout to apply on created processes.
     *
     * @param integer|float $timeout
     *
     * @return ProcessBuilderFactoryInterface
     *
     * @throws InvalidArgumentException In case the timeout is not valid
     */
    public function setTimeout($timeout);

    /**
     * Returns the current timeout applied to the created processes.
     *
     * @return integer|float
     */
    public function getTimeout();
}
