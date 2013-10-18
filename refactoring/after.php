<?php
/**
 * Frontend form 
 * 
 * @author JohnDoe
 */
class Company_AdhocEdi_Form_Shop_Tyre extends Company_AdhocEdi_Form_Shop_Abstract {

    /** @var \Company_AdhocEdi_Model_Tyre */
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

        $elementsInfo = array(
            // sezon
            MtcsAttributeClasses::CLASS_FUNCTION_SEASON => array(
                'name' => 'season',
                'label' => 'search_car_season',
                'select_text' => 'select_any_m',
                'values' => $this->_oTyre->getSeason(),
            ),
            // przeznaczenie
            MtcsAttributeClasses::CLASS_FUNCTION_PRZEZNACZENIE => array(
                'name' => 'destination',
                'label' => 'search_car_destination',
                'select_text' => 'select_any_m',
                'values' => $this->_oTyre->getDestinationes(),
            ),
            // szerokosc
            MtcsAttributeClasses::CLASS_FUNCTION_SZEROKOSC => array(
                'name' => 'width',
                'label' => 'search_car_chains_width',
                'select_text' => 'select_any_f',
                'values' => $this->_oTyre->getWidths(),
            ),
            // profil
            MtcsAttributeClasses::CLASS_FUNCTION_PROFIL => array(
                'name' => 'range',
                'label' => 'search_car_chains_range',
                'select_text' => 'select_any_m',
                'values' => $this->_oTyre->getProfiles(),
            ),
            // srednica
            MtcsAttributeClasses::CLASS_FUNCTION_FELGI_SREDNICA => array(
                'name' => 'dimater',
                'label' => 'search_car_tyre_diameter',
                'select_text' => 'select_any_f',
                'values' => $this->_oTyre->getDimaters(),
            ),
            // producent
            MtcsAttributeClasses::CLASS_FUNCTION_PRODUCENT => array(
                'name' => 'producer_itg',
                'label' => 'search_car_producer_tyre',
                'select_text' => 'select_any_m',
                'values' => $this->_oTyre->getManufacturer(),
            ),
        );

        foreach($elementsInfo as $classFunction => $info) {
            $element = new Zend_Form_Element_Select($info['name']);
            $element->setLabel(Company_View_Helper_Translate::translate($info['label']));
            $element->addMultiOption('', Company_View_Helper_Translate::translate($info['select_text']));
            $lastRow = '';
            foreach($info['values'] as $item) {
                if (!empty($item['trs_trans_value'])) {
                    $element->addMultiOption($item['trs_trans_id'], $item['trs_trans_value']);
                }

                if(!empty($item['dict_attr_is_selected_on_default']) && $item['dict_attr_is_selected_on_default'] == true) {
                    $element->setValue($item['trs_trans_value']);
                }
                $lastRow = $item['mtcs_attribute_class_id'];
            }
            $element->setName('attribute_' . $lastRow);
            $aElements[$classFunction] = $element;
        }

        return $aElements;
    }
}