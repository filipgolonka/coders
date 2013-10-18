<?php
/**
 * Frontend form 
 * 
 * @author JohnDoe
 */
class Company_AdhocEdi_Form_Shop_Tyre extends Company_AdhocEdi_Form_Shop_Abstract {
    
    private $_oTyre;
    
    public function init() {
        parent::init();
        
        $this->_oTyre = new Company_AdhocEdi_Model_Tyre();
        
        $pgrId = Company_AdhocEdi_Model::getCategoryTyreId();
        
        // pobieramy domyslny porzadek elementow, definiowany w panelu administracyjnym
        $aOrder = $this->_oTyre->getAttributeOrderForCategory($pgrId);
        
        // jesli nie zdefiniowano porzadku w PA to bierzemy nastepujacy porzadek
        if(count($aOrder) == 0) {
            $aOrder = array(
                1 => MtcsAttributeClasses::CLASS_FUNCTION_SEASON,
                2 => MtcsAttributeClasses::CLASS_FUNCTION_PRZEZNACZENIE, 
                3 => MtcsAttributeClasses::CLASS_FUNCTION_SZEROKOSC, 
                4 => MtcsAttributeClasses::CLASS_FUNCTION_PROFIL, 
                5 => MtcsAttributeClasses::CLASS_FUNCTION_FELGI_SREDNICA,
                6 => MtcsAttributeClasses::CLASS_FUNCTION_PRODUCENT
            );
        }
        
        $aElements = $this->_initElements();
        
        $i = 1;
        foreach($aOrder as $function) {
            if(isset($aElements[$function])) {
                $aElements[$function]->setOrder($i);
                $this->addElement($aElements[$function]);
                $i++;
            }
        }
        
        // dostepnosc
        $this->addElement(Company_AdhocEdi_Form_Abstract::getAvailabilityElement());
    }
    
    private function _initElements() {
        $aElements = array();
        
        $seasones = $this->_oTyre->getSeason();
        $manufactureres = $this->_oTyre->getManufacturer();
        $dimmensiones = $this->_oTyre->getDimaters();
        $profiles = $this->_oTyre->getProfiles();
        $widths = $this->_oTyre->getWidths();
        $destinationes = $this->_oTyre->getDestinationes();
        
        $element = new Zend_Form_Element_Select('season');
        $element->setLabel(Company_View_Helper_Translate::translate('search_car_season'));
        $element->addMultiOption('', Company_View_Helper_Translate::translate('select_any_m'));
        $lastRow = '';
        foreach ($seasones as $item) {
            $element->addMultiOption($item['trs_trans_id'], $item['trs_trans_value']);
            $lastRow = $item['mtcs_attribute_class_id'];
        }
        $element->setName('attribute_'.$lastRow);
        $aElements[MtcsAttributeClasses::CLASS_FUNCTION_SEASON] = $element;

        $element = new Zend_Form_Element_Select('destination');
        $element->setLabel(Company_View_Helper_Translate::translate('search_car_destination'));
        $element->addMultiOption('', Company_View_Helper_Translate::translate('select_any_m'));
        foreach ($destinationes as $item) {
            $element->addMultiOption($item['trs_trans_id'], $item['trs_trans_value']);
            $lastRow = $item['mtcs_attribute_class_id'];
        }
        $element->setName('attribute_'.$lastRow);
        
        $aElements[MtcsAttributeClasses::CLASS_FUNCTION_PRZEZNACZENIE] = $element;
        
        // szerokosc
        $element = new Zend_Form_Element_Select('width');
        $element->setLabel(Company_View_Helper_Translate::translate('search_car_chains_width'));
        $element->addMultiOption('', Company_View_Helper_Translate::translate('select_any_f'));

        foreach ($widths as $item) {
            if (!empty($item['trs_trans_value'])) {
                $element->addMultiOption($item['trs_trans_id'], $item['trs_trans_value']);
            }
            $lastRow = $item['mtcs_attribute_class_id'];
        }
        $element->setName('attribute_'.$lastRow);
        $aElements[MtcsAttributeClasses::CLASS_FUNCTION_SZEROKOSC] = $element;
        
        // profil
        $element = new Zend_Form_Element_Select('range');
        $element->setLabel(Company_View_Helper_Translate::translate('search_car_chains_range'));
        $element->addMultiOption('', Company_View_Helper_Translate::translate('select_any_m'));
        foreach ($profiles as $item) {
            if (!empty($item['trs_trans_value'])) {
                $element->addMultiOption($item['trs_trans_id'], $item['trs_trans_value']);
            }
            $lastRow = $item['mtcs_attribute_class_id'];
        }
        $element->setName('attribute_'.$lastRow);
        $aElements[MtcsAttributeClasses::CLASS_FUNCTION_PROFIL] = $element;
        
        // srednica
        $element = new Zend_Form_Element_Select('dimater');
        $element->setLabel(Company_View_Helper_Translate::translate('search_car_tyre_diameter'));
        $element->addMultiOption('', Company_View_Helper_Translate::translate('select_any_f'));
        foreach ($dimmensiones as $item) {
            if (!empty($item['trs_trans_value'])) {
                $element->addMultiOption($item['trs_trans_id'], $item['trs_trans_value']);
            }
            $lastRow = $item['mtcs_attribute_class_id'];
        }
        $element->setName('attribute_'.$lastRow);
        $aElements[MtcsAttributeClasses::CLASS_FUNCTION_FELGI_SREDNICA] = $element;
        
        // producent
        $element = new Zend_Form_Element_Select('producer_itg');
        $element->setLabel(Company_View_Helper_Translate::translate('search_car_producer_tyre'));
        $element->addMultiOption('', Company_View_Helper_Translate::translate('select_any_m'));
        foreach ($manufactureres as $item) {
            $element->addMultiOption($item['trs_trans_id'], $item['trs_trans_value']);
            $lastRow = $item['mtcs_attribute_class_id'];
        }
        $element->setName('attribute_'.$lastRow);
        $aElements[MtcsAttributeClasses::CLASS_FUNCTION_PRODUCENT] = $element;
        
        return $aElements;
    }
}