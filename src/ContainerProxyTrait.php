<?php

/**
 * This file is part of Zero Framework.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace LaravelZero\Framework;

use BadMethodCallException;

/**
 * The is the Zero Framework container proxy class.
 *
 * @author Nuno Maduro <enunomaduro@gmail.com>
 */
trait ContainerProxyTrait
{
    /**
     * Proxies calls into the container.
     *
     * @param string $method
     * @param array $parameters
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function __call($method, array $parameters)
    {
        if (is_callable([$this->container, $method])) {
            return call_user_func_array([$this->container, $method], $parameters);
        }

        throw new BadMethodCallException("Method [{$method}] does not exist.");
    }

    /**
     * Dynamically access container services.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->container->{$key};
    }

    /**
     * Dynamically set container services.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->container->{$key} = $value;
    }

    /**
     * Determine if a given offset exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->container[$key]);
    }

    /**
     * Get the value at a given offset.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->container[$key];
    }

    /**
     * Set the value at a given offset.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->container[$key] = $value;
    }

    /**
     * Unset the value at a given offset.
     *
     * @param string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->container[$key]);
    }
}
