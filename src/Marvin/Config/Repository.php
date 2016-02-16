<?php
namespace Marvin\Config;

class Repository
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
        if (empty($this->items) || is_null($key)) {
            return false;
        }

        if (array_key_exists($key, $this->items)) {
            return true;
        }

        return false;
    }

    /**
     * Get specified configuration if exist.
     * If configuration not exist and default is set return default value.
     *
     * @param string $key
     * @param null   $default
     *
     * @return array|null
     */
    public function get($key, $default = null)
    {
        if ($this->has($key) && isset($this->items[$key])) {
            return $this->items[$key];
        }
        if ( ! $this->has($key) && ! is_null($default)) {
            return $default;
        }

        return $this->items;
    }

    /**
     * Set configuration name and value
     *
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        $this->items[$key] = $value;
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
}
