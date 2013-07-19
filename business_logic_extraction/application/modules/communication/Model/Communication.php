<?php

class communication_Model_Communication {
    
    private $_strategy = null;
    
    public function __construct() {
        $businessConfig = Zend_Registry::get('business_logic');
        if(isset($businessConfig['message_adapter']['utm']) && class_exists($businessConfig['message_adapter']['utm'])) {
            $class = $businessConfig['message_adapter']['utm'];
            $this->_strategy = new $class($this);
        }
    }
    
    /**
     * Wypelnia komunikacje dla jednego usera
     *
     * @param EicComm $entity
     * @param EicPerson $oPersons
     * @param bool $localImagesPath
     * @return string
     */
    protected function _fillCommunication($entity, $oPersons, $localImagesPath = false)
    {
        $host = Zend_Registry::get('host');
        $directory = $host['dir'];

        $aSearchVars = array();
        $aMatches = array();
        $body = '';
        $aReplace = array("\r\n", "\n", "\r");

        $conf = Zend_Registry::get('host');
        $oBaseUrl = new Empathy_View_Helper_BaseUrl();
        $oBaseUrl->setBaseUrl($conf['http']);

        $content = $this->_getCommunicationTemplateData($entity->fk_template_id);

        $content = str_replace($aReplace, '', $content);

        $aSearch = $this->getTemplateVariables();
        $pattern = '/<body[^>]*>(.*?)<\/body>/';

        preg_match($pattern, $content, $aMatches);

        foreach($aSearch as $aSearchVar) {
            $aSearchVars[] = $aSearchVar['name'];
        }

        $templateHtml = $content;

        $previewLink = '';
        if ($entity->comm_type == self::TYPE_MAIL) {
            $previewLink = Empathy_View_Helper_CommunicationPreviewLink::communicationPreviewLink($this->_messageHash);
        }

        /*
         *  Nowa zminna umozliwiajace wylaczenie appndowania contentu
         * linkime podgladau wiadomosci jezeli jej nie mozesz odczytac
         */
        $communicationBodyContent = $entity->content;
        if (false === strstr($entity->content, '%no_preview_link%')) {
            $communicationBodyContent = $entity->content.$previewLink;
        } else {
            $communicationBodyContent = str_replace('%no_preview_link%', '', $communicationBodyContent);
        }

        /*
         * Zmienna umozliwiajaca podanie linku podgladu nieczytelnej wiadomosci
         * w dowolnym zdjeciu
         */
        $previewLink = Empathy_View_Helper_CommunicationPreviewLink::communicationPreviewLink($this->_messageHash, false, true);
        $communicationBodyContent = str_replace('%preview_link%', $previewLink, $communicationBodyContent);

        if(isset($aMatches[1])) {
            //$content = str_replace('<content />', $communicationBodyContent, $aMatches[1]);
            $content = preg_replace('@(<\s*content\s*\/\s*>)|(<\s*content\s*>\s*<\s*\/\s*content\s*>)@', $communicationBodyContent, $aMatches[1]);
        }else{
            $content = $communicationBodyContent;
        }

        //link wypisania się z newslettera

        if (strstr($content, '%removeLink%')) {
            if ($oPersons instanceof EicPerson) {
                $content = str_replace('%removeLink%', $oBaseUrl->getBaseUrl().'/pl-PL/newsletters/remove/prs_hash/'.$oPersons->prs_hash, $content);
            }
            else {
                $content = str_replace('%removeLink%', $oBaseUrl->getBaseUrl().'/pl-PL/newsletters/remove/nle_hash/'.$oPersons->nle_hash, $content);
            }
        }

        if (strstr($content, '%title%')) {
            $content = str_replace('%title%', $entity->title, $content);
        }

        if (strstr($content, '%content%')) {
            $content = str_replace('%content%', $communicationBodyContent, $content);
        }

        if (strstr($content, '%footer%')) {
            $content = str_replace('%footer%', $entity->footer, $content);
        }

        if (strstr($content, '%promoHeader%')) {
            $content = str_replace('%promoHeader%', $entity->prom_header, $content);
        }

        if ($entity->fk_art_id) {
            $content = str_replace('%promo_magazine_id%', $entity->fk_art_id, $content);
        }
        else {
            $content = str_replace('%promo_magazine_id%', '', $content);
        }

        if ($entity->banner_id) {
            $banner = Doctrine::getTable('CmsWgBannerList')->find($entity->banner_id);
            if (strstr($content, '%banner%')) {
                $content = str_replace('%banner%', $banner->ban_file_name, $content);
                if (strstr($banner->ban_url, 'http') || strstr($banner->ban_url, 'www')) {
                    $content = str_replace('%bannerLink%', $banner->ban_url.'%hash2%', $content);
                }
                else {
                    $content = str_replace('%bannerLink%', $oBaseUrl->getBaseUrl().$banner->ban_url.'%hash2%', $content);
                }
            }
        }
        else {
            $content = str_replace('%banner%', '', $content);
            $content = str_replace('%bannerLink%', '', $content);
        }

        $content .= sprintf('<img src="%s">', newsletter_Model_Default::get1pxImageCountAppLink());

        /**
         * Podmiana poduktów w szablonie
         */

        if (strstr($content, '%baseUrl%')) {
            $content = str_replace('%baseUrl%', $oBaseUrl->getBaseUrl(), $content);
        }

        if (strstr($content, '%main_product%')) {
            if (!empty($entity->main_product_code)) {
                $product = $this->getProductByIndex($entity->main_product_code);
                if (!empty($product)) {
                    $product['pgr_name']        = Empathy_View_Helper_ReplaceInUrl::replaceInUrl($product['pgr_name']);
                    $product['prod_name_url']   = Empathy_View_Helper_ReplaceInUrl::replaceInUrl($product['prd_name']);

                    $foto = explode('/', $product['prd_main_image']);
                    $fotoFile = end($foto);

                    if (!empty($foto)) {
                        $foto = $oBaseUrl->getBaseUrl().'/public/foto/prdcard_'.$fotoFile;
                    }
                    else {
                        $foto = $oBaseUrl->getBaseUrl().self::NO_PHOTO;
                    }

                    if ($product['prd_price_old_gross'] && $product['prd_price_old_gross'] > $product['prd_price_new_min_gross']) {
                        $price = '<span style="font-size: 12px; color: #525252; margin-right: 10px;  text-decoration: line-through; line-height: 20px; font-family: Tahoma, Arial, sans-serif"> '.str_replace('.', ',', round($product['prd_price_old_gross'], 2)).' zł </span>';
                    }
                    else {
                        $price = '';
                    }

                    list($width, $height) = @getimagesize($directory.'public/foto/prdcard_'.$fotoFile);

                    if ($width * 185 > $height * 225) {
                        $widthOrHeight  = 'width: 225px;';
                        $padding        = 'padding-top: ' . (int)((186 - ((225 * $height ) / $width)) / 2 + 0.5) .'px; padding-bottom: ' . (int)((186 - ((225 * $height ) / $width)) / 2 + 0.5) .'px;';
                    }
                    else {
                        $widthOrHeight  = 'height: 185px;';
                        $padding        = 'padding-top: 0;';
                    }

                    if (strstr($content, '%main_product%')) {
                        $content = str_replace('%main_product%', '
                                    <!-- PRODUCT IMAGE -->
                                <td style="width: 250px; vertical-align: top; padding-bottom: 10px;">
                                    <div style="width: 153px; margin-left: auto; margin-right: auto;">
                                        <a href="'.$oBaseUrl->getBaseUrl().'/pl-PL/produkt/'.$product['prd_id'].'/'.$product['pgr_id'].'/'.$product['pgr_name'].'/'.$product['prod_name_url'].'/prod_'.$i.'/%hash%" style="color: #1C60A2; text-decoration: none;">
                                            <img alt="" src="'.$foto.'" style="margin-bottom: 10px; '.$widthOrHeight.' display: block; vertical-align: middle; border: 1px solid #ccc;" />
                                            <br />
                                            <span style="display: block; overflow: hidden; padding: 3px;">
                                                '.$product['prd_name'].'         
                                                <br>
                                            </span>
                                            <span style="font-size: 15px; line-height: normal; overflow: auto;">
                                                <span style="color: #000; font-weight: 700;">
                                                    <span>'.str_replace('.', ',', round($product['prd_price_new_min_gross'],2)).'zł </span>
                                                </span>
                                            </span>
                                        </a>
                                    </div>
                                </td>
                            ', $content);
                    }
                    else {
                        $content = str_replace('%main_product%', '', $content);
                    }
                }
                else {
                    $content = str_replace('%main_product%', '', $content);
                }
            }
        }

        $i = 1;
        $products = unserialize($entity->products);
        foreach($products as $product) {

            $product = $this->getProductByIndex(trim($product));
            if (!empty($product)) {
                $product['pgr_name']        = Empathy_View_Helper_ReplaceInUrl::replaceInUrl($product['pgr_name']);
                $product['prod_name_url']   = Empathy_View_Helper_ReplaceInUrl::replaceInUrl($product['prd_name']);

                if ($product['prd_price_old_gross'] && $product['prd_price_old_gross'] > $product['prd_price_new_min_gross']) {
                    $price = '<span style="font-size: 12px; color: #525252; margin-right: 10px;  text-decoration: line-through; line-height: 20px; font-family: Tahoma, Arial, sans-serif"> '.str_replace('.', ',', round($product['prd_price_old_gross'], 2)).' </span>';
                }
                else {
                    $price = '';
                }

                $foto = explode('/', $product['prd_main_image']);
                $fotoFile = end($foto);

                if (!empty($foto)) {
                    $foto = $oBaseUrl->getBaseUrl().'/public/foto/prdcard_'.$fotoFile;
                }
                else {

                    $foto = $oBaseUrl->getBaseUrl().self::NO_PHOTO;
                }

                list($width, $height) = @getimagesize($directory.'public/foto/prdcard_'.$fotoFile);

                if ($width * 185 > $height * 225) {
                    $widthOrHeight  = 'width: 225px;';
                    $padding        = 'padding-top: ' . (int)((185 - ((225 * $height ) / $width)) / 2 + 0.5) .'px; padding-bottom: ' . (int)((186 - ((225 * $height ) / $width)) / 2 + 0.5) .'px;';
                }
                else {
                    $widthOrHeight  = 'height: 185px;';
                    $padding        = 'padding-top: 0;';
                }

                if (strstr($content, '%product'.$i.'%')) {
                    $content = str_replace('%product'.$i.'%', '
                                    
            <td style="width: 250px; vertical-align: top; padding-bottom: 10px;">
                <div style="width: 153px; margin-left: auto; margin-right: auto;">
                    <a href="'.$oBaseUrl->getBaseUrl().'/pl-PL/produkt/'.$product['prd_id'].'/'.$product['pgr_id'].'/'.$product['pgr_name'].'/'.$product['prod_name_url'].'/prod_'.$i.'/%hash%" style="color: #1C60A2; text-decoration: none;">
                        <img alt="" src="'.$foto.'" style="margin-bottom: 10px; '.$widthOrHeight.' display: block; vertical-align: middle; border: 1px solid #ccc;" />
                        <br />
                        <span style="display: block; overflow: hidden; padding: 3px;">
                            '.$product['prd_name'].'         
                            <br>
                        </span>
                        <span style="font-size: 15px; line-height: normal; overflow: auto;">
                            <span style="color: #000; font-weight: 700;">
                                <span>'.str_replace('.', ',', round($product['prd_price_new_min_gross'],2)).'zł </span>
                            </span>
                        </span>
                    </a>
                </div>
            </td>
                                    ', $content);
                }
                else {
                    $content = str_replace('%product'.$i.'%', '', $content);
                }
            }
            else {

                $content = str_replace('%product'.$i.'%', '', $content);

            }
            $i++;
        }
        
        /** ************************************************************************************ */
        if($this->_strategy !== null) {
            $this->_strategy->setPerson($oPersons);
            $this->_strategy->setSearchVars($aSearchVars);
            $content = $this->_strategy->addUtm($entity, $content);
        }
        /** ************************************************************************************ */
        
        return $content;
    }
    
}
