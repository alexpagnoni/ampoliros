<?php
// $Id: HuiCommand.php,v 1.6 2004-07-14 15:16:44 alex Exp $

package('com.solarix.ampoliros.hui.mvc');

import('com.solarix.ampoliros.hui.mvc.HuiFrontController');

abstract class HuiCommand extends Object {
	protected $fc;

	public final function __construct(HuiFrontController $fc) {
		$this->fc = $fc;
	}
	
	abstract public function execute();
}

?>