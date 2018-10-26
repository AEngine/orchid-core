<?php

namespace AEngine\Orchid;

use AEngine\Orchid\Interfaces\CollectionInterface;
use ArrayIterator;
use JsonSerializable;
use Traversable;

class CollectionBase implements CollectionInterface
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
    protected $items = [];

    /**
     * Create a new collection.
     *
     * @param mixed $items
     *
     * @return void
     */
    final public function __construct($items = [])
    {
        $this->replace(static::getArrayByItems($items));
    }

    /**
     * Returns element that corresponds to the specified index
     *
     * @param int   $key
     * @param mixed $default
     *
     * @return mixed
     * @internal param int $index
     */
    public function get($key, $default = null)
    {
        return $this->offsetGet($key) ?? $default;
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
        return $this->offsetSet($key, $value);
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
     * Get all items in collection
     *
     * @return array The collection's source data
     */
    public function all()
    {
        return $this->items;
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
        return $this->offsetExists($key);
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
        return $this->offsetUnset($key);
    }

    /**
     * Remove all items from collection
     *
     * @return $this
     */
    public function clear()
    {
        $this->items = [];

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
            foreach ($this->items as $model) {
                $data[] = $model[$field];
            }
        }

        // $oc->where(['login', 'password'])
        if (is_array($field)) {
            foreach ($this->items as $model) {
                $item = [];
                foreach ($field as $key) {
                    $item[$key] = $model[$key];
                }
                $data[] = $item;
            }
        }

        // $oc->where('id', 'login')
        if (is_string($field) && is_string($value)) {
            foreach ($this->items as $model) {
                $data[$model[$field]] = $model[$value];
            }
        }

        // $oc->where('id', ['login', 'password'])
        if (is_string($field) && is_array($value)) {
            foreach ($this->items as $model) {
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
                foreach ($this->items as $obj) {
                    if (!empty($obj[$field])) {
                        $data[] = $obj;
                    }
                }
            } else {
                // $oc->find('Location', 'Lviv')
                foreach ($this->items as $obj) {
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
     * @param callable $callable
     *
     * @return $this
     */
    public function filter(callable $callable = null)
    {
        $data = [];

        if (is_callable($callable)) {
            foreach ($this->items as $key => $model) {
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
     * @param callable|string $param
     * @param mixed $args
     *
     * @return $this
     */
    public function assort($param, $args)
    {
        if (is_string($param)) {
            usort($this->items, $this->sortProperty($param));
        } else if (is_callable($param)) {
            usort($this->items, $this->sortCallable($param, $args));
        }

        return $this;
    }

    /**
     * Sort by property
     *
     * @param string $key
     *
     * @return callable
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
     * @param callable $callable
     * @param mixed    $args
     *
     * @return callable
     */
    protected function sortCallable(callable $callable, $args = null)
    {
        return function ($a, $b) use ($callable, $args) {
            return $callable($a, $b, $args);
        };
    }

    /**
     * Results array of items from Collection or Array.
     *
     * @param mixed $items
     *
     * @return array
     */
    protected static function getArrayByItems($items)
    {
        switch (true) {
            case is_array($items):
                return $items;

            case $items instanceof self:
                return $items->all();

            case $items instanceof JsonSerializable:
                return $items->jsonSerialize();

            case $items instanceof Traversable:
                return iterator_to_array($items);
        }

        return (array)$items;
    }

    /**
     * Iterator position
     *
     * @var int
     */
    protected $position = 0;

    /**
     * Returns current element of the array
     *
     * @return mixed
     */
    public function current()
    {
        $buf = $this->items[$this->key()];

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
        $bufKeys = array_keys($this->items);

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
        return $this->key() !== false && isset($this->items[$this->key()]);
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
        return count($this->items);
    }

    /**
     * Does this collection have a given key?
     *
     * @param string $key The data key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
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
        if (static::$model) {
            return new static::$model($this->items[$key]);
        }

        return $this->items[$key];
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     *
     * @return $this
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            if ($value instanceof Model) {
                $this->items[] = $value->toArray();
            } else {
                $this->items[] = $value;
            }
        } else {
            if ($value instanceof Model) {
                $this->items[$key] = $value->toArray();
            } else {
                $this->items[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     *
     * @return $this
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);

        return $this;
    }

    /**
     * Get collection iterator
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     */
    public function jsonSerialize()
    {
        return $this->__toString();
    }

    /**
     * Return collection as string
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->items, JSON_UNESCAPED_UNICODE);
    }
}
