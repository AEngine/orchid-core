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

namespace Engine;

class Response {
	public $body    = "";
	public $status  = 200;
	public $mime    = "html";
	public $nocache = false;
	public $headers = [];

	protected $mimeTypes = array(
		//Texts
		"txt"    => "text/plain",
		"ini"    => "text/ini",
		"config" => "text/xml",

		//WWW
		"htm"    => "text/html",
		"html"   => "text/html",
		"tpl"    => "text/html",
		"css"    => "text/css",
		"less"   => "text/css",
		"js"     => "application/x-javascript",
		"json"   => "application/json",
		"xml"    => "application/xml",
		"swf"    => "application/x-shockwave-flash",

		//Images
		"jpe"    => "image/jpeg",
		"jpg"    => "image/jpeg",
		"jpeg"   => "image/jpeg",
		"png"    => "image/png",
		"bmp"    => "image/bmp",
		"gif"    => "image/gif",
		"tif"    => "image/tiff",
		"tiff"   => "image/tiff",
		"ico"    => "image/vnd.microsoft.icon",
		"svg"    => "image/svg+xml",
		"svgz"   => "image/svg+xml",

		//Fonts
		"eot"    => "application/vnd.ms-fontobject",
		"ttf"    => "application/font-ttf",
		"woff"   => "application/font-woff",

		//Audio
		"flac"   => "audio/x-flac",
		"mp3"    => "audio/mpeg",
		"wav"    => "audio/wav",
		"wma"    => "audio/x-ms-wma",

		//Video
		"qt"     => "video/quicktime",
		"mov"    => "video/quicktime",
		"mkv"    => "video/mkv",
		"mp4"    => "video/mp4",

		//Archive
		"7z"     => "application/x-7z-compressed",
		"zip"    => "application/x-zip-compressed",
		"rar"    => "application/x-rar-compressed",

		//Application
		"jar"    => "application/java-archive",
		"java"   => "application/octet-stream",
		"exe"    => "application/octet-stream",
		"msi"    => "application/octet-stream",
		"dll"    => "application/x-msdownload",

		//Other
		"none"   => "text/plain",
	);

	public function flush() {
		if (!headers_sent()) {
			if ($this->nocache || !empty($_SERVER["HTTPS"])) {
				header("Cache-Control: no-cache, must-revalidate");
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
				header("Pragma: no-cache");
			} else {
				header("Cache-Control: public, store, cache, no-validate");
				header("Expires: " . gmdate("D, d M Y H:i:s", (time() + (60 * 60 * 24))) . " GMT");
			}

			foreach ($this->headers as $key => $val) {
				header($key . ": " . $val);
			}

			http_response_code($this->status);

			if (is_array($this->body)) {
				$this->mime = "json";
				$this->body = json_encode($this->body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			}
			if (!is_file($this->body)) {
				header("Content-Type: " . $this->mimeTypes[$this->mime] . "; charset=utf-8");

				echo trim($this->body);
			} else {
				header("Content-Type: " . $this->mimeTypes[@end(explode(".", basename($this->body)))] . "; charset=utf-8");
				header("Content-Disposition: inline; filename='" . basename($this->body) . "'");

				if (strpos($_SERVER['SERVER_SOFTWARE'], "nginx") !== false) {
					header("X-Accel-Redirect: " . str_replace(ORCHID, "", $this->body));
				}
				elseif (strpos($_SERVER['SERVER_SOFTWARE'], "Apache") !== false) {
					header("X-SendFile: " . str_replace(ORCHID, "", $this->body));
				}
				else {
					readfile($this->body);
				}
			}
		}
	}
}