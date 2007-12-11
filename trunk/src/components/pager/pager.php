<?php
class pager_component extends tm_component {

	public $items_per_page = 5;

	public function get_sQL() {
		return $this->sql_trailing;
	}
	
	public function init($total_items, $order = null) {
		
		$this->using('template');

		$param =& $this->using('param');

		if (!is_array($order)) {
			$order = $order ? array($order) : array();
		}

		$sel_order = array();
		foreach ($order as $i => $v) {
			if (is_int($i)) {
				$o = explode(' ', $v);
				$sel_order[$o[0]] = $o[0];
			} else {
				$o = explode(' ', $i);
				$sel_order[$o[0]] = $v;
			}
		}

		reset($order);
		while (list($i) = each($order)) {
			if (is_int($i)) {
				$tmp = explode(' ', $order[$i]);
			} else {
				$tmp = explode(' ', $i);
			}
			$order[$i] = array(
				'order_by' => $tmp[0],
				'order_mode' => isset($tmp[1]) ? $tmp[1] : 'ASC'
			);
		}

		$page = (int)$param->raw('page');

		if ($page < 1) {
			$page = 1;
		}
		$page--;

		$order_by = null;

		if ($param->raw('order_by')) {
			$raw_order_by = $param->raw('order_by');
			foreach ($order as $o) {
				if ($raw_order_by == $o['order_by']) {
					$order_by = $raw_order_by;
				}
			}
		}
		if (!$order_by && $order) {
			reset($order);
			list($a, $b) = each($order);
			$order_by = $b['order_by'];
		}

		$order_mode = null;
		if ($param->raw('order_mode')) {
			$raw_order_mode = $param->raw('order_mode');
			if (preg_match('/^(desc|asc)$/i', $raw_order_mode)) {
				$order_mode = strtoupper($raw_order_mode);
			}
		}

		if (!$order_mode && $order) {
			reset($order);
			list($a, $b) = each($order);
			$order_mode = $b['order_mode'];
		}

		$begin = $page*$this->items_per_page;
		$end = $this->items_per_page;

		$this->sql_trailing = ($order_by ? "ORDER BY {$order_by} {$order_mode}" : null)." LIMIT $begin, $end";

		$total_pages = ceil($total_items/$this->items_per_page);
	
		$tpl = new template('pager');
		$tpl->set(
			array (
				'total_pages'		=> $total_pages,
				'current_page'	=> $page+1,
				'page_size'			=> $this->items_per_page,
				'order_by'			=> $order_by,
				'order_mode'		=> $order_mode,
				'sel_order_mode'=>  array('ASC' => __('Ascendent'), 'DESC' => __('Descendent')),
				'sel_order_by'	=> $sel_order
			)
		);

		$this->parent->param->set('pager', $tpl->output(), false);
	}
}
?>
