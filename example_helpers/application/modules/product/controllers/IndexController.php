<?php

class Product_IndexController extends Zend_Controller_Action {
	
	public function showAction() {
		$productId = (int) $this->getRequest()->getParam('id');
		
		$product = array();
		
		$productModel = new product_Model_Default();
		if($productModel->isValidForShow($productId)) {
			$product = $productModel->getProduct($productId);
		}
		
		$this->view->product = $product;
	}
	
	public function listAction() {
		$categoryId = (int) $this->getRequest()->getParam('id');
		$productModel = new product_Model_Default();
		$this->view->products = $productModel->getProductsForCategory($categoryId);
	}
	
}
