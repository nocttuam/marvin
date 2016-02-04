<?php
namespace Marvin\Config;


class Repository
{
//    public function has($key);
//
//    public function get($key, $default);
//
//    public function set($key, $value);
//
//    public function all();

    protected $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function has($key)
    {
        if (empty($this->items) || is_null($key)) {
            return false;
        }

        if (array_key_exists($key, $this->items)) {
            return true;
        }
    }

    public function get($key, $default = null)
    {
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }
        if ( ! $this->has($key) && ! is_null($default)) {
            return $default;
        }
    }

    public function set($key, $value)
    {
        $this->items[$key] = $value;
    }

    public function all()
    {
        return $this->items;
    }
}
