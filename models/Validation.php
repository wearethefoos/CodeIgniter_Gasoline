<?php
/**
 * Validation for models.
 * 
 * @package CodeIgniter
 * @subpackage Gasoline
 * @author W.R. de Vos
 * @copyright Copyright (c) 2011, W.R. de Vos
 * @link http://foxycoder.com
 * @version 0.0.1 (Alpha)
 */
class Validation
{
/**
 * Default check function initiates the different functions
 * specified in the validation criteria parameter.
 * 
 * @param Application_Model $model: the model instance that initiated the 
 * call.
 * @param String $field: the field to check.
 * @param Mixed $data: the data to check, could be either a string/value or an 
 * array.
 * @param Mixed $criteria: the criteria to check the data on. Could be either
 * a single string containing a rule name (e.g. unique) or an array, 
 * containing at least a rule (e.g.) and optionally a message:
 * array(
 * 	'rule' => 'unique',
 * 	'message' => '%s should be unique.'
 * );
 * Note that the %s in the message will be replaced with the $field string.
 */
	public function check(&$model, $field, $data, $criteria) {
		$rules = array();
		if (is_array($criteria)) {
			foreach ($criteria as $criterium) {
				if (is_array($criterium)) {
					if (isset($criterium['rule'])) {
						$rules[] = $criterium;
					}
				} else {
					$rules[] = array('rule' => $criterium);
				}
			}
		} else {
			$rules[] = array('rule' => $criterium);
		}
		
		foreach ($rules as $rule) {
			if (method_exists($this, $rule['rule'])) {
				if (isset($rule['regex'])) {
					$validate = $this->$rule['rule']($model, $field, $data, $rule['regex']);
				} 
				elseif (isset($rule['min']) && isset($rule['max'])) {
					$validate = $this->$rule['rule']($model, $field, $data, $rule['min'], $rule['max']);
				}
				elseif ($rule['rule'] == 'email' && isset($rule['deep'])) {
					$validate = $this->$rule['rule']($model, $field, $data, $rule['deep']);
				}
				else {
					$validate = $this->$rule['rule']($model, $field, $data);
				}
				if ($validate !== true) {
					$model->validation_errors[$field] = (isset($rule['message'])) ? $rule['message'] : $validate;
				}
			} elseif (method_exists($model, $rule['rule'])) {
				$validate = $model->$rule['rule']($model, $field, $data);
				if ($validate !== true) {
					$model->validation_errors[$field] = (isset($rule['message'])) ? $rule['message'] : $validate;
				}
			}
		}
				
		return (count($model->validation_errors) == 0);
	}
	
/**
 * Required
 * 
 * Checks if a value is present.
 * 
 */
	public function required(&$model, $field, $data) {		
		if (isset($data[$field]) && self::prepare_value($data[$field]) != null) {
			return true;
		}
		
		return sprintf('%s is required.', ucfirst($field));
	}

/**
 * Unique
 * 
 * Checks if a value is unique.
 * 
 * @access public
 */
	public function unique(&$model, $field, $data) {
		if ($this->required($model, $field, $data) === true) {
			if ($model->isUnique($field, $data[$field])) {
				return true;
			}
		}
		
		return sprintf('%s needs to be unique.', ucfirst($field));
	}
	
/**
 * Between
 * 
 * Checks if a value is between two values.
 */
	function between(&$model, $field, $data, $min, $max) {
		$length = mb_strlen($check);
		if ($length >= $min && $length <= $max) {
			return true;
		}
		
		return sprintf('%s needs to contain a value between %s and %s', ucfirst($field), $min, $max);
	}
	
/**
* Alphanumeric
* 
* Checks that a string contains only integer or letters
* 
* @access public
*/
	public function alphaNumeric(&$model, $field, $data) {
		
		$regex = '/^[\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]+$/mu';
		
		if ($this->regex($model, $field, $data, $regex) === true) {
			return true;
		}

		return sprintf('%s contains an invalid value.', ucfirst($field));
	}

/**
 * Email
 * 
 * Checks that a string is a valid email address
 * 
 * @access public
 */
	public function email(&$model, $field, $data, $deep = false) {
		
		$regex = '/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9][-a-z0-9]*\.)*(?:[a-z0-9][-a-z0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,4}|museum|travel)$/i';
		
		if ($this->regex($model, $field, $data, $regex) === true) {
			if ($deep === true && preg_match('/@(?:[a-z0-9][-a-z0-9]*\.)*(?:[a-z0-9][-a-z0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,4}|museum|travel)$/i', $data[$field], $regs)) {
				if (function_exists('getmxrr') && getmxrr($regs[1], $mxhosts)) {
					return true;
				}
				if (function_exists('checkdnsrr') && checkdnsrr($regs[1], 'MX')) {
					return true;
				}
				if (is_array(gethostbynamel($regs[1]))) {
					return true;
				}
			} else {
				return true;
			}
		}

		return sprintf('%s contains an invalid value.', ucfirst($field));
	}
	
/**
 * Regex
 * 
 * Checks a value with a regular expression.
 * 
 * @access public
 */
	public function regex(&$model, $field, $data, $regex = "") {
		if ($this->required($model, $field, $data) === true) {
			if (preg_match($regex, $data[$field])) {
				return true;
			}
		}
		
		return sprintf('%s contains an invalid value.', ucfirst($field));
	}
	
/**
 * Prepares a value to use before validation.
 * 
 * @param mixed $value
 * @return mixed prepared value
 */
	private static function prepare_value($value) {
		$value = trim($value);
		
		if (empty($value)) {
			return null;
		}
		
		return $value;
	}
}