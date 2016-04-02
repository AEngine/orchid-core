<?php

namespace Orchid;

class Request {
	const METHOD_HEAD = "HEAD";
	const METHOD_GET = "GET";
	const METHOD_POST = "POST";
	const METHOD_PUT = "PUT";
	const METHOD_PATCH = "PATCH";
	const METHOD_DELETE = "DELETE";
	const METHOD_PURGE = "PURGE";
	const METHOD_OPTIONS = "OPTIONS";
	const METHOD_TRACE = "TRACE";
	const METHOD_CONNECT = "CONNECT";

	/**
	 * Массив заголовков запроса
	 *
	 * @var array
	 */
	protected static $headers = [];

	/**
	 * Подготавливает параметры запроса для дальнейшего использования
	 *
	 * @param string $query
	 * @param string $method
	 * @param array  $post
	 * @param array  $file
	 * @param array  $cookie
	 * @param array  $session
	 */
	public static function initialize($query, $method = "GET", $post = [], $file = [], $cookie = [], $session = []) {
		// наполняем массив заголовков
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == "HTTP_") {
				static::$headers[str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($name, 5)))))] = $value;
			}
		}

		// декодируем строку
		$query = urldecode($query);

		// записываем хост и порт
		Registry::set("host", parse_url($query, PHP_URL_HOST));
		Registry::set("port", parse_url($query, PHP_URL_PORT));

		// заполняем URI
		$uri = [];
		foreach (explode("/", parse_url($query, PHP_URL_PATH)) as $part) {
			if ($part) {
				$uri[] = $part;
			}
		}
		Registry::set("uri", $uri);
		Registry::set("method", $method);

		// переписываем GET
		parse_str(parse_url($query, PHP_URL_QUERY), $get);
		Registry::set("param", $get);

		// проверяем php://input и объединяем с $_POST
		if (Request::is("ajax")) {
			if ($json = json_decode(@file_get_contents("php://input"), true)) {
				$post = array_merge($_POST, $json);
			}
		}
		Registry::set("data", $post);
		Registry::set("file", $file);
		Registry::set("cookie", $cookie);
		Registry::set("session", $session);

		$_GET = $get;
		$_POST = $get;
		$_COOKIE = $cookie;
		$_REQUEST = array_merge($get, $post, $cookie);
	}

	/**
	 * @param $type
	 *
	 * @return bool|int
	 */
	public static function is($type) {
		switch (strtolower($type)) {
			case "ajax": {
				return (
					(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest") ||
					(isset($_SERVER["CONTENT_TYPE"]) && stripos($_SERVER["CONTENT_TYPE"], "application/json") !== false) ||
					(isset($_SERVER["HTTP_CONTENT_TYPE"]) && stripos($_SERVER["HTTP_CONTENT_TYPE"], "application/json") !== false)
				);
			}
			case "mobile": {
				$mobileDevices = [
					"midp", "240x320", "blackberry", "netfront", "nokia", "panasonic", "portalmmm",
					"sharp", "sie-", "sonyericsson", "symbian", "windows ce", "benq", "mda", "mot-",
					"opera mini", "philips", "pocket pc", "sagem", "samsung", "sda", "sgh-", "vodafone",
					"xda", "iphone", "ipod", "android",
				];

				return preg_match("/(" . implode("|", $mobileDevices) . ")/i", strtolower($_SERVER["HTTP_USER_AGENT"]));
			}
			case "head": {
				return (strtolower(Registry::get("method")) == "head");
			}
			case "put": {
				return (strtolower(Registry::get("method")) == "put");
			}
			case "post": {
				return (strtolower(Registry::get("method")) == "post");
			}
			case "get": {
				return (strtolower(Registry::get("method")) == "get");
			}
			case "delete": {
				return (strtolower(Registry::get("method")) == "delete");
			}
			case "options": {
				return (strtolower(Registry::get("method")) == "options");
			}
			case "trace": {
				return (strtolower(Registry::get("method")) == "trace");
			}
			case "connect": {
				return (strtolower(Registry::get("method")) == "connect");
			}
			case "secure": {
				return (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off");
			}
		}

		return false;
	}

	/**
	 * Возвращает значение заголовка по ключу
	 *
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public static function getHeader($key) {
		return isset(static::$headers[$key]) ? static::$headers[$key] : null;
	}

	/**
	 * Возвращает весь список заголовков
	 *
	 * @return array
	 */
	public static function getAllHeaders() {
		return static::$headers;
	}

	/**
	 * Возвращает IP адрес клиента
	 *
	 * @return string|null
	 */
	public static function getClientIp() {
		switch (true) {
			case isset($_SERVER["HTTP_X_FORWARDED_FOR"]): {
				return $_SERVER["HTTP_X_FORWARDED_FOR"];
			}
			case isset($_SERVER["HTTP_CLIENT_IP"]): {
				return $_SERVER["HTTP_CLIENT_IP"];
			}
			case isset($_SERVER["REMOTE_ADDR"]): {
				return $_SERVER["REMOTE_ADDR"];
			}
		}

		return null;
	}

	/**
	 * Возвращает наиболее подходящий язык браузера клиента из заданных в locale
	 *
	 * @param string $default по умолчанию русский
	 *
	 * @return string
	 */
	public static function getClientLang($default = "ru") {
		if (($list = strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]))) {
			if (preg_match_all("/([a-z]{1,8}(?:-[a-z]{1,8})?)(?:;q=([0-9.]+))?/", $list, $list)) {
				$language = [];

				foreach (array_combine($list[1], $list[2]) as $lang => $priority) {
					$language[$lang] = (float)($priority ? $priority : 1);
				}
				arsort($language, SORT_NUMERIC);

				foreach ($language as $lang => $priority) {
					if (in_array($lang, Registry::get("locale"))) {
						return $lang;
					}
				}
			}
		}

		return $default;
	}

	/**
	 * Возвращает схему
	 *
	 * @return string
	 */
	public static function getScheme() {
		return Request::is("ssl") ? "https" : "http";
	}

	/**
	 * Возвращает префикс приложения
	 *
	 * Если текущее приложение public, то будет возвращена пустая строка
	 *
	 * @param string $app принудительный выбор приложения
	 *
	 * @return string
	 */
	public static function getApp($app = "") {
		if (($app = (!$app ? Registry::get("app") : $app)) && $app != "public") {
			return $app . ".";
		}

		return "";
	}

	/**
	 * Возвращает базовое имя хоста
	 *
	 * @return mixed
	 */
	public static function getHost() {
		return Registry::get("base_host");
	}

	/**
	 * Возвращает порт
	 *
	 * Если порт текущий 80, то будет возвращена пустая строка
	 *
	 * @return string
	 */
	public static function getPort() {
		if (($port = Registry::get("base_port")) != 80) {
			return ":" . $port;
		}

		return "";
	}

	/**
	 * Возвращает путь
	 *
	 * @return string
	 */
	public static function getPath() {
		return "/" . implode("/", Registry::get("uri"));
	}

	/**
	 * Возвращает адрес страницы
	 *
	 * @param string $app
	 * @param bool   $withPath вернуть адрес со строкой запроса
	 *
	 * @return string
	 */
	public static function getUrl($app = "", $withPath = false) {
		$url = static::getScheme() . "://" . static::getApp($app) . static::getHost() . static::getPort();

		if ($withPath) {
			$url .= Request::getPath();
		}

		return rtrim($url, "/");
	}
}