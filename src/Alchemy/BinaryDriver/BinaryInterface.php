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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

interface BinaryInterface extends ConfigurationAwareInterface, ProcessBuilderFactoryAwareInterface, LoggerAwareInterface
{
    /**
     * Loads a binary
     *
     * @param string|array                 $binaries      A binary name or an array of binary names
     * @param null||LoggerInterface        $logger        A Logger
     * @param array|ConfigurationInterface $configuration The configuration
     *
     * @throws ExecutableNotFoundException In case none of the binaries were found
     *
     * @return BinaryInterface
     */
    public static function load($binaries, LoggerInterface $logger = null, $configuration = array());
}
