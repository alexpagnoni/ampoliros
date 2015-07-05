<?php
// $Id: HuiSession.php,v 1.4 2004-07-14 15:16:44 alex Exp $

package('com.solarix.ampoliros.hui.mvc');

class HuiSession extends Object {
	protected $id;

	public function HuiSession() {
		$this -> id = session_id();
	}

	public function start() {
		//if ($this -> state != Ampoliros :: STATE_SETUP)
		ini_set('session.save_path', TMP_PATH.'phpsessions/');
		session_start();
	}

	public function put($key, $value) {
		$_SESSION[$key] = $value;
	}

	public function get($key) {
		return (isset($_SESSION[$key]) ? $_SESSION[$key] : '');
	}

	public function remove($key) {
		if (isset($_SESSION[$key]))
			unset($_SESSION[$key]);
	}

	public function isValid($key) {
		return isset($_SESSION[$key]);
	}

	public function getId() {
		return $this -> id;
	}
}

?>