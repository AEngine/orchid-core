<?php

namespace Orchid\Entity;

use Iterator;
use RuntimeException;
use Orchid\App;
use Orchid\Entity\Exception\FileNotFoundException;

final class Configuration implements Iterator {
	protected $position = 0;
	protected $array;

	/**
	 * Инициализация из указанного файла
	 *
	 * @param string $path
	 *
	 * @return static
	 * @throws FileNotFoundException
	 */
	public static function fromFile($path) {
		if ($path = App::getPath($path)) {
			$ext = pathinfo($path);

			switch ($ext["extension"]) {
				case "ini": {
					return new static((array)parse_ini_file($path, true));
				}
				case "php": {
					return new static((array)require_once $path);
				}
			}
		}

		throw new FileNotFoundException("Не удалось найти файл конфигурации");
	}

	/**
	 * Инициализация из переданного массива
	 *
	 * @param array $data
	 *
	 * @return static
	 */
	public static function fromArray(array $data) {
		return new static($data);
	}

	/**
	 * @param array $data
	 *
	 * @throws RuntimeException
	 */
	protected function __construct(array $data) {
		if (empty($data)) {
			throw new RuntimeException("Переданный массив пуст");
		}

		$this->array = $data;
	}

	/**
	 * Получение ключа из конфигурации
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public function get($key, $default = null) {
		if (isset($this->array[$key])) {
			return $this->array[$key];
		}

		return $default;
	}

	/* Методы Iterator */

	public function current() {
		return $this->array[$this->position];
	}

	public function next() {
		$this->position++;
	}

	public function prev() {
		$this->position--;
	}

	public function key() {
		return $this->position;
	}

	public function valid() {
		return isset($this->array[$this->position]);
	}

	public function rewind() {
		$this->position = 0;
	}
}