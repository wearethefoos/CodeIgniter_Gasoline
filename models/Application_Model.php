<?php
/**
 * Application model class to be extended with
 * your application's model classes.
 * 
 * @package CodeIgniter
 * @subpackage Gasoline
 * @author W.R. de Vos
 * @copyright Copyright (c) 2011, W.R. de Vos
 * @link http://foxycoder.com
 * @version 0.0.1 (Alpha)
 */
class Application_Model extends CI_Model {
	
/**
 * Collection to be used by the model.
 * 
 * @access protected
 */
	protected $collection = '';

/**
 * Acts As contains an array of class names
 * to be used as behaviors.
 * 
 * @access public
 */
	public $actsAs   = array();
	
/**
 * Validate contains validation options for
 * model fields to be used by the validation
 * class.
 * 
 * @access public
 */
	public $validate = array();
	
/**
 * Validation errors
 * 
 * @access public
 */
	public $validation_errors = array();
	
	
/**
 * Basic constructor.
 * 
 * Initiates the parent constructor and
 * tries to load behavior classes.
 */

	public function __construct() {
		
		parent::__construct();
		
		// Loads behaviors
		$this->init();
	}
	
/**
 * Initiates behavior classes.
 * 
 * @access protected
 * @return void
 */
	protected function init() {
		// instantiate the Mongo DB class
		$this->load->library('mongo_db');
		
		// load and init behaviors
		foreach ($actsAs as $behavior) {
			$this->load->model('behaviors/' . $behavior . '_behavior', $behavior);
			$this->{$behavior}->init();
		}
	}
	
/**
 * Insert
 * 
 * @access public
 * @param Array $insert: data to insert.
 * @return mixed: _id or false on failure.
 */
	public function insert($insert = array()) {
		// validate data first
		if ($this->validate($insert)) {
			// before insert callback
			$this->beforeInsert($insert);
			
			// insert
			$result = $this->mongo_db->insert($insert);
			
			// after insert callback
			$this->afterInsert($result);
			
			return $result;
		}
		
		return false;
	}

/**
 * Update
 * 
 * @access public
 * @param Array $data: the data to save.
 * @param Array $options: the selection criteria for the record to update.
 * @return boolean: success or failure.
 */
	public function update($data = array(), $options = array()) {
		// validate data first
		if ($this->validate($data)) {
			// before insert callback
			$this->beforeUpdate($data);

			// insert
			$result = $this->mongo_db->update($data, $options);

			// after insert callback
			$this->afterUpdate($result);

			return $result;
		}

		return false;
	}
	
/**
 * Validate
 * 
 * @access public
 * @param array $data: data to be validated.
 * @return boolean: success or failure.
 */
	public function validate($data = array()) {
		
		if ($valid = $this->beforeValidate($data)) {
		
			// instantiate the Validation class
			$this->load->model('Validation');
		
			foreach ($this->validate as $field => $criteria) {
				$message = $this->validate->check($field, $data, $criteria);
				if ($message !== true) {
					$valid = false;
					$this->validation_errors[$field] = $message;
				}
			}
			
			if ($valid) {
				$valid = $this->afterValidate();
			}
			
		}
		
		return $valid;
	}
	
/**
 * Callbacks:
 * 
 * - beforeValidate
 * - beforeInsert
 * - beforeUpdate
 * - beforeGet
 * 
 * - afterValidate
 * - afterInsert
 * - afterUpdate
 * - afterGet
 */

/**
 * Before Validate Callback.
 * 
 * @access public
 * @param Array $data: data to be validated.
 * @return boolean: success or failure.
 */
	public function beforeValidate($data = array()) {
		foreach($actsAs as $behavior) {
			if ($this->{$behavior}->beforeValidate($data) === false) {
				return false;
			}
		}
		
		return true;
	}
	
/**
 * After Validate Callback.
 * 
 * @access public
 * @param Array $data: data to be validated.
 * @return boolean: success or failure.
 */
	public function afterValidate($data = array()) {
		foreach($actsAs as $behavior) {
			if ($this->{$behavior}->afterValidate($data) === false) {
				return false;
			}
		}
		
		return true;
	}

/**
 * Before Insert Callback.
 * 
 * @access public
 * @param Array $insert: data to be inserted.
 */
	public function beforeInsert($insert = array()) {
		foreach($actsAs as $behavior) {
			$this->{$behavior}->beforeInsert($insert);
		}
	}

/**
 * After Insert
 * 
 * @access public
 * @param mixed $result: id if successful | false if unsuccesful
 */
	public function afterInsert($result) {
		foreach($actsAs as $behavior) {
			$this->{$behavior}->afterInsert($update, $options);
		}
	}

/**
 * Before Update Callback.
 * 
 * @access public
 * @param Array $update: data to be updated.
 * @param Array $options: query options
 */
	public function beforeUpdate($update = array(), $options = array()) {
		foreach($actsAs as $behavior) {
			$this->{$behavior}->beforeUpdate($update, $options);
		}
	}

/**
 * After Update Callback.
 * 
 * @access public
 * @param Boolean $success: whether or not the update was successful.
 */
	public function afterUpdate($success = false) {
		foreach($actsAs as $behavior) {
			$this->{$behavior}->afterUpdate($success);
		}
	}
	
/**
 * Before Get Callback.
 * 
 * @access public
 */
	public function beforeGet() {
		foreach($actsAs as $behavior) {
			$this->{$behavior}->beforeGet();
		}
	}
	
/**
 * After Get Callback.
 * 
 * @access public
 */
	public function afterGet($results = array()) {
		foreach($actsAs as $behavior) {
			$this->{$behavior}->afterGet($results);
		}
	}
}