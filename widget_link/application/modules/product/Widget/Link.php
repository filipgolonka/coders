<?php

class product_Widget_Link  extends Our_Widget_Base{

    const PARTIAL_VIEW = 'link'; //todo wycaigac z klasy?
    private  $_params = array();

    public $userRequestAsCacheParams = true;

    public function test() {
        echo 'test';
    }
    
    public function render($renderView = null) {
       $oCmsLinik = new CmsWgLink();
       
       $listLink = $oCmsLinik->getLinkByBox($this->cmswId, $this->_langId);
       
       $controller = Zend_Controller_Front::getInstance();
       $request = $controller->getRequest();
       $this->_params['userLocale'] = $request->getParam('language');

       
       $this->_params['boxname'] = '';
       foreach ($listLink as $link){

           if(!is_null($link["cwl_lin_url"])) {
               if(!is_null($link["cwl_lin_is_popup"]) && $link["cwl_lin_is_popup"]) {
                   $this->_params['links'][] = array('url' => $link["cwl_lin_url"], 'title'=>$link["cwl_lin_title_name"], 'class' => 'ajaxDialogNoModel');
               } else {
                   $this->_params['links'][] = array('url' => $link["cwl_lin_url"], 'title'=>$link["cwl_lin_title_name"]);
               }
                
           }else {
               $url = $this->view->url(array('module'=>'default', 'controller'=>'staticpage', 'action'=>'article', 'id'=>$link["cart_art_articles_id"]), 'article_page');
               if(!is_null($link["cwl_lin_is_popup"]) && $link["cwl_lin_is_popup"]) {
                   $this->_params['links'][] = array('url' => $url, 'title'=>$link["cwl_lin_title_name"], 'class' => 'ajaxDialogNoModel');
               } else {
                   $this->_params['links'][] = array('url' => $url, 'title'=>$link["cwl_lin_title_name"]);
               }
           }
           $this->_params['boxname'] = $link['cwbl_box_name'];


    }

    if(isset($this->_params['links'])) {
           
           return $this->_render($renderView, self::PARTIAL_VIEW, $this->_params);
       }
       return null;
    }

    
}


