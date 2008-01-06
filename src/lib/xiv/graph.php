<?php

/**
 * textMotion
 * ---
 * Written by Jose Carlos Nieto <xiam@menteslibres.org>
 * Copyright (c) 2007-2008, Jose Carlos Nieto <xiam@menteslibres.org>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author          Jose Carlos Nieto <xiam@menteslibres.org>
 * @package         textMotion
 * @copyright       Copyright (c) 2007-2008, J. Carlos Nieto <xiam@menteslibres.org>
 * @link            http://www.textmotion.org
 * @version         $Revision$
 * @modifiedby      $LastChangedBy$
 * @lastmodified    $Date$
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

class graph {
	private $data = array();
	private $title = '';
	private $img = null;
	private $integer_chart = true;

	private $colors = array(
		array(0x00, 0x3e, 0xff),
		array(0x2c, 0xd5, 0x00),
		array(0xff, 0x88, 0x00),
		array(0xb8, 0x00, 0xd5),
		array(0xd1, 0xd5, 0x00)
	);

	private function image() {
		$this->img = imagecreatetruecolor($this->width, $this->height);
		imagefill($this->img, 0, 0, $this->color(0xff, 0xff, 0xff));
	}

	private function color($red, $green, $blue) {
		return imagecolorallocate($this->img, $red, $green, $blue);
	}
	
	private function text($x, $y, $text, $color) {
		if ($x < 0) $x = $this->width + $x;
		if ($y < 0) $y = $this->height + $y;
		imagestring($this->img, 2, $x, $y, $text, $color);
	}

	private function vtext($x, $y, $text, $color) {
		if ($x < 0) $x = $this->width + $x;
		if ($y < 0) $y = $this->height + $y;
		imagestringup($this->img, 2, $x, $y, $text, $color);
	}

	private function linecolor() {
		if (!isset($this->current_color)) {
			$this->current_color = 0;
		}
		$color = $this->colors[$this->current_color%count($this->colors)];
		$this->current_color++;
		return $this->color($color[0], $color[1], $color[2]);
	}

	private function add_label($text, $color) {
		if (!isset($this->labels)) {
			$this->labels = 0;
		}
		imagestring($this->img, 2, 15+$this->labels*100, 7, $text, $color);
		$this->labels++;
	}

	private function vline($cte, $y1, $y2, $color) {
		$this->line($cte, $y1, $cte, $y2, $color);
	}

	private function hline($cte, $x1, $x2, $color) {
		$this->line($x1, $cte, $x2, $cte, $color);
	}

	private function line($x1, $y1, $x2, $y2, $color) {
		if ($x1 < 0) $x1 = $this->width + $x1;
		if ($y1 < 0) $y1 = $this->height + $y1;
		if ($x2 < 0) $x2 = $this->width + $x2;
		if ($y2 < 0) $y2 = $this->height + $y2;
		imageline($this->img, $x1, $y1, $x2, $y2, $color);
	}

	private function point($x, $y, $color) {
		$this->line($x, $y, $x, $y, $color);
	}


	public function __construct($width = 0, $height = 0) {
		if ($width) {
			$this->dimensions($width, $height);
		}
	}

	public function buff() {
		ob_start();
		$this->draw();
		imagepng($this->img);
		return ob_get_clean();
	}

	public function dimensions($width, $height) {
		$this->width = $width;
		$this->height = $height;
	}

	public function title($title) {
		$this->title = $title;
	}

	public function data($title, $value = null) {
		if (is_array($title)) {
			foreach($title as $t => $v) {
				$this->data($t, $v);
			} 
		} else {
			if ($title) {
				$this->data[$title] = $value;
			} else {
				$this->data[] = $value;
			}
		}
	}

	public function draw() {

		$this->image();

		$max = 0;
		$maxt = 0;

		$count = 0;

		foreach($this->data as $title => $data) {
			if (count($data) > $count) {
				$count = count($data);
			}
			foreach($data as $t => $v) {
				if ($v > $max) {
					$max = $v;
				}
				if (strlen($t) > $maxt) {
					$maxt = strlen($t);
				}
			}
		}

		if ($max) {
			$max = $max-$max%pow(10, floor(log10($max)))+pow(10, floor(log10($max)));
		} else {
			$max = 1;
		}

		$padding = array(
			'left' => strlen($max)*14,
			'top' => 30,
			'right' => 10,
			'bottom' => $maxt*10
		);

		$spanx = ($this->width - $padding['left'] - $padding['right']);
		$spany = ($this->height - $padding['top'] - $padding['bottom']);

		if ($max) {
			// drawing grid
			$hline = pow(10, floor(log10($max)));
			$tline = $hline/2;

			$step = $tline*$spany/$hline;

			$log = strlen($max)+1;

			for ($i = 0; $i <= $max; $i += $tline) {
				if ($i%$hline == 0) {
					$color = $this->color(0xdd, 0xdd, 0xdd);
				} else {
					$color = $this->color(0xee, 0xee, 0xee);
				}
				if (!$this->integer_chart || !preg_match('/[^0-9]/', $i)) {
					$y = -1*$padding['bottom']-($i*$spany/$max);
					$this->hline($y, $padding['left'], -1*$padding['right'], $color);
					
					$label = $i;
					while (strlen($label) < $log) {
						$label = ' '.$label;
					}
					$this->text($padding['left']-$log*7, $y-6, $label, $this->color(0, 0, 0));
				}
			}
			// end grid

			// drawing data
			$labels = -1;
			foreach($this->data as $title => $data) {

				$i = 0;
				$lx = $ly = 0;
				$stepx = $spanx/($count+1);
				$color = $this->linecolor();
				
				$this->add_label($title, $color);
				
				$current_label = 0;
				foreach($data as $label => $value) {

					$x = $padding['left']+$stepx+$stepx*$i;
					$y = ($padding['bottom']+$value*$spany/$max) * -1;
					
					$this->vline($x, -1*$padding['bottom'], $padding['top'], $this->color(0xee, 0xee, 0xee));

					if ($labels < $current_label++) {
						$this->vtext($x-6, -1*$padding['bottom']+($maxt*7), $label, $this->color(0, 0, 0));
						$labels++;
					}

					if ($i > 0) {
						$this->line($lx, $ly, $x, $y, $color);
					}

					$this->point($x, $y, $color);

					$lx = $x;
					$ly = $y;
					$i++;
				}
			}
			// end drawing data
		}

		// drawing lines
		$this->hline(-1*$padding['bottom'], $padding['left'], -1*$padding['right'], $this->color(0x0, 0x0, 0x0));
		//$this->hline(-1*$padding['bottom']-1, $padding['left'], -1*$padding['right'], $this->color(0x0, 0x0, 0x0));
		$this->vline($padding['left'], -1*$padding['bottom'], $padding['top'], $this->color(0x0, 0x0, 0x0));
		//$this->vline($padding['left']-1, -1*$padding['bottom'], $padding['top'], $this->color(0x0, 0x0, 0x0));
		// end drawling lines

	}

	public function display() {
		$this->draw();
		header('Content-Type: image/png');
		imagepng($this->img);
	}

	public function save($file) {
		$this->draw();
		imagepng($this->img, $file);
	}

	public function run_test() {
		$graph = new graph(350, 200);

		$graph->data(
			array(
				'Unique hits' => array(
					'aa' => 45,
					'sb' => 21,
					'cx' => 6,
					'ds' => 35,
					'ex' => 52
				),
				'Page views' => array(
					99,
					123,
					9,
					134,
					11
				)
			)
		);

		$gradh->display();
	}
}

//graph::run_test();

?>
