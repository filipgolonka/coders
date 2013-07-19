<?php
// kod do wklejenia
private $_strategy = null;
// dopisac gettery i settery


$businessConfig = Zend_Registry::get('business_logic');
if(isset($businessConfig['message_adapter']['utm']) && class_exists($businessConfig['message_adapter']['utm'])) {
	$class = $businessConfig['message_adapter']['utm'];
	$this->_strategy = new $class($this);
}