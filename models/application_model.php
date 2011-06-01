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
 * Data contained by the model.
 * 
 * @access public
 */
	public $data = array();
	
/**
 * Id of the current record.
 * 
 * @access public
 */
	public $id = false;
	
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
 * 
 * If a collection name is provided as
 * a parameter, the model can be used
 * as a standard model, like so:
 * 
 * (e.g. in a controller)
 * $myModel = new Application_Model('MyCollection');
 * 
 * @param [optional] string $collection
 */

	public function __construct($collection = "") {
		$this->load->model('behaviors/behavior');
		
		parent::__construct();
		
		if (!$this->collection) {
			$this->collection = $collection;
		}
		
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
		foreach($this->actsAs as $behavior => $settings) {
			$this->load->model('behaviors/' . $behavior . '_behavior', $behavior);
			$this->{$behavior}->init($this, $settings);
		}
	}
	
/**
 * Db
 * 
 * @access public
 */
	function db() {
		return $this->mongo_db;
	}
	
/**
 * Get
 * 
 * @access public
 * @return Mongo_db
 */
	public function get($params = array()) {
		
		$params = $this->beforeGet($params);
		
		if (isset($params['where'])) {
			$this->mongo_db->where($params['where']);
		}
		if (isset($params['fields'])) {
			$this->mongo_db->select($params['fields']);
		}
		if (isset($params['order'])) {
			$this->mongo_db->order_by($params['order']);
		}
		if (isset($params['limit'])) {
			$this->mongo_db->limit((int) $params['limit']);
		}
		if (isset($params['offset'])) {
			$this->mongo_db->skip((int) $params['offset']);
		}
		
		$results = $this->mongo_db->get($this->collection);
		
		return $this->afterGet($results);
	}
	
/**
 * Setter for model data
 * 
 * @access public
 */
	public function set($field, $value = "") {
		$this->data[$field] = $value;
	}
	
/**
 * Getter for model data
 * 
 * @access public
 * @param $field: the field to get
 * @param $id [optional]: if an id is provided the collection is queried
 * and the field value from the database is returned instead of the data
 * contained by the model instance.
 */
	public function read($field, $id = null) {
		if (!isset($this->data[$field]) || $id) {
			return $this->mongo_db->select(array($field))->where(array('_id' => $id))->get($this->collection);
		}
		
		return $this->data[$field];
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
			// set model data
			$this->data = array_merge($this->data, $insert);
			
			// before insert callback
			$this->beforeInsert($insert);
			
			// insert
			$this->id = $this->mongo_db->insert($this->collection, $this->data);
			
			// after insert callback
			$this->afterInsert($this->id);
			
			return $this->id;
		}
		
		return false;
	}

/**
 * Update
 * 
 * @access public
 * @param Array $data: the data to save.
 * @param Array $options: options the record to update.
 * @return boolean: success or failure.
 */
	public function update($data = array(), $options = array()) {
		// validate data first
		if ($this->validate($data)) {
			// set model data
			$this->data = array_merge($this->data, $data);
			
			$where = array('_id' => $this->data['_id']);
			
			// before insert callback
			$this->beforeUpdate($data);

			// update
			unset($this->data['_id']);
			$result = $this->mongo_db->where($where)->update($this->collection, $this->data, $options);

			// after insert callback
			$this->afterUpdate($result);

			return $result;
		}

		return false;
	}
	
/**
 * Delete
 * 
 * @access public
 * @param Mixed $id: Id of the record to delete.
 * @return boolean: success
 */
	public function delete($id = null) {
		if ($id) {
			// before delete callback
			$this->beforeDelete($id, false);
			
			$success = $this->mongo_db->where(array('_id' => $id))->delete($this->collection);
			
			// after delete callback
			$this->afterDelete($success);
			
			return $success;
		}
	}
	
/**
 * Delete
 * 
 * @access public
 * @param array [optional] $conditions: conditions of the records to delete.
 * * * !! if left empty, all will be deleted !! * * *
 * @return boolean: success
 */
	public function deleteAll($conditions = array()) {
		if ($id) {
			// before delete callback
			$this->beforeDelete($conditions, true);
			
			$success = $this->mongo_db->where($conditions)->delete($this->collection);
			
			// after delete callback
			$this->afterDelete($success);
			
			return $success;
		}
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
				if ($this->Validation->check($this, $field, $data, $criteria) !== true) {
					$valid = false;
				}
			}
			
			if ($valid) {
				$valid = $this->afterValidate();
			}
			
		}
				
		return $valid;
	}
	
/**
 * Check if a specified value is unique for a certain field.
 * 
 * @access public
 * @param string $field: the field to check.
 * @param mixed $value: the value to check.
 */
	public function isUnique($field = "", $value = "", $not = false) {
		return $this->mongo_db->isUnique($this->collection, $field, $value, $not);
	}
	
/**
 * Callbacks:
 * 
 * - beforeValidate
 * - beforeInsert
 * - beforeUpdate
 * - beforeDelete
 * - beforeGet
 * 
 * - afterValidate
 * - afterInsert
 * - afterUpdate
 * - afterDelete
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
		foreach($this->actsAs as $behavior => $settings) {
			if ($this->{$behavior}->beforeValidate($this, $data) === false) {
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
		foreach($this->actsAs as $behavior => $settings) {
			if ($this->{$behavior}->afterValidate($this, $data) === false) {
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
		foreach($this->actsAs as $behavior => $settings) {
			$this->{$behavior}->beforeInsert($this, $insert);
		}
	}

/**
 * After Insert
 * 
 * @access public
 * @param mixed $result: id if successful | false if unsuccesful
 */
	public function afterInsert($result) {
		foreach($this->actsAs as $behavior => $settings) {
			$this->{$behavior}->afterInsert($this, $result);
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
		foreach($this->actsAs as $behavior => $settings) {
			$this->{$behavior}->beforeUpdate($this, $update, $options);
		}
	}

/**
 * After Update Callback.
 * 
 * @access public
 * @param Boolean $success: whether or not the update was successful.
 */
	public function afterUpdate($success = false) {
		foreach($this->actsAs as $behavior => $settings) {
			$this->{$behavior}->afterUpdate($this, $success);
		}
	}
	
/**
 * Before Delete Callback.
 * 
 * @access public
 * @param mixed $params: the id of the record to delete or the conditions
 * of the delete query.
 * @param boolean $multiple: deleting multiple records via deleteAll or not
 * via delete.
 */
	public function beforeDelete($params, $multiple = false) {
		foreach($this->actsAs as $behavior => $settings) {
			$this->{$behavior}->beforeDelete($this, $params, $multiple);
		}
	}

/**
 * After Delete Callback
 * 
 * @access public
 * @param boolean $success: delete succeeded or not
 */
	public function afterDelete($success) {
		foreach($this->actsAs as $behavior => $settings) {
			$this->{$behavior}->afterInsert($this, $success);
		}
	}

/**
 * Before Get Callback.
 * 
 * @access public
 */
	public function beforeGet($params) {
		foreach($this->actsAs as $behavior => $settings) {
			$params = $this->{$behavior}->beforeGet($this, $params);
		}
		
		return $params;
	}
	
/**
 * After Get Callback.
 * 
 * @access public
 */
	public function afterGet($results = array()) {
		foreach($this->actsAs as $behavior => $settings) {
			$results = $this->{$behavior}->afterGet($this, $results);
		}
		
		return $results;
	}
}