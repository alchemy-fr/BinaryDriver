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

interface ConfigurationInterface extends \ArrayAccess, \IteratorAggregate
{
    /**
     * Returns the value given a key from configuration
     *
     * @param string $key
     * @param mixed  $default The default value in case the key does not exist
     *
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Set a value to configuration
     *
     * @param string $key   The key
     * @param mixed  $value The value corresponding to the key
     */
    public function set(string $key, $value);

    /**
     * Tells if Configuration contains `$key`
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key) : bool;

    /**
     * Removes a value given a key
     *
     * @param string $key
     *
     * @return mixed The previous value
     */
    public function remove(string $key);

    /**
     * Returns all values set in the configuration
     *
     * @return mixed[]
     */
    public function all() : array;
}
