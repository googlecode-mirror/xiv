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

require_once "xml.php";
require_once "archive.php";

class feedreader extends tm_object {
	private function date_to_time($date) {
		$months = array(
			'Jan' => 1,
			'Feb' => 2,
			'Mar' => 3,
			'Apr' => 4,
			'May' => 5,
			'Jun' => 6,
			'Jul' => 7,
			'Aug' => 8,
			'Sep' => 9,
			'Oct' => 10,
			'Nov' => 11,
			'Dec' => 12
		);
		$y = null;
		if (preg_match('/\w+,\s(\d+)\s(\w+)\s(\d+)\s(\d+):(\d+):(\d+)\s([+-])(\d+)/', $date, $match)) {
			$d = (int)$match[1];
			$m = (int)$months[$match[2]];
			$y = (int)$match[3];
			$h = (int)$match[4];
			$i = (int)$match[5];
			$s = (int)$match[6];
			$o = (int)$match[8];
			$f = ($match[7] == '+' ? 1 : -1);
		}
		if ($y) {
			$offset = $f*substr("$o", 0, 2)*60+substr("$o", 2);
			$time = mktime($h, $i, $s, $m, $d, $y);
			return $time + $offset;
		}
		return 0;
	}
	private function _sort($a, $b) {
		return ($this->date_to_time($b['pubdate']) - $this->date_to_time($a['pubdate']));
	}
	public function sort(&$data) {
		usort($data, array(&$this, '_sort'));
	}
	public function read($url) {
		extract(
			$this->using(
				'xml',
				'archive'
			)
		);
		$file = TM_TEMP_DIR.md5($url);
		@unlink($file);
		$archive->download($url, $file);
		$feed = array(
			'head' => array(),
			'body' => array()
		);
		if (file_exists($file)) {
			$xml = new xml();
			$xml->load($file);

			$nodes = $xml->get_nodes('rss/channel');

			if (!empty($nodes['_nodes'])) {
				foreach($nodes['_nodes'] as $i => $v) {
					$channel_node =& $nodes['_nodes'][$i]['channel'];
					if (!empty($channel_node['_nodes'])) {
						foreach($channel_node['_nodes'] as $j => $w) {
							reset($w);
							list($name) = each($w);
							if (preg_match('/^(copyright|pubdate|description|link|title)$/', $name)) {
								$feed['head'][$name] = $w[$name]['_data'];
							} else if ($name == 'item') {
								$item_node =& $w['item'];
								$item =& $feed['body'][];
								if (isset($item_node)) {
									foreach($item_node['_nodes'] as $k => $x) {
										list($name) = each($x);
										if (preg_match('/^(title|link|description|pubdate|guid)$/i', $name)) {
											$item[$name] = $x[$name]['_data'];
										}
									}
								}
							}
						}
					}
				}
			}
			return $feed;
		}
	}
}

?>
