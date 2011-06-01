<?php
class Behavior {
	private $defaults = array();
	private $settings = array();
	
	public function init(&$model, $settings = array()) {
		$this->settings = array_merge($this->settings, $settings);
	}
	
	public function beforeValidate($data = array()) {
		return true;
	}
	
	public function afterValidate() {
		return true;
	}
	
	public function beforeInsert(&$model, $data) { }
	
	public function afterInsert(&$model, $result) { }
	
	public function beforeDelete(&$model, $params, $multiple) {
		return true;
	}
	
	public function afterDelete(&$model, $success) { }
	
	public function beforeGet(&$model, $params) {
		return $params;
	}
	
	public function afterGet(&$model, $results) {
		return $results;
	}
	
	public function beforeUpdate(&$model, $update, $options) {
		
	}
	
	public function afterUpdate(&$model, $success) {
		
	}
}