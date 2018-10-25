<?php

namespace AEngine\Orchid;

use ArrayIterator;
use Closure;
use AEngine\Orchid\Interfaces\CollectionInterface;

class Collection implements CollectionInterface
{
    /**
     * Full path of the model class
     *
     * @var string
     */
    protected static $model;

    /**
     * Internal storage of models
     *
     * @var array
     */
    protected $data = [];

    /**
     * Iterator position
     *
     * @var int
     */
    protected $position = 0;

    final public function __construct(array $data = [])
    {
        $this->replace($data);
    }

    /**
     * Returns element that corresponds to the specified index
     *
     * @param int  $key
     * @param null $default
     *
     * @return mixed
     * @internal param int $index
     */
    public function get($key, $default = null)
    {
        if (isset($this->data[$key])) {
            if (static::$model) {
                return new static::$model($this->data[$key]);
            }

            return $this->data[$key];
        }

        return $default;
    }

    /**
     * Set value of the element
     *
     * @param int         $key
     * @param Model|array $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        if ($value instanceof Model) {
            $this->data[$key] = $value->toArray();
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Add item to collection, replacing existing items with the same data key
     *
     * @param array $items Key-value array of data to append to this collection
     *
     * @return $this
     */
    public function replace(array $items)
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Does this collection have a given key?
     *
     * @param string $key The data key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param  mixed $value
     * @param  bool  $strict
     *
     * @return mixed
     */
    public function search($value, $strict = false)
    {
        if (is_callable($value)) {
            foreach ($this->data as $key => $item) {
                if (call_user_func($value, $item, $key)) {
                    return $key;
                }
            }
        }

        return array_search($value, $this->data, $strict);
    }

    /**
     * Get all items in collection
     *
     * @return array The collection's source data
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     *
     * @return $this
     */
    public function remove($key)
    {
        unset($this->data[$key]);

        return $this;
    }

    /**
     * Remove all items from collection
     *
     * @return $this
     */
    public function clear()
    {
        $this->data = [];

        return $this;
    }

    /**
     * Collects and returns the values as array
     *
     * Collect value of the specified field
     * @usage $oc->where('login')
     *
     * Collect values of these fields
     * @usage $oc->where(['login', 'password'])
     *
     * Collect value of the specified field
     * The key is 'id' field value
     * @usage $oc->where('id', 'login')
     *
     * Collect values of these fields
     * The key is 'id' field value
     * @usage $oc->where('id', ['login', 'password'])
     *
     * @param string|array $field
     * @param string|array $value
     *
     * @return array
     */
    public function where($field, $value = null)
    {
        $data = [];

        // $oc->where('login')
        if (is_string($field) && is_null($value)) {
            foreach ($this->data as $model) {
                $data[] = $model[$field];
            }
        }

        // $oc->where(['login', 'password'])
        if (is_array($field)) {
            foreach ($this->data as $model) {
                $item = [];
                foreach ($field as $key) {
                    $item[$key] = $model[$key];
                }
                $data[] = $item;
            }
        }

        // $oc->where('id', 'login')
        if (is_string($field) && is_string($value)) {
            foreach ($this->data as $model) {
                $data[$model[$field]] = $model[$value];
            }
        }

        // $oc->where('id', ['login', 'password'])
        if (is_string($field) && is_array($value)) {
            foreach ($this->data as $model) {
                $item = [];
                foreach ($value as $key) {
                    $item[$key] = $model[$key];
                }
                $data[$model[$field]] = $item;
            }
        }

        return $data;
    }

    /**
     * Find all model parameter satisfy the condition
     *
     * Find all model wherein the field is not empty
     * @usage $oc->find('Location')
     *
     * Find all model wherein the field is equal to the specified value
     * @usage $oc->find('Location', 'Lviv')
     *
     * @param string $field
     * @param string $value
     *
     * @return $this
     */
    public function find($field, $value = null)
    {
        $data = [];

        if (is_string($field)) {
            if (is_null($value)) {
                // $oc->find('Location')
                foreach ($this->data as $obj) {
                    if (!empty($obj[$field])) {
                        $data[] = $obj;
                    }
                }
            } else {
                // $oc->find('Location', 'Lviv')
                foreach ($this->data as $obj) {
                    if ($obj[$field] == $value) {
                        $data[] = $obj;
                    }
                }
            }
        }

        return new $this($data);
    }

    /**
     * Filter models using user-defined function
     *
     * @param Closure $callable
     *
     * @return $this
     */
    public function filter($callable)
    {
        $data = [];

        if (is_callable($callable)) {
            foreach ($this->data as $key => $model) {
                if ($callable($model, $key)) {
                    $data[] = $model;
                }
            }
        }

        return new $this($data);
    }

    /**
     * Sort models
     *
     * Sort models for the specified field
     * @usage $oc->sort('id')
     *
     * Sort models with user-defined function
     * @usage $oc->sort(function(mixed $a, mixed $b, $args))
     *
     * @param Closure|string $param
     * @param mixed          $args
     *
     * @return $this
     */
    public function sort($param, $args = null)
    {
        if (is_string($param)) {
            usort($this->data, $this->sortProperty($param));
        } else if (is_callable($param)) {
            usort($this->data, $this->sortCallable($param, $args));
        }

        return $this;
    }

    /**
     * Sort by property
     *
     * @param string $key
     *
     * @return Closure
     */
    protected function sortProperty($key = null)
    {
        return function ($a, $b) use ($key) {
            return strnatcmp($a[$key], $b[$key]);
        };
    }

    /**
     * Sort function
     *
     * @param Closure $callable
     * @param mixed   $args
     *
     * @return Closure
     */
    protected function sortCallable($callable, $args = null)
    {
        return function ($a, $b) use ($callable, $args) {
            return $callable($a, $b, $args);
        };
    }

    /**
     * Returns current element of the array
     *
     * @return mixed
     */
    public function current()
    {
        $buf = $this->data[$this->key()];

        if (static::$model) {
            return new static::$model($buf);
        }

        return $buf;
    }

    /**
     * Move forward to next element
     *
     * @return $this
     */
    public function next()
    {
        $this->position++;

        return $this;
    }

    /**
     * Move forward to previously element
     *
     * @return $this
     */
    public function prev()
    {
        $this->position--;

        return $this;
    }

    /**
     * Returns current element key
     *
     * @return int
     */
    public function key()
    {
        $bufKeys = array_keys($this->data);

        if ($bufKeys && isset($bufKeys[$this->position])) {
            return $bufKeys[$this->position];
        }

        return false;
    }

    /**
     * Check current position of the iterator
     *
     * @return bool
     */
    public function valid()
    {
        return $this->key() !== false && isset($this->data[$this->key()]);
    }

    /**
     * Set iterator to the first element
     *
     * @return $this
     */
    public function rewind()
    {
        $this->position = 0;

        return $this;
    }

    /**
     * Returns number of elements of the object
     *
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get collection item for key
     *
     * @param string $key The data key
     *
     * @return mixed The key's value, or the default value
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * Get collection iterator
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /**
     * Return collection as string
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE);
    }
}
