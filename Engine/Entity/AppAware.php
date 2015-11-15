<?php

/*
 * Copyright (c) 2011-2016 AEngine
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Engine\Entity;

use Engine\Orchid;

abstract class AppAware {
	/** @var Orchid */
	protected $app;

	public function __construct() {
		$this->app = &Orchid::getInstance();
	}

	protected function __invoke($extension) {
		return $this->app->extension($extension);
	}

	protected function __call($key, $arguments) {
		if (method_exists($this->app, $key)) {
			return call_user_func_array([$this->app, $key], $arguments);
		}

		return $this;
	}

	protected function __get($key) {
		return $this->app[$key];
	}

	protected function __set($key, $value) {
		$this->app[$key] = $value;
	}
}