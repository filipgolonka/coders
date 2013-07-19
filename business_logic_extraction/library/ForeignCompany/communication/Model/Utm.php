<?php
class ForeignCompany_communication_Model_Utm extends ForeignCompany_Business_StrategyAbstract{
	
    /**
     *
     * @var \EicPerson
     */
    private $_person = null;
    
    private $_searchVars = array();
    
    public function getPerson() {
        return $this->_person;
    }

    public function setPerson($oPerson) {
        $this->_person = $oPerson;
        return $this;
    }
    
    public function getSearchVars() {
        return $this->_searchVars;
    }

    public function setSearchVars($searchVars) {
        $this->_searchVars = $searchVars;
    }

    public function addUtm(\Doctrine_Record $entity, $content) {
        if ($entity->add_utm && !empty($entity->fk_utm_id)) {
            $utm = $entity->EisUtm;
            $utmLink = '?utm_source='.$utm->utm_source.'&utm_medium='.$utm->utm_medium.'&utm_campaign='.$utm->utm_campaign.'&utm_term='.$utm->utm_term.'&utm_content='.$utm->utm_content;
        } else {
            $utmLink = '';
        }

        //podmiana hasha sledzenia wejsc na strone glowna, strone promocji, produkty itp
        if (strstr($content, '%hash%')) {
            if ($this->_person instanceof EicPerson) {
                if (empty($utmLink)) {
                    $content = str_replace('%hash%', '?mailing='.$entity->comm_hash.'_'.$this->_person->prs_hash, $content);
                } else {
                    $content = str_replace('%hash%', $utmLink.'&mailing='.$entity->comm_hash.'_'.$this->_person->prs_hash, $content);
                }
            } else {
                if (empty($utmLink)) {
                    $content = str_replace('%hash%', '?mailing='.$entity->comm_hash.'_'.$this->_person->nle_hash, $content);
                }
                else {
                    $content = str_replace('%hash%', $utmLink.'&mailing='.$entity->comm_hash.'_'.$this->_person->nle_hash, $content);
                }
            }
        }

        //FIX ME
        //potrzebne dla starych szablonów
        if (strstr($content, '%hash2%')) {
            if ($this->_person instanceof EicPerson) {
                if (empty($utmLink)) {
                    $content = str_replace('%hash2%', '?mailing='.$entity->comm_hash.'_'.$this->_person->prs_hash, $content);
                } else {
                    $content = str_replace('%hash2%', $utmLink.'&mailing='.$entity->comm_hash.'_'.$this->_person->prs_hash, $content);
                }
            }
            else {
                if (empty($utmLink)) {
                    $content = str_replace('%hash2%', '?mailing='.$entity->comm_hash.'_'.$this->_person->nle_hash, $content);
                } else {
                    $content = str_replace('%hash2%', $utmLink.'&mailing='.$entity->comm_hash.'_'.$this->_person->nle_hash, $content);
                }
            }
        }

        if(get_class($this->_person) == 'Doctrine_Collection') {
            foreach ($this->_person as $person) {
                $aReplace = array(
                    $person->prs_fname
                );

                $body .= str_replace($this->_searchVars, $aReplace, $content) . '<div id="break"></div>';
            }
        } elseif ($this->_person instanceof EisNewsletterEmail) {
            $aReplace = array(
                $this->_person->nle_email
            );

            $body = str_replace($$this->_searchVars, $aReplace, $content);
        } else {
            $aReplace = array(
                $this->_person->prs_fname
            );

            $body = str_replace($this->_searchVars, $aReplace, $content);
        }

        $content = $this->_addUtmParams($content, $utmLink);
        
        return $content;
    }
    
    protected function _addUtmParams($content, $utmLink = null)
    {
        if (!empty($utmLink)) {
            $dom = new Zend_Dom_Query($content);

            $results = $dom->queryXpath('//*/a[@href]');
            foreach ($results as $result) {
                $src = $result->getAttribute('href');

                if ($src) {
                    $parseResult = parse_url($src);
                    if (isset($parseResult['query']) && !empty($parseResult['query'])) {
                        $parseResult['query'] = $utmLink . '&' . $parseResult['query'];
                    } else {
                        $parseResult['query'] = $utmLink;
                    }

                    $replaced = $parseResult['scheme'] . '://' . $parseResult['host'] . $parseResult['path'] . $parseResult['query'];
                    $result->setAttribute('href', $replaced);
                }
            }

            $content = $results->getDocument()->saveHTML();
            return $content;
        }
        return $content;
    }
    
}