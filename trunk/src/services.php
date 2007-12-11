<?php
class services extends tm_object {

	var $use = array('conf');
	var $lib = array('net/smtp.php');

	function send_template_mail($to, $subject, $template, $headers = null) {
		$this->using('template');

		$param =& $this->using('param');

		$tpl = new template();
		$template = explode('/', $template);
		$tpl->load(TM_TEMPLATES_DIR.C_TEMPLATE.'/layout/views/'.$template[0].'/'.$template[1]);
		$tpl->set($param->get_params(), true);
		$message = $tpl->output();

		$this->send_mail($to, $subject, $message, null, $headers);
	}

	function send_mail($to, $subject, $message, $content_type = 'text/plain', $headers = null) {

		$conf =& $this->conf;

		if (is_array($headers)) {
			$text = array();
			foreach ($headers as $i => $v) {
				$text[] = "$i: ".preg_replace("/[\r\n].*?/", '', $v)."\r\n";
			}
			$headers = implode('', $text);
		}
		
		// preventing possible spam attacks
		$to = trim(preg_replace("/[\r|\n](.*?)/", "", $to));
		$subject = trim(preg_replace("/[\r|\n](.*?)/", "", $subject));
		$message = trim(preg_replace("/[\r|\n]\.[\r|\n](.*?)/", "", $message));

		if ($conf->get("core/smtp_enable")) {

			$smtp = new smtp_session(conf::get("core/smtp_host"), conf::get("core/smtp_port"), conf::get("core/smtp_user"), conf::get("core/smtp_pass"));
			if ($smtp->conn->status()) {
				$smtp->send($to, $subject, $message, $content_type, $headers);
			}
			$smtp->bye();

		} else {

			$headers = "Mime-Version: 1.0\r\n"
			."Content-Type: $content_type\r\n"
			."From: ".TM_HOST." <".$conf->get("core/email_address").">\r\n"
			."X-From-Ip: ".IP_ADDR."\r\n"
			."Date: ".date("r")."\r\n"
			.$headers;

			mail($to, $subject, $message, trim($headers));
		}
	}
}
?>
