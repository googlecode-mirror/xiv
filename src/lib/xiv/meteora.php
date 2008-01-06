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

class meteora extends tm_object {
	
	function rpc_files_browse($id, $url) {
		$r = "
			var fb = document.{$id};
			fb.browse('".$url."');
		";
		return $r;
	}

	function rpc_files_refresh($id) {
		$r = "
			var fb = document.{$id};
			fb.refresh();
		";
		return $r;
	}

	function create_notebook($name, $elements) {

		$param =& $this->using('param');

		echo "<div id=\"$name\"></div>\n"
		."<script type=\"text/javascript\">\n"
		."Meteora.uses('notebook');\n"
		."Meteora.onStart(\n"
		."\tfunction() {\n"
		."\t\tvar nb = new Notebook('$name', {allowBookmark: true});\n";
		foreach ($elements as $i) {
			if (!isset($i['auth']) || $i['auth']) {
				echo "\t\tnb.addPage({id: '{$i['id']}', title: '{$i['title']}'}, {url: '".$param->create($i['url'])."'}, {allowBookmark: true});\n";
			}
		}
		echo "\t\tdocument.$name = nb;\n"
		."\t}\n"
		.")\n"
		."</script>\n"
		;
	}

	function rpc_notebook_update_page($notebook, $pageid, $content = null) {
		$this->notebook_content($content);
		$r = "var nb = document.{$notebook}; nb.updatePage('$pageid', $content);";
		return $r;
	}
	
	function rpc_notebook_close_page($notebook, $pageid, $autorefresh = true) {
		$r = "
			var nb = document.{$notebook};
			nb.closePage('{$pageid}');
		";
		if ($autorefresh) {
			$r .= "
			nb.selectPage('index');
			nb.updatePage('index');
			";
		}
		return $r; 
	}

	private function notebook_content(&$content) {
		if (is_array($content)) {
			$param =& $this->using('param');
			$content = "{url: '".$param->create($content['url'])."'}";
		} else {
			$json =& $this->using('json');
			$content = $json->encode($content);
		}
	}

	function rpc_notebook_select_page($notebook, $pageid) {
		$json =& $this->using('json');
		$r = "
			var nb = document.{$notebook};
			nb.selectPage('{$pageid}');
		";
		return $r;
	}

	function rpc_notebook_open_page($notebook, $pageid, $pagetitle, $content) {
		$json =& $this->using('json');

		$this->notebook_content($content);

		$r = "
			var nb = document.{$notebook};
			nb.addPage(
				{id: '{$pageid}', title: '{$pagetitle}'},
				{$content},
				{
					allowClose: true
				}
			);
			nb.selectPage('{$pageid}');
		";
		return $r;
	}
}
?>
