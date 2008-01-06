<?php
/**
 * Format
 * Formats HTML and strips out possible harmful code based on a privileges
 * list.
 * ---
 * Copyright (c) 2007 Jose Carlos Nieto <xiam@menteslibres.org>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@menteslibres.org>
 * @copyright       Copyright (c) 2007-2008, Jose Carlos Nieto
 * @link            http://opensource.astrata.com.mx Astrata Open Source Projects
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

require 'engines/html_filter_engine.php';

define('RSS_DATE', '%a, %d %b %Y %H:%M:%S %z');

class format extends html_filter_engine {
	function intro($text, $size = 170, $maxword = 20) {

		$filter = new html_filter_engine();
		$filter->return_escaped_tag = false;
		$filter->allowed_tags_by_level = array(
			0 => 'img|a|b'
		);
		$filter->allowed_attr_by_level = array(
			0 => 'src|href|width|height'
		);
		$filter->filter_level = 0;
		$filter->filter(&$text);

		$buff = '';
		$len = strlen($text);
		$wc = 0;
		$st = 0;
		for ($i = 0; $i < $len; $i++) {
			$c = $text{$i};
			if ($st == 1) {
				// nothing
				if ($c == '>') {
					$st = 0;
				}
			} else if ($st == 0) {
				if ($c == '<') {
					$st = 1;
				} else {
					$cc = 0;
					while ($i < $len && preg_match('/[\s\t\n\r]/', $text{$i}) == false) {
						$buff .= $text{$i};
						if ($cc > $maxword) {
							$buff .= '... ';
							while ($i < $len && !preg_match('/[\s\t\n\r]/', $text{$i})) {
								$i++;
							}
							break;
						}
						$i++;
						$cc++;
					}
					$wc += $cc;
				}
			}
			$buff .= isset($text{$i}) ? $text{$i} : null;
			if ($wc > $size) {
				break;
			}
		}

		return $buff;
	}
	function apply_filter(&$text, $level = 0) {
		$this->filter_level = $level;
		$this->filter(&$text);
		return $text;
	}
	function html($text) {
		return htmlspecialchars($text);
	}
	function escape(&$text) {
		if (is_array(&$text)) {
			reset($text);
			while (list($i) = each($text))
				$this->escape($text[$i]);
		} else {
			$text = htmlspecialchars($text);
		}
	}

	function _code($attr = null, $inner = null) {
		if (isset($attr['lang'])) {
			$sc =& $this->plugin('source_color');
			//$sc = new source_color_plugin();
			if ($sc) {
				$sc->lang = $attr['lang'];
				$inner = preg_replace("/<br[^>]*?\/?>[\r\n]+/si", '', $inner);
				$inner = str_replace('&nbsp;', ' ', $inner);
				$inner = $sc->code(trim(html_entity_decode($inner)));
			}
		}
		return $inner;
	}

	function date($date = null, $format = null) {

		if (!is_integer($date)) {
			$date = preg_match('/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/', $date, $match);
			list($date, $y, $m, $d, $h, $i, $s) = $match;
			$time = mktime($h, $i, $s, $m, $d, $y);
		} else {
			if ($date) {
				$time = $date;
			} else {
				$time = time();
			}
		}

		if (!$format) {
			$format = __('%A %B %e, %Y at %H:%M');	

			$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
			$tomorrow = $today + 86400;
			$yesterday = $today - 86400;

			if ($time > $yesterday) {
				if ($time > $today) {
					if ($time < $tomorrow) {
						$format = __('Today at %H:%M');
					} else {
						if ($time < $tomorrow + 86400) {
							$format = __('Tomorrow at %H:%M');
						}
					}
				} else {
					$format = __('Yesterday at %H:%M');
				}
			}
		}

		return strftime($format, $time);
	}

	function fix_links(&$buff) {
		$buff = preg_replace_callback(
		'/<([^>]+)(action|src|href)="([^\.\/#][^"]*)"([^>]*?)>/',
		create_function('$a', 'return "<".$a[1]." $a[2]=\"".((preg_match("/[a-z]+:/", $a[3])) ? $a[3] : TM_BASE_URL.ltrim($a[3], "/"))."\"".$a[4].">";'),
		$buff);
	}


	public function emoticon($text) {
		$conf =& $this->using('conf');

		if ($conf->get('core/enable_emoticons', true, 'b')) {

			$emoticon_theme = $conf->get('core/emoticon_theme', 'default', 's');

			$theme = TM_MEDIA_DIR.'emoticons/'.$emoticon_theme.'/theme.ini';

			if (file_exists($theme)) {
				$data = parse_ini_file($theme, array('theme', 'icons'));

				$map = array(
					'pattern' => array(), 
					'callback' => array()
				);

				if (!empty($data['icons'])) {
					foreach($data['icons'] as $icon => $pattern) {
						$pattern = explode(' ', $pattern);
						foreach($pattern as $i => $v) {
							$pattern[$i] = preg_quote($v);
						}
						$pattern = implode('|', $pattern);
						$pattern = '/(<[^>]*>)|((?<=.\W|\W.|^\W|^)('.$pattern.')(?=.\W|\W.|\W$|$))/s';
						$callback = create_function('$a', 'return isset($a[3]) ? "<img src=\"'.TM_BASE_URL.'media/emoticons/'.$emoticon_theme.'/'.$icon.'.'.$data['theme']['extension'].'\" width=\"'.$data['theme']['width'].'\" height=\"'.$data['theme']['height'].'\" />" : $a[0];');
						$text = preg_replace_callback($pattern, $callback, $text);
					}
				}
			}
		}
		return $text;
	}

	public function display($text, $user_id = 0) {
		$auth =& $this->using('auth');
		$filter_level = 0;
		if ($auth->is_admin($user_id)) {
			$filter_level = 3;	
		} else if ($auth->is_operator($user_id)) {
			$filter_level = 2;	
		} else if ($auth->is_user($user_id)) {
			$filter_level = 1;	
		}
		return $this->emoticon($this->display_with_filter($text, $filter_level));
	}

	public function display_with_filter($text, $filter_level) {
		$this->style($text, $filter_level);
		if (!preg_match('/<br/i', $text) && strpos($text, "\n") !== false) {
			$text = str_replace("\n", '<br />', $text);
		}
		$this->fix_links($text);
		return trim($text);
	}

	function minify($buff, $factor = 2.5) {
		$buff = preg_replace_callback(
			'/(width|height)="(\d+)"/i',
			create_function('$a', 'return $a[1]."=\"".floor($a[2]/'.$factor.')."\"";'),
			$buff
		);
		$buff = preg_replace_callback(
			'/(width|height)\s*?:\s*(\d+)px/i',
			create_function('$a', 'return $a[1].": ".floor($a[2]/'.$factor.')."px";'),
			$buff
		);
		return $buff;
	}

	function feed($string) {
		$xml =& $this->using('xml');
		return preg_replace('/style="[^\"]*"/', '', $xml->escape($string));
	}

	function style(&$text, $filter_level = 0) {
		
		$this->apply_filter($text, $filter_level);

		$copy = $text;

		$stack = array();

		$step = 0;
		$orig = -1;

		for ($i = 0; isset($text{$i}); $i++) {

			switch ($step) {
				case 0:
					if ($text{$i} == '[') {
						$start = $i;
						if ($orig < 0) {
							$orig = $i;
						}
						$step = 1;
					}
				break;
				case 1:
					
					if ($text{$i} == ']') {

						$tag = substr($text, $start, $i - $start + 1);

						preg_match('/\[([\/a-z0-9]+)(\s[^$]+)?\]/', $tag, $m);

						$name = isset($m[1]) ? $m[1] : '';
						$attr_str = isset($m[2]) ? trim($m[2]) : '';

						if ($name) {
							if ($name[0] == '/') {
								if ($stack) {
									$pop = array_pop($stack);
									if ($pop == substr($name, 1)) {
										if (empty($stack)) {
											
											$block = substr($text, $orig, $i - $orig + 1);
											
											$orig_len = strlen($block);

											preg_match('/^\[([^\]]+)\](.*)\[\/([^\]]+)\]$/sm', $block, $m);
											if ($m) {
												$tag = $m[3];	
												$inner = $m[2];
												$attr_str = trim(substr($m[1], strlen($tag)));

												$attr_arr = array();
												if ($attr_str) {
													$a = array();
													preg_match_all("/([a-z0-9]+)=\"([^\"]+)\"/i", $attr_str, $a[]);
													preg_match_all("/([a-z0-9]+)='([^']+)'/i", $attr_str, $a[]);
													preg_match_all("/([a-z0-9]+)=([a-z0-9]+)/i", $attr_str, $a[]);
													foreach ($a as $t) {
														foreach ($t[0] as $j => $v) {
															$attr_arr[$t[1][$j]] = $t[2][$j];
														}
													}
												}

												if (method_exists($this, '_'.$tag)) {
													$call = "_{$tag}";
													$inner = preg_replace('/^(<br[^>]*>)+/', '', $inner);
													$inner = preg_replace('/(<br[^>]*>)+$/', '', $inner);
													$block = $this->$call($attr_arr, $inner);	
												}
												$text = substr($text, 0, $orig).$block.substr($text, $i + 1);
												$i = $orig + strlen($block) - 1;
												// reset
												$orig = -1;
												$step = 0;
											}
										}
									} else {
										array_unshift($stack, $pop);
									}
								} else {

								}
							} else {
								if (method_exists($this, '_'.$name)) {
									array_push($stack, $name);
								}
							}
						}
						$step = 0;
					}
				break;
			}
		}
		if ($stack)
			$text = $copy;
	}
	
	function tag_reassemble($a) {
		
		$node = $a[1];

		// getting referer document path
		$base = env('HTTP_REFERER');

		preg_match("/^([a-z]*:\/\/[^\/]*)\/(.*?)(index.php|$)/i", $base, $url);

		$host = $url[1];
		$docroot = '/'.(($url[3]) ? dirname($url[2]).'/' : $url[2]);

		$attr = format::split_attributes(isset($a[2]) ? $a[2] : null);

		// checking resources
		$src_arr = array('src', 'href', 'action');
		foreach ($src_arr as $src)
			if (isset($attr[$src])) {

				if (substr($attr[$src], 0 ,2) == '..') {
					$path = explode('/', $docroot);

					if (($del = strpos($attr[$src], '?')) !== false)
						$relative = substr($attr[$src], 0, $del);

					$rel = explode('/', isset($relative) ? $relative : $attr[$src]);

					for ($i = 0; $rel[$i] == '..'; $i++)
						array_pop($path);

					$attr[$src] = (count($path) ? implode('/', $path).'/' : '').substr($attr[$src], $i*3);
				}
			}


		switch ($node) {
			case 'img':

				if (!isset($attr['alt']))
					$attr['alt'] = htmlspecialchars(basename($attr['src']));

				if (!isset($attr['width']) || !isset($attr['height']))
					$attr['width'] = $attr['height'] = 0;

				if (substr($attr['src'], 0, 7) != 'http://' || substr($attr['src'], 0, strlen($_SERVER['HTTP_HOST'])) == $_SERVER['HTTP_HOST']) {

					$src = urldecode($attr['src']);

					$local_src = TM_SOURCE_DIR.ltrim($src, '/');

					if (file_exists($local_src)) {
						$size = @getimagesize($local_src);
						if (isset($size[0]) && isset($size[1])) {
							$attr['width'] = $size[0];
							$attr['height'] = $size[1];
						}
					}
				}

				// this line breaks the standards... but surely the user don't cares (she/he didn't provide a with nor a height attribute)
				if (0 == ($attr['width'] + $attr['height']))
					unset ($attr['width'], $attr['height']);
			break;
		}

		$attr = format::join_attributes($attr);

		return '<'.$node.($attr ? ' '.$attr: '').(format::is_single_tag($node) ? ' /': '').'>';
	}

	function editor_content_fix($buff) {
		if (preg_match("/^<br[^>]*?>$/", $buff)) {
			$buff = '';
		} else {
			$buff = preg_replace_callback('/<(\/?\w*)[\s\t\n\r]([^>]*?)\/?>/sm',
				create_function('$a', 'return (substr($a[0], 0, 4) == "<!--" && substr($a[0], -3) == "-->") ? $a[0] : format::tag_reassemble($a);'),
				$buff
			);
		}
		return $buff;
	}


}
?>
