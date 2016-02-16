<?php
namespace Marvin\Contracts;

interface Host
{
    /**
     * Set a given configuration name and value
     *
     * @param string $key
     * @param string $value
     */
    public function set($key, $value);

    /**
     * Get the specified configuration value of the virtual host object
     *
     * @param string $key
     */
    public function get($key);

    /**
     * Create a temporary file containing virtual host configurations
     */
    public function create();
}