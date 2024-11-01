<?php
class WPSS_Exclude {

	public function __construct() {
	}

	public function init() {
		if ($this->is_privileged()) {
			$supposed_hooks_class = get_class($this) . '_Hooks';
			WPSS_Base_Factory::get($supposed_hooks_class)->register_hooks();
		}
	}

	public function is_privileged() {
		return true;
	}
}