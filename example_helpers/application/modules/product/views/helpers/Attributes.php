<?php

class product_View_Helper_Attributes extends Zend_View_Helper_Abstract {
	
	private $_attributes = array();
	
	public function attributes($productId) {
		$oQuery = Doctrine_Query::create()
				->from('AttributesTable')
				->select('attr_id, attr_name, attr_value')
				->where('fk_prd_id = ?', $productId);
		
		$attributes = $oQuery->execute(array(), Doctrine::HYDRATE_ARRAY);
		
		if($attributes) {
			$this->_attributes = $attributes;
		}
		
		return $this;
	}
	
	public function __toString() {
		$html = array();
		if(count($this->_attributes)) {
			$html[] = '<ul>';
			foreach($this->_attributes as $attribute) {
				$html[] = sprintf('<li>%s: %s</li>', $attribute['attr_name'], $attribute['attr_value']);
			}
			$html[] = '</ul>';
		}
		
		return implode("\n", $html);
	}
	
}