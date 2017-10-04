<?php namespace Aert;

/**
 * Aert_Minifier 类
 *
 * 实现 HTML,CSS,JS 字符串压缩 
 *
 * @author 449211678@qq.com
 */
class Minifier
{

	public static function html($body)
	{
		$search = ['/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '/\n/', '/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '/<!--.*?-->/'];
		$replace = [' ', ' ', '>', '<', '\\1', ''];
		$html = preg_replace($search, $replace, $body);
		if (empty($html)) return $body;
		return $html;
	}

}