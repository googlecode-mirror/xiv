<?php
class cli extends tm_object {
	function begin($s) {
		$this->out("* $s");
	}
	function success($s) {
		$this->out("--> $s\n");
	}
	function out($s) {
		echo "$s\n";
	}
}
?>
