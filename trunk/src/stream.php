<?php
class stream {
	private $fp;
	private $file;
	public function __construct($file = null) {
		if ($file) {
			$this->open($file);
		}
	}
	public function open($file) {
		$this->file = $file;
		if (is_readable($file)) {
			$this->fp = fopen($this->file, 'rb');
			return true;
		} else {
			return false;
		}
	}
	public function read($bytes) {
		$buff = '';
		if ($bytes > 0) {
			$buff = fread($this->fp, $bytes);
		}
		return $buff;
	}
	public function seekto($pos) {
		fseek($this->fp, $pos);
		return $this->currentpos();
	}
	public function length() {
		return filesize($this->file);
	}
	public function currentpos() {
		return ftell($this->fp);
	}
	public function close() {
		return fclose($this->fp);
	}
}
?>
