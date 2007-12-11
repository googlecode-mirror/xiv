<?php
class misc extends tm_object {
	function random_string($len, $charset = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ") {
		$buff = null;
		for ($i = 0; $i < $len; $i++)
			$buff .= $charset[rand(0, strlen($charset)-1)];
		return $buff;
	}
}
?>
