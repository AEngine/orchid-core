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

namespace Orchid\Entity\Validate;

use Closure;

trait String {
	/**
	 * Экранирует строку
	 * @return Closure
	 */
	public function escape() {
		return function (&$field) {
			$field = str_replace(
				["'", '"', ">", "<", "`", "\\"],
				["&#039;", "&#34;", "&#62;", "&#60;", "&#96;", "&#92;"],
				$field
			);

			return true;
		};
	}

	/**
	 * Проверяемое значение это E-Mail адрес
	 * @return Closure
	 */
	public function isEmail() {
		return function ($field) {
			return !!filter_var($field, FILTER_VALIDATE_EMAIL);
		};
	}

	/**
	 * Проверяемое значение это IP адрес
	 * @return Closure
	 */
	public function isIp() {
		return function ($field) {
			return !!filter_var($field, FILTER_VALIDATE_IP);
		};
	}
}