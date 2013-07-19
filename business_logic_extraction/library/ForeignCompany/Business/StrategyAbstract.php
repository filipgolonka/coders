<?php
abstract class ForeignCompany_Business_StrategyAbstract {
	
	protected $_context;
	
	public function __construct($context) {
		$this->_context = $context;
	}

	public function getContext() {
		return $this->_context;
	}

	public function setContext($context) {
		$this->_context = $context;
		return $this;
	}
	
}