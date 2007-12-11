<?php

/**
 * XML Parser Engine
 * A wrapper for working with XML files.
 * ---
 * Written by Jose Carlos Nieto <xiam@astrata.com.mx>
 * Copyright (c) 2007 Astrata Software S.A. de C.V.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@astrata.com.mx>
 * @copyright       Copyright (c) 2007, Astrata Software S.A. de C.V.
 * @link            http://opensource.astrata.com.mx Astrata Open Source Projects
 * @version         $Revision$
 * @modifiedby      $Last_changed_by: $
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

class xml_parser_engine {
	
	private $entities = array (
		'&nbsp;' => '&#xA0;',
		'&iexcl;' => '&#xA1;',
		'&cent;' => '&#xA2;',
		'&pound;' => '&#xA3;',
		'&curren;' => '&#xA4;',
		'&yen;' => '&#xA5;',
		'&brvbar;' => '&#xA6;',
		'&sect;' => '&#xA7;',
		'&uml;' => '&#xA8;',
		'&copy;' => '&#xA9;',
		'&ordf;' => '&#xAA;',
		'&laquo;' => '&#xAB;',
		'&not;' => '&#xAC;',
		'&shy;' => '&#xAD;',
		'&reg;' => '&#xAE;',
		'&macr;' => '&#xAF;',
		'&deg;' => '&#xB0;',
		'&plusmn;' => '&#xB1;',
		'&sup2;' => '&#xB2;',
		'&sup3;' => '&#xB3;',
		'&acute;' => '&#xB4;',
		'&micro;' => '&#xB5;',
		'&para;' => '&#xB6;',
		'&middot;' => '&#xB7;',
		'&cedil;' => '&#xB8;',
		'&sup1;' => '&#xB9;',
		'&ordm;' => '&#xBA;',
		'&raquo;' => '&#xBB;',
		'&frac14;' => '&#xBC;',
		'&frac12;' => '&#xBD;',
		'&frac34;' => '&#xBE;',
		'&iquest;' => '&#xBF;',
		'&Agrave;' => '&#xC0;',
		'&Aacute;' => '&#xC1;',
		'&Acirc;' => '&#xC2;',
		'&Atilde;' => '&#xC3;',
		'&Auml;' => '&#xC4;',
		'&Aring;' => '&#xC5;',
		'&AElig;' => '&#xC6;',
		'&Ccedil;' => '&#xC7;',
		'&Egrave;' => '&#xC8;',
		'&Eacute;' => '&#xC9;',
		'&Ecirc;' => '&#xCA;',
		'&Euml;' => '&#xCB;',
		'&Igrave;' => '&#xCC;',
		'&Iacute;' => '&#xCD;',
		'&Icirc;' => '&#xCE;',
		'&Iuml;' => '&#xCF;',
		'&ETH;' => '&#xD0;',
		'&Ntilde;' => '&#xD1;',
		'&Ograve;' => '&#xD2;',
		'&Oacute;' => '&#xD3;',
		'&Ocirc;' => '&#xD4;',
		'&Otilde;' => '&#xD5;',
		'&Ouml;' => '&#xD6;',
		'&times;' => '&#xD7;',
		'&Oslash;' => '&#xD8;',
		'&Ugrave;' => '&#xD9;',
		'&Uacute;' => '&#xDA;',
		'&Ucirc;' => '&#xDB;',
		'&Uuml;' => '&#xDC;',
		'&Yacute;' => '&#xDD;',
		'&THORN;' => '&#xDE;',
		'&szlig;' => '&#xDF;',
		'&agrave;' => '&#xE0;',
		'&aacute;' => '&#xE1;',
		'&acirc;' => '&#xE2;',
		'&atilde;' => '&#xE3;',
		'&auml;' => '&#xE4;',
		'&aring;' => '&#xE5;',
		'&aelig;' => '&#xE6;',
		'&ccedil;' => '&#xE7;',
		'&egrave;' => '&#xE8;',
		'&eacute;' => '&#xE9;',
		'&ecirc;' => '&#xEA;',
		'&euml;' => '&#xEB;',
		'&igrave;' => '&#xEC;',
		'&iacute;' => '&#xED;',
		'&icirc;' => '&#xEE;',
		'&iuml;' => '&#xEF;',
		'&eth;' => '&#xF0;',
		'&ntilde;' => '&#xF1;',
		'&ograve;' => '&#xF2;',
		'&oacute;' => '&#xF3;',
		'&ocirc;' => '&#xF4;',
		'&otilde;' => '&#xF5;',
		'&ouml;' => '&#xF6;',
		'&divide;' => '&#xF7;',
		'&oslash;' => '&#xF8;',
		'&ugrave;' => '&#xF9;',
		'&uacute;' => '&#xFA;',
		'&ucirc;' => '&#xFB;',
		'&uuml;' => '&#xFC;',
		'&yacute;' => '&#xFD;',
		'&thorn;' => '&#xFE;',
		'&yuml;' => '&#xFF;',
		'&fnof;' => '&#x192;',
		'&Alpha;' => '&#x391;',
		'&Beta;' => '&#x392;',
		'&Gamma;' => '&#x393;',
		'&Delta;' => '&#x394;',
		'&Epsilon;' => '&#x395;',
		'&Zeta;' => '&#x396;',
		'&Eta;' => '&#x397;',
		'&Theta;' => '&#x398;',
		'&Iota;' => '&#x399;',
		'&Kappa;' => '&#x39A;',
		'&Lambda;' => '&#x39B;',
		'&Mu;' => '&#x39C;',
		'&Nu;' => '&#x39D;',
		'&Xi;' => '&#x39E;',
		'&Omicron;' => '&#x39F;',
		'&Pi;' => '&#x3A0;',
		'&Rho;' => '&#x3A1;',
		'&Sigma;' => '&#x3A3;',
		'&Tau;' => '&#x3A4;',
		'&Upsilon;' => '&#x3A5;',
		'&Phi;' => '&#x3A6;',
		'&Chi;' => '&#x3A7;',
		'&Psi;' => '&#x3A8;',
		'&Omega;' => '&#x3A9;',
		'&alpha;' => '&#x3B1;',
		'&beta;' => '&#x3B2;',
		'&gamma;' => '&#x3B3;',
		'&delta;' => '&#x3B4;',
		'&epsilon;' => '&#x3B5;',
		'&zeta;' => '&#x3B6;',
		'&eta;' => '&#x3B7;',
		'&theta;' => '&#x3B8;',
		'&iota;' => '&#x3B9;',
		'&kappa;' => '&#x3BA;',
		'&lambda;' => '&#x3BB;',
		'&mu;' => '&#x3BC;',
		'&nu;' => '&#x3BD;',
		'&xi;' => '&#x3BE;',
		'&omicron;' => '&#x3BF;',
		'&pi;' => '&#x3C0;',
		'&rho;' => '&#x3C1;',
		'&sigmaf;' => '&#x3C2;',
		'&sigma;' => '&#x3C3;',
		'&tau;' => '&#x3C4;',
		'&upsilon;' => '&#x3C5;',
		'&phi;' => '&#x3C6;',
		'&chi;' => '&#x3C7;',
		'&psi;' => '&#x3C8;',
		'&omega;' => '&#x3C9;',
		'&thetasym;' => '&#x3D1;',
		'&upsih;' => '&#x3D2;',
		'&piv;' => '&#x3D6;',
		'&bull;' => '&#x2022;',
		'&hellip;' => '&#x2026;',
		'&prime;' => '&#x2032;',
		'&Prime;' => '&#x2033;',
		'&oline;' => '&#x203E;',
		'&frasl;' => '&#x2044;',
		'&weierp;' => '&#x2118;',
		'&image;' => '&#x2111;',
		'&real;' => '&#x211C;',
		'&trade;' => '&#x2122;',
		'&alefsym;' => '&#x2135;',
		'&larr;' => '&#x2190;',
		'&uarr;' => '&#x2191;',
		'&rarr;' => '&#x2192;',
		'&darr;' => '&#x2193;',
		'&harr;' => '&#x2194;',
		'&crarr;' => '&#x21B5;',
		'&lArr;' => '&#x21D0;',
		'&uArr;' => '&#x21D1;',
		'&rArr;' => '&#x21D2;',
		'&dArr;' => '&#x21D3;',
		'&hArr;' => '&#x21D4;',
		'&forall;' => '&#x2200;',
		'&part;' => '&#x2202;',
		'&exist;' => '&#x2203;',
		'&empty;' => '&#x2205;',
		'&nabla;' => '&#x2207;',
		'&isin;' => '&#x2208;',
		'&notin;' => '&#x2209;',
		'&ni;' => '&#x220B;',
		'&prod;' => '&#x220F;',
		'&sum;' => '&#x2211;',
		'&minus;' => '&#x2212;',
		'&lowast;' => '&#x2217;',
		'&radic;' => '&#x221A;',
		'&prop;' => '&#x221D;',
		'&infin;' => '&#x221E;',
		'&ang;' => '&#x2220;',
		'&and;' => '&#x2227;',
		'&or;' => '&#x2228;',
		'&cap;' => '&#x2229;',
		'&cup;' => '&#x222A;',
		'&int;' => '&#x222B;',
		'&there4;' => '&#x2234;',
		'&sim;' => '&#x223C;',
		'&cong;' => '&#x2245;',
		'&asymp;' => '&#x2248;',
		'&ne;' => '&#x2260;',
		'&equiv;' => '&#x2261;',
		'&le;' => '&#x2264;',
		'&ge;' => '&#x2265;',
		'&sub;' => '&#x2282;',
		'&sup;' => '&#x2283;',
		'&nsub;' => '&#x2284;',
		'&sube;' => '&#x2286;',
		'&supe;' => '&#x2287;',
		'&oplus;' => '&#x2295;',
		'&otimes;' => '&#x2297;',
		'&perp;' => '&#x22A5;',
		'&sdot;' => '&#x22C5;',
		'&lceil;' => '&#x2308;',
		'&rceil;' => '&#x2309;',
		'&lfloor;' => '&#x230A;',
		'&rfloor;' => '&#x230B;',
		'&lang;' => '&#x2329;',
		'&rang;' => '&#x232A;',
		'&loz;' => '&#x25CA;',
		'&spades;' => '&#x2660;',
		'&clubs;' => '&#x2663;',
		'&hearts;' => '&#x2665;',
		'&diams;' => '&#x2666;',
		'&quot;' => '&#x22;',
		'&amp;' => '&#x26;',
		'&lt;' => '&#x3C;',
		'&gt;' => '&#x3E;',
		'&OElig;' => '&#x152;',
		'&oelig;' => '&#x153;',
		'&Scaron;' => '&#x160;',
		'&scaron;' => '&#x161;',
		'&Yuml;' => '&#x178;',
		'&circ;' => '&#x2C6;',
		'&tilde;' => '&#x2DC;',
		'&ensp;' => '&#x2002;',
		'&emsp;' => '&#x2003;',
		'&thinsp;' => '&#x2009;',
		'&zwnj;' => '&#x200C;',
		'&zwj;' => '&#x200D;',
		'&lrm;' => '&#x200E;',
		'&rlm;' => '&#x200F;',
		'&ndash;' => '&#x2013;',
		'&mdash;' => '&#x2014;',
		'&lsquo;' => '&#x2018;',
		'&rsquo;' => '&#x2019;',
		'&sbquo;' => '&#x201A;',
		'&ldquo;' => '&#x201C;',
		'&rdquo;' => '&#x201D;',
		'&bdquo;' => '&#x201E;',
		'&dagger;' => '&#x2020;',
		'&Dagger;' => '&#x2021;',
		'&permil;' => '&#x2030;',
		'&lsaquo;' => '&#x2039;',
		'&rsaquo;' => '&#x203A;',
		'&euro;' => '&#x20AC;',
	);


	public $source = null;
	
	/**
	 * XML data tree
	 * @var array
	 * @access private
	 */
	public $data = array();

	/**
	 * General pourpose pointers
	 * @access private
	 */
	private $_esp = null;
	private $_psp = null;

	/**
	 * Constructor that initializes the parser.
	 */
	public function __construct() {
		$this->parser = xml_parser_create();
		xml_set_object($this->parser, $this);
	}

	public function tag($name, $attr = null, $data = null) {
		if (!$attr)
			$attr = array();
		$return = array($name => array('_attr' => $attr, '_nodes' => array(), '_data' => null));
		if (is_array($data)) {
			$return[$name]['_nodes'] = $data;
		} else {
			$return[$name]['_data'] = $data;
		}
		return $return;
	}

	private function html_entity_encode($string) {
		$string = htmlspecialchars($string);
		return preg_replace("/&[A-Za-z]+;/", " ", strtr($string, $this->entities));
	}

	/**
	 * Lowercases a string or an array
	 * @param mixed &$str Pointer to the variable to be lowercased, if an array is passed its keys will be lowercased.
	 */
	public function lower(&$str) {
		if (is_array($str)) {
			$c = array();
			while (list($i) = each($str)) 
				$c[strtolower($i)] = $str[$i];
			$str = $c;
		} else {
			$str = strtolower($str);
		}
	}

	/**
	 * Starting tag handler
	 * @param object $p XML parser
	 * @param string $tag Name of the XML tag
	 * @param array $attr Tag attributes
	 */
	private function tag_start($p, $tag, $attr) {
		$this->lower($tag);
		$this->lower($attr);

		$this->_psp =& $this->_esp['_nodes'];
		$parent =& $this->_esp;

		$this->_esp =& $this->_psp[];

		$this->_esp = array(
			$tag => array(
				'_attr' => $attr,
				'_nodes' => array(),
				'_data' => null
			)
		);
		$this->_esp =& $this->_esp[$tag];
		$this->_esp['_parent'] =& $parent;
	}

	/**
	 * Ending tag handler
	 * @param object $p XML Parser
	 * @param string $tag Ending XML tag
	 */
	private function tag_end($p, $tag) {
		$this->lower($tag);
		$this->_esp =& $this->_esp['_parent'];
	}

	/**
	 * Tag content handler
	 * @param object $p XML Parser
	 * @param string $content XML tag data
	 */
	private function tag_content($p, $content) {
		$this->_esp['_data'] .= $content;
	}

	/**
	 * Parses the data in the buffer
	 */
	public function parse_buff($buff = null) {
		if ($buff) {
			$this->buff = $buff."\n";
		}
		$this->data = array('_nodes' => array());
		$this->_esp =& $this->data;
		xml_set_element_handler($this->parser, 'tag_start', 'tag_end');
		xml_set_character_data_handler($this->parser, 'tag_content');
		xml_parse($this->parser, $this->buff) or $this->error();
	}	

	/**
	 * Shows when a syntax error ocurred
	 */
	public function error() {
		// TODO: This public function is ugly as hell
		trigger_error(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($this->parser)), xml_get_current_line_number($this->parser)), E_USER_WARNING);
	}

	/**
	 * Gets all children nodes
	 * @param string $name Path of the node
	 * @param string $parent Node where to start searching
	 */
	public function get_tree($name, $parent = null) {

		if (!$parent) {
			$parent =& $this->data;
		}

		$return = null;

		$name = explode('/', $name);

		$p =& $parent;
		$n =& $p['_nodes'];

		foreach($name as $node) {
			$found = false;
			$total = count($n);
			for ($i = 0; $i < $total; $i++) {
				if (isset($n[$i][$node])) {
					$p =& $n[$i][$node];
					$n =& $p['_nodes'];
					$found = true;
				}
			}
			if ($found == false) {
				$m = $this->tag($node);
				$m[$node]['_parent'] =& $p;
				$n[] = $m;
				$p =& $n[count($n)-1][$node];
				$n =& $p['_nodes'];
			}
		}

		return $p;
	}

	/**
	 * Gets all children nodes
	 * @param string $name Path of the node
	 * @param string $parent Node where to start searching
	 */
	public function get_nodes($name, $parent = null) {

		if (!$parent)
			$parent =& $this->data;

		$return = null;

		$name = explode('/', $name);
		$meta = array_pop($name);

		$p =& $parent;
		$n =& $p['_nodes'];

		if (is_array($n)) {

			foreach($name as $node) {
				$found = false;
				$total = count($n);
				for ($i = 0; $i < $total; $i++) {
					if (isset($n[$i][$node])) {
						$p =& $n[$i][$node];
						$n =& $p['_nodes'];
						$found = true;
					}
				}
				if ($found == false) {
					$m = $this->tag($node);
					$m[$node]['_parent'] =& $p;
					$n[] = $m;
					$p =& $n[count($n)-1][$node];
					$n =& $p['_nodes'];
				}
			}

			$return = array('_nodes' => array());
			$results = 0;
			$total = count($n);

			for ($i = 0; $i < $total; $i++) {
				if (isset($n[$i][$meta])) {
					$return['_nodes'][] =& $n[$i];
					$results++;
				}
			}

			if ($results == 0) {
				$m = $this->tag($meta);
				$m[$meta]['_parent'] =& $p;
				$n[] = $m;
				$p =& $n[count($n)-1][$meta];
				$return['_nodes'] =& $p['_nodes'];
			}
		}
		return $return;
	}

	public function set_nodes(&$node, $nodes) {
		$node['_nodes'] = $nodes;
	}

	public function set_attribute(&$node, $name, $value = null) {
		if (!$value)
			$value = $name;
		reset($node);
		list($i) = each($node);
		$node[$i]['_attr'][$name] = $value;
	}

	public function load($path) {
		$this->buff = null;
		$this->__construct();
		$this->source = $path;
		if (filesize($path)) {
			$f = fopen($path, 'r');
			debug($path);
			debug($this->buff);
			$this->buff = fread($f, filesize($path));
			fclose($f);
			if (trim($this->buff)) {
				return $this->parse_buff();
			}
		}
		return false;
	}

	public function compile(&$buff, $level = 0) {
		$return = array();
		if (is_array($buff)) {
			$tab = str_repeat("\t", $level);
			foreach ($buff as $i => $node) {
				reset($node);
				list($name, $cont) = each($node);
				$attr = array();
				foreach ($cont['_attr'] as $a => $v) {
					$attr[] = "$a=\"".htmlentities($v)."\"";
				}
				$attr = $attr ? ' '.implode(' ', $attr) : null;
				$inner = trim($this->escape($cont['_data'])).$this->compile($cont['_nodes'], $level + 1);
				
				if (strlen($inner) < 50) {
					$return[] = "{$tab}<{$name}{$attr}>{$inner}</{$name}>\n";
				} else {
					$return[] = "{$tab}<{$name}{$attr}>\n{$inner}\n{$tab}</{$name}>\n";
				}
			}
			return rtrim(implode("", $return));
		}
		return null;
	}

	public function save($dest = null) {
		if (!$dest)
			$dest = $this->source;
		$buff = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$buff .= $this->compile($this->data['_nodes']);
		$buff .= "\n";
		$f = fopen($dest, 'w');
		fwrite($f, $buff);
		fclose($f);
	}

	public function escape($string) {
		$string = $this->html_entity_encode($string);
		$string = preg_replace('/style="[^\"]*"/', '', $string);
		return $string;
	}
}
?>
