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
use Alchemy\BinaryDriver\Listeners\ListenerInterface;
use Psr\Log\LoggerInterface;
use Evenement\EventEmitterInterface;

interface BinaryInterface extends ConfigurationAwareInterface, ProcessBuilderFactoryAwareInterface, ProcessRunnerAwareInterface, EventEmitterInterface
{
    /**
     * Adds a listener to the binary driver
     *
     * @param ListenerInterface $listener
     *
     * @return BinaryInterface
     */
    public function listen(ListenerInterface $listener);

    /**
     * Removes a listener from the binary driver
     *
     * @param ListenerInterface $listener
     *
     * @return BinaryInterface
     */
    public function unlisten(ListenerInterface $listener);

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
