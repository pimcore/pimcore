<?php

class AdminerFrames {
	/** @access protected */
	var $sameOrigin;

	/**
	* @param bool allow running from the same origin only
	*/
	function AdminerFrames($sameOrigin = false) {
		$this->sameOrigin = $sameOrigin;
	}

	function headers() {
		if ($this->sameOrigin) {
			header("X-Frame-Options: SameOrigin");
		}
		header("X-XSS-Protection: 0");
		return false;
	}

}