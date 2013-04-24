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

interface ConfigurationInterface extends \ArrayAccess, \IteratorAggregate
{
    /**
     * Returns the value given a key from configuration
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws InvalidArgumentException In case the key is not defined
     */
    public function get($key);

    /**
     * Set a value to configuration
     *
     * @param string $key   The key
     * @param mixed  $value The value corresponding to the key
     */
    public function set($key, $value);

    /**
     * Tells if Configuration contains `$key`
     *
     * @param string $key
     *
     * @return Boolean
     */
    public function has($key);

    /**
     * Removes a value given a key
     *
     * @param string $key
     *
     * @return mixed The previous value
     *
     * @throw InvalidArgumentException In case the key is not defined
     */
    public function remove($key);

    /**
     * Returns all values set in the configuration
     *
     * @return array
     */
    public function all();
}
