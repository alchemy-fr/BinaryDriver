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

interface ProcessBuilderFactoryInterface
{
     /**
     * Returns a new instance of Symfony Process
     *
     * @return Process
     *
     * @throws InvalidArgumentException
     */
    public function create($arguments = array());

    /**
     * Returns the path to the binary that is used
     *
     * @return String
     */
    public function getBinary();

    /**
     * Sets the path to the binary
     *
     * @param String $binary A path to a binary
     *
     * @return ProcessBuilderFactoryInterface
     *
     * @throws InvalidArgumentException In case binary is not executable
     */
    public function useBinary($binary);
}
