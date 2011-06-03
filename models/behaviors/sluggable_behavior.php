<?php
class Sluggable_Behavior extends Behavior {
	
/**
 * Default map of accented and special characters to ASCII characters
 *
 * @var array
 * @access protected
 */
	protected $transliteration = array(
		'/ä|æ|ǽ/' => 'ae',
		'/ö|œ/' => 'oe',
		'/ü/' => 'ue',
		'/Ä/' => 'Ae',
		'/Ü/' => 'Ue',
		'/Ö/' => 'Oe',
		'/À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ/' => 'A',
		'/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª/' => 'a',
		'/Ç|Ć|Ĉ|Ċ|Č/' => 'C',
		'/ç|ć|ĉ|ċ|č/' => 'c',
		'/Ð|Ď|Đ/' => 'D',
		'/ð|ď|đ/' => 'd',
		'/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě/' => 'E',
		'/è|é|ê|ë|ē|ĕ|ė|ę|ě/' => 'e',
		'/Ĝ|Ğ|Ġ|Ģ/' => 'G',
		'/ĝ|ğ|ġ|ģ/' => 'g',
		'/Ĥ|Ħ/' => 'H',
		'/ĥ|ħ/' => 'h',
		'/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ/' => 'I',
		'/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı/' => 'i',
		'/Ĵ/' => 'J',
		'/ĵ/' => 'j',
		'/Ķ/' => 'K',
		'/ķ/' => 'k',
		'/Ĺ|Ļ|Ľ|Ŀ|Ł/' => 'L',
		'/ĺ|ļ|ľ|ŀ|ł/' => 'l',
		'/Ñ|Ń|Ņ|Ň/' => 'N',
		'/ñ|ń|ņ|ň|ŉ/' => 'n',
		'/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ/' => 'O',
		'/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º/' => 'o',
		'/Ŕ|Ŗ|Ř/' => 'R',
		'/ŕ|ŗ|ř/' => 'r',
		'/Ś|Ŝ|Ş|Š/' => 'S',
		'/ś|ŝ|ş|š|ſ/' => 's',
		'/Ţ|Ť|Ŧ/' => 'T',
		'/ţ|ť|ŧ/' => 't',
		'/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ/' => 'U',
		'/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ/' => 'u',
		'/Ý|Ÿ|Ŷ/' => 'Y',
		'/ý|ÿ|ŷ/' => 'y',
		'/Ŵ/' => 'W',
		'/ŵ/' => 'w',
		'/Ź|Ż|Ž/' => 'Z',
		'/ź|ż|ž/' => 'z',
		'/Æ|Ǽ/' => 'AE',
		'/ß/'=> 'ss',
		'/Ĳ/' => 'IJ',
		'/ĳ/' => 'ij',
		'/Œ/' => 'OE',
		'/ƒ/' => 'f'
	);
	
	private $defaults = array(
		'field' => 'title',
		'replacement' => '_',
	);
	private $settings = array();
	
	public function init(&$model, $settings = array()) {
		$this->settings[get_class($model)] = array_merge($this->defaults, $settings);
	}
	
	public function beforeInsert(&$model, $data) {
		if (is_array($this->settings[get_class($model)]['field'])) {
			foreach ($this->settings[get_class($model)]['field'] as $fld) {
				if (isset($slug_string)) {
					$slug_string .= ' ' . $data[$fld];
				} else {
					$slug_string = $data[$fld];
				}
			}
		} else {
			$slug_string = $data[$this->settings[get_class($model)]['field']];
		}
		
		$slug = $this->sluggify($slug_string, $this->settings[get_class($model)]['replacement']);
		
		// slug has to be unique.
		$i = 0;
		while ($model->isUnique('slug', $slug) !== true) {
			$slug = $this->sluggify($slug_string, $this->settings[get_class($model)]['replacement']) . $this->settings[get_class($model)]['replacement'] . $i++;
		}
		
		$model->data['slug'] = $slug;
		
		return true;
	}
	
	public function beforeUpdate(&$model, $data) {		
		// check if the sluggable field data has changed
		$update_slug = false;
		if (is_array($this->settings[get_class($model)]['field'])) {
			foreach ($this->settings[get_class($model)]['field'] as $fld) {
				if ($model->read($fld, $model->data['_id']) != $model->data[$fld]) {
					$update_slug = true;
				}
			}
			if ($update_slug) {
				foreach ($this->settings[get_class($model)]['field'] as $fld) {
					if (isset($slug_string)) {
						$slug_string .= ' ' . $data[$fld];
					} else {
						$slug_string = $data[$fld];
					}
				}
			}
		}		
		elseif ($model->read($this->settings[get_class($model)]['field'], $model->data['_id']) != $model->data[$this->settings[get_class($model)]['field']]) {
			
			$slug_string = $data[$this->settings[get_class($model)]['field']];
			
			$update_slug = true;
		}
		
		// update slug
		if ($update_slug) {
			$slug = $this->sluggify($slug_string, $this->settings[get_class($model)]['replacement']);
			
			// slug has to be unique.
			$i = 1;
			while ($model->isUnique('slug', $slug, $data['_id']) !== true) {
				$slug = $this->sluggify($slug_string, $this->settings[get_class($model)]['replacement']) . $this->settings[get_class($model)]['replacement'] . $i++;
			}
			
			$model->data['slug'] = $slug;
		}
		
		return true;
	}
	
/**
 * Returns a string with all spaces converted to underscores (by default), accented
 * characters converted to non-accented characters, and non word characters removed.
 *
 * @param string $string the string you want to slug
 * @param string $replacement will replace keys in map
 * @param array $map extra elements to map to the replacement
 * @return string
 * @access public
 * @static
 * @link http://book.cakephp.org/view/1479/Class-methods
 */
    public function sluggify($string, $replacement = null, $map = array()) {
		if (!$replacement) {
			$replacement = '-';
		}

        if (is_array($replacement)) {
            $map = $replacement;
            $replacement = '_';
        }
        $quotedReplacement = preg_quote($replacement, '/');

        $merge = array(
            '/[^\s\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]/mu' => ' ',
            '/\\s+/' => $replacement,
            sprintf('/^[%s]+|[%s]+$/', $quotedReplacement, $quotedReplacement) => '',
        );

        $map = $map + $this->transliteration + $merge;
        return strtolower(preg_replace(array_keys($map), array_values($map), $string));
    }
}