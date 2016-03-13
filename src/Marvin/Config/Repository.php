<?php
namespace Marvin\Config;

use ArrayAccess;

class Repository implements ArrayAccess
{
    /**
     * List of the configurations items
     *
     * @var array
     */
    protected $items;

    /**
     * Repository constructor.
     *
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Return if configuration item exist in items list
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        $array = $this->items;

        if (empty($array) || is_null($key)) {
            return false;
        }

        if ($this->exists($array, $key)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Get specified configuration if exist.
     * If configuration not exist and default is set return default value.
     * Support dot notation
     *
     * @param string $key
     * @param null   $default
     *
     * @return array|null
     */
    public function get($key, $default = null)
    {
        $array = $this->items;

        if ($this->exists($array, $key)) {
            return $this->items[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if ($this->exists($array, $segment)) {
                $array = $array[$segment];
            } elseif ( ! is_null($default)) {
                return $default;
            }
        }

        return $array;
    }

    public function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Set configuration name and value
     * Support dot notation
     *
     * @param string $key
     * @param string $value
     *
     * @return mixed
     */
    public function set($key, $value)
    {
        $array = &$this->items;

        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);
            if ( ! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Return complete configuration list
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     *
     * @return mixed
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetUnset($key)
    {
        $this->set($key, null);
    }
}
