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

interface ProcessBuilderFactoryAwareInterface
{
    /**
     * Returns the current process builder factory
     *
     * @return ProcessBuilderFactoryInterface
     */
    public function getProcessBuilderFactory() : ProcessBuilderFactoryInterface;

    /**
     * Set a process builder factory
     *
     * @param ProcessBuilderFactoryInterface $factory
     *
     * @return ProcessBuilderFactoryAwareInterface
     */
    public function setProcessBuilderFactory(ProcessBuilderFactoryInterface $factory) : ProcessBuilderFactoryAwareInterface;
}
