<?php

class OurCompany_Message_Adapter extends Zend_View_Helper_Abstract implements OurCompany_Message_Interface
{
    const  MSG_TYPE_EMAIL = 'email';
    const  MSG_TYPE_SYSTEM = 'sys';

    protected $_bEmailFlag = false;
    protected $_bCommunicationFlag = false;

    protected $module = null;
    protected $_msg_type = 'sys';       // typ wiadomosci sys - systemwa, email - email ;)
    protected $_msg_status = false;     // jeśli true wiadomość ma zostać wysłana odrazu - brane pod uwage tylko gdy type=email
    protected $_msg_email_to = null;
    protected $_msg_email_from = null;
    protected $_msg_email_cc = array();
    protected $_msg_email_bcc = array();

    protected $_value = array();
    protected $_template = null;

    protected $_iPersonId = null;

    /**
     * Email, który zostanie dodany do CC - UWAGA - po dodaniu nie jest blokowany!
     * @var string|null
     */
    protected $_forcedCopyEmail = null;

    /**
     * @var ModelMessage
     */
    protected $_oModelMessage = null;
    protected $_oTranslator = null;

    protected $_sPersonEmail = '';
    protected $_sPersonName = '';

    protected $_sSystemEmail = '';
    protected $_sSystemName = '';

    protected $_sTmxEmailTitle = 'message_title';
    protected $_sTmxEmailContent = 'message_content';

    protected $_sTmxEmailHeader = 'message_header';
    protected $_sTmxEmailFooter = 'message_footer';

    protected $_renderHeader = true;
    protected $_limitLinkLength = true;

    protected $_aTitleParams = array();
    protected $_aContentParams = array();
    protected $_encoding = null;

    protected $_commId = null;

    protected $_additionalTitle = "";

    /**
     * @var EicPerson
     */
    protected $_personFrom = null;

    /**
     *
     * @var \EicMessage
     */
    private $_oEicMessage = null;
	
	protected $_replyToEmail;
    protected $_replyToName;
	
	protected $_useHtmlWrap = true;
	
	protected $_baseUrl;
	
	protected $_attachments = array();
    protected $_attachementFromContent = false;
	
    public static function repairHtml($content)
    {
        if (function_exists('tidy_repair_string')) {
            $xhtml = tidy_repair_string($content, array(
                'output-xhtml' => true,
                'show-body-only' => true,
                'doctype' => 'strict',
                'drop-font-tags' => true,
                'drop-proprietary-attributes' => true,
                'lower-literals' => true,
                'quote-ampersand' => true,
                'wrap' => 0), 'utf8');
            return $xhtml;
        } else {
            $xhtml = $content;
            return $xhtml;
        }
    }

    public function  __construct($view = null, /* @var $oPerson EicPerson */ $oPerson = null, $sEncoding = 'utf-8')
    {
        if (!$view) {
            $this->view = Zend_Layout::startMvc()->getView();
        }
        else {
            $this->view = $view;
        }
        
        $this->view->addHelperPath('OurCompany' . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Helper', 'OurCompany_View_Helper');
                                
        if (is_object($oPerson)) {
            $this->_bCommunicationFlag = $oPerson->prs_messsage_notif;
            $this->_bEmailFlag = $oPerson->prs_mail_notif;

            $this->_iPersonId = $oPerson->prs_id;
            $this->_sPersonEmail = $oPerson->prs_email;
            $this->_sPersonName = (string) $oPerson;
        }

        $oConfig = new EisConfiguration();
        $this->_sSystemEmail = $oConfig->smartGetConfOption(EisConfiguration::CONF_APP_MAILER_EMAIL, false);
        $this->_sSystemName = $oConfig->smartGetConfOption(EisConfiguration::CONF_APP_MAILER_NAME, false);

        $this->_oModelMessage = ModelMessage::getInstance();
        $this->_oTranslator = Zend_Registry::get('translator');
        $this->_encoding = $sEncoding;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $sContent = '';
        if ($this->_useHtmlWrap) {
            $sContent = '<html><head><meta content="text/html; charset='.$this->_encoding.'"http-equiv="content-type"></head><body>';
        }

        $layoutConfig       = Zend_Registry::get('layout');
        $host               = Zend_Registry::get('host');

        if($this->_renderHeader) {
            $translate = sprintf('%s_%s', $this->_sTmxEmailHeader, $layoutConfig['defaultTheme']);
            $translated = $this->_oTranslator->_($translate);
            if ($translated == $translate) {
                // nie ma tłumaczenia - korzystam z default
                $sContent .= $this->_oTranslator->_($this->_sTmxEmailHeader);
            } else {
                // przetłumaczone
                $sContent.= $translated;
            }
        }

        $sContent.= $this->_aContentParams;

        if($this->_renderHeader) {
            $translate = sprintf('%s_%s', $this->_sTmxEmailFooter, $layoutConfig['defaultTheme']);
            $translated = $this->_oTranslator->_($translate);
            if ($translated == $translate) {
                // nie ma tłumaczenia - korzystam z default
                $sContent .= $this->_oTranslator->_($this->_sTmxEmailFooter);
            } else {
                // przetłumaczone
                $sContent .= $translated;
            }
        }

        if ($this->_useHtmlWrap)
        {
            $sContent.= '</body></html>';
        }

        $sContent = str_replace('_themeName_', $layoutConfig['defaultTheme'], $sContent);
        if(default_Model_Shop::isWholesaleForeign()){
            $sContent = str_replace('/images/logo.gif', '/images/logo_biz.gif', $sContent);
        }
        
        $showMessageInBrowserUrl = '#';
        
        if($this->_oEicMessage instanceof EicMessage) {
            $hash = $this->_oEicMessage->msg_hash_preview;
            $showMessageInBrowserUrl = OurCompany_View_Helper_CommunicationPreviewLink::communicationPreviewLink($hash, false, true);
            $sContent = str_replace('_VIEW_MESSAGE_IN_BROWSER_URL_', $showMessageInBrowserUrl, $sContent);
        }
        $sContent = self::replaceVars($sContent);

        return $sContent;
    }

    public static function replaceVars($content)
    {
        $host = Zend_Registry::get('host');
        $content = str_replace('_hostHttp_', @$host['http'], $content);
        $content = str_replace('_hostName_', parse_url(@$host['http'], PHP_URL_HOST), $content);
        return $content;
    }

    private function getTitle()
    {
        $sContent = $this->_oTranslator->_($this->_sTmxEmailTitle);
        foreach ($this->_aTitleParams as $key => $value)
        {
            $sContent = mb_eregi_replace(':'.$key, $value, $sContent);
        }

        return $sContent ;
    }

    public function sendEmail()
    {
        $this->checkMail();
        
        $oZendMail = new Zend_Mail($this->_encoding);
        
        // sprawdzanie czy wysylka jest zablokowana
        // jesli nie, to dodajemy kopie na adres zdefiniowany jako bcc
        if(!$this->_isBlocked() && count($this->_msg_email_bcc)) {
            OurCompany_Log::debug('Communication isn\'t blocked, copy will be also sent to email address from list below', __CLASS__);
            $oZendMail->addBcc($this->_msg_email_bcc);
        }

        //$oZendMail->setHeaderEncoding(ZEND_MIME::ENCODING_QUOTEDPRINTABLE);

        if ($forcedCopyEmail = $this->getForcedCopyEmail()) {
            $oZendMail->addCc($forcedCopyEmail);
        }

        $content = $this->getContent();

        if ($this->getAttachmentFromContent())
        {
            try
            {
                $xhtml = self::repairHtml($content);

                $baseUrl = $this->getBaseUrl();

                $xhtml = preg_replace('#(src)="([^:"]*)("|(?:(?:%20|\s|\+)[^"]*"))#','$1="'.$baseUrl.'$2$3',$xhtml);

                $dom = new DOMDocument(null, 'UTF-8');
                $dom->loadHTML($xhtml);
                $images = $dom->getElementsByTagName('img');

                for ($i = 0; $i < $images->length; $i++) {
                    $img = $images->item($i);

                    $url = $img->getAttribute('src');

                    $image_http = new Zend_Http_Client($url);
                    $image_content = $image_http->request()->getBody();

                    $response = $image_http->getLastResponse();

                    $pathinfo = pathinfo($url);

                    $mime_type = $response->getHeader('Content-type');

                    $hash = md5($image_content);

                    $mime = new OurCompany_Message_Part($image_content);
                    $mime->location = $url;
                    $mime->type        = $mime_type;//.";\n\tname=\"".$pathinfo['basename']."\"";
                    $mime->disposition = Zend_Mime::DISPOSITION_INLINE;
                    $mime->encoding    = Zend_Mime::ENCODING_BASE64;
                    $mime->filename    = $pathinfo['basename'];
                    $mime->id = 'cid_' . $hash;

                    $img->setAttribute('src','cid:' . $mime->id);

                    $oZendMail->addAttachment($mime);
                }

                $content = $dom->saveHTML();
            } catch(Exception $e) {
                OurCompany_Log::exception($e);
            }

            /*
             * Wylaczenie logowania error'ow dla DOMDocument
             */
            restore_error_handler();
        }


        # dodawanie załączników
        if (count($this->_attachments))
        {
            foreach ($this->_attachments as $attachment)
            {
                list($body, $mimeType, $disposition, $encoding, $filename) = $attachment;
                $oZendMail->createAttachment($body, $mimeType, $disposition, $encoding, $filename);
            }
        }

        if (!empty($this->_replyToEmail))
        {
            /*
             * 
             * http://framework.zend.com/issues/browse/ZF-8723 
             */
            $email = $this->_filterEmail($this->_replyToEmail);
            $name  = $this->_filterName($this->_replyToName);
            $address = $this->_formatAddress($email, $name);

            $oZendMail->setReplyTo($address);
        }

        if ($this->_commId) {
            $oZendMail->addHeader('commId', $this->_commId);
        }

        $oZendMail->addHeader('orgrec', $this->_sPersonEmail);

        OurCompany_Log::debug(sprintf('TO: %s <%s>', $this->_sPersonName, $this->_sPersonEmail), __CLASS__);
        OurCompany_Log::debug(sprintf('FROM: %s <%s>', $this->_sSystemName, $this->_sSystemEmail), __CLASS__);
        foreach($this->_msg_email_bcc as $bcc) {
            OurCompany_Log::debug(sprintf('BCC: %s <%s>', $bcc, $bcc), __CLASS__);
        }

        $oZendMail
                ->setBodyHtml($content)
                ->setFrom($this->_sSystemEmail, $this->_sSystemName)
                ->addTo($this->_sPersonEmail, $this->_sPersonName)
                ->setSubject($this->getTitle() . $this->_additionalTitle)
                ->send();
    }

    public function getDebugSendData($asString = false)
    {
        $return = array(
            '$this->_sSystemEmail' => $this->_sSystemEmail,
            '$this->_sPersonEmail' => $this->_sPersonEmail,
            '$this->_additionalTitle' => $this->_additionalTitle,
            '$this->getTitle()' => $this->getTitle(),
        );

        $return = ($asString) ? print_r($asString, true) : $return;

        return $return;
    }

    public function addAttachment($body,
                                  $filename    = null,
                                  $mimeType    = Zend_Mime::TYPE_OCTETSTREAM,
                                  $disposition = Zend_Mime::DISPOSITION_ATTACHMENT,
                                  $encoding    = Zend_Mime::ENCODING_BASE64)
    {
        $this->_attachments[] = array($body, $mimeType, $disposition, $encoding, $filename);
    }
	
    public function sendCommunication()
    {
        try {
            $this->_oEicMessage = $this->_oModelMessage->addMessageToPerson(
                $this->_iPersonId,
                $this->getTitle(),
                $this->getContent(),
                $this->_msg_type,
                $this->_msg_status,
                $this->_sSystemEmail,
                $this->_sPersonEmail,
                $this->_msg_email_cc,
                $this->_msg_email_bcc
            );
        } catch (Exception $e) {
            OurCompany_Log::exception($e);
            Zend_Debug::dump($e->getMessage());
            exit(); 
        }
    }

    public function send()
    {
        if($this->_msg_type == 'email' && $this->_msg_status) {
            try {
                $this->sendEmail();
            } catch(Zend_Mail_Transport_Exception $e) {
                OurCompany_Log::debug(sprintf('SENDING FAILED, EXCEPTION: %s', $e->getMessage()), __CLASS__);
                $this->_msg_status = false;
            } catch(Exception $e) {
                OurCompany_Log::debug(sprintf('ANOTHER EXCEPTION: %s', $e->getMessage()), __CLASS__);
                throw $e;
            }
        }
        
        $this->sendCommunication();
    }

    /*
     * Funkcja powoduje automatyczne nadawnie znacznika <a> URL'a oraz adresom e-mail
     */
    private function url($txt)
    {
        /* tablice statyczne */
        static $patterns = array();
        static $replacements = array();
        /* Automatyczne wykrywanie URL i adresow e-mail */

        if ( !$patterns )
        {
            $replacement = $this->_limitLinkLength ? "' . substr('$1', 0, 70) . '" : '$1';
            //linki z protokolem [np. http://cos.pl, ftp://cos.pl]
            //              $patterns[] = '#(^|[\n ]|\()([\w]+:/{2}.*?(?:[^ \t\n\r<"\'\)&]+|&(?!lt;))*)#ie';    //bug #230
            $patterns[] = '#(?:^|(?<=[][()<>\s]|&gt;|&lt;))(\w+:/{2}.*?(?:[^][ \t\n\r<>")&,\']+|&(?!lt;|gt;)|,(?!\s)|\[])*)#ie';
            $replacements[] = "'<a href=\"$1\">$replacement</a>'";

            //linki bez protokolu, z 'www' na poczatku
            //              $patterns[] = '#(^|[\n ]|\()(w{3}\.[\w\-]+\.[\w\-.\~]+(?:[^ \t\n\r<"\'\)&]+|&(?!lt;))*)#ie';    //bug #230
            $patterns[] = '#(?:^|(?<=[][()<>"\'\s]|&gt;|&lt;))(w{3}\.[\w-]+\.[\w.~-]+(?:[^][ \t\n\r<>")&,\']+|&(?!lt;|gt;)|,(?!\s)|\[])*)#ie';
            $replacements[] = "'<a href=\"http://$1\">$replacement</a>'";

            //              $patterns[] = "@\b(?:(http(?:s)?://|ftp://)|(www\.))([a-z0-9_-]+(?:(?:/|\.)[][(){}^/$,?+#*:;%~=&a-z0-9ąćęłńóśźżĄĆĘŁŃÓŚŹŻ_-]+)+)@ie";
            //              $replacements[] = "'<a href=\"' . ('\\1' == ''?'http://\\2':'\\1') . '\\3\">' . cut_url('\\1\\2\\3', 70) . '</a>\n\n'";

            //adresy e-mail
            $patterns[] = '#(^|[\n ]|\()([a-z0-9&\-_.]+?@[\w\-]+\.(?:[\w\-\.]+\.)?[\w]+)#i';
            $replacements[] = "\$1<a href=\"mailto:\$2\">$2</a>";
        }

        return preg_replace($patterns, $replacements, $txt);
    }


    /**
     * Dla srodowisk nieprodukcyjnych, nie puszczamy maili innych niz z domeny ourcompany.pl
     */
    private function checkMail() {
        if ($this->_isBlocked())
        {
            $oConfig = new EisConfiguration();
            $email = $oConfig->smartGetConfOption(EisConfiguration::CONF_APP_SENDBLOCKED_EMAIL, false);

            $origRcpt = array();
            $origRcpt[] = $this->_sPersonEmail;

            $this->_sPersonEmail = $email;

            foreach ($this->_msg_email_cc as $k => $tmp) {
                $origRcpt[] = $tmp;
                $this->_msg_email_cc[$k] = $email;
            }
            
            foreach ($this->_msg_email_bcc as $k => $tmp) {
                $origRcpt[] = $tmp;
                $this->_msg_email_bcc[$k] = $email;
            }

            if (Zend_Registry::get('sendblocked_copy_enabled')){
                $this->_forcedCopyEmail = Zend_Registry::get('sendblocked_copy');
            }

            $this->_additionalTitle = " [orig rcpt: " . implode(",",$origRcpt) . "]";
        }
    }
    
    /**
     * sprawdzanie, czy komunikacja mailowa jest zablokowana
     * @return bool true jesli jest blokowana
     */
    private function _isBlocked() {
        $oConfig = new EisConfiguration();
        
        return $oConfig->smartGetConfOption(EisConfiguration::CONF_APP_MAILER_BLOCKED, false);
    }
    /**
     * Filter of email data
     *
     * @param string $email
     * @return string
     */
    protected function _filterEmail($email)
    {
        $rule = array("\r" => '',
                      "\n" => '',
                      "\t" => '',
                      '"'  => '',
                      ','  => '',
                      '<'  => '',
                      '>'  => '',
        );

        return strtr($email, $rule);
    }

    /**
     * Filter of name data
     *
     * @param string $name
     * @return string
     */
    protected function _filterName($name)
    {
        $rule = array("\r" => '',
                      "\n" => '',
                      "\t" => '',
                      '"'  => "'",
                      '<'  => '[',
                      '>'  => ']',
        );

        return trim(strtr($name, $rule));
    }

    /**
     * Formats e-mail address
     *
     * @param string $email
     * @param string $name
     * @return string
     */
    protected function _formatAddress($email, $name)
    {
        if ($name === '' || $name === null || $name === $email) {
            return $email;
        } else {
            $encodedName = $this->_encodeHeader($name);
            if ($encodedName === $name && strpos($name, ',') !== false) {
                $format = '"%s" <%s>';
            } else {
                $format = '%s <%s>';
            }
            return sprintf($format, $encodedName, $email);
        }
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    public function setBaseUrl($baseUrl)
    {
        $this->_baseUrl = $baseUrl;
    }
	
	public function setEmail($sEmail, $name = null)
    {
        $this->_sPersonEmail = $sEmail;
        $this->_sPersonName = $name;
    }
	
	 public function setRenderHeader($value) {
        $this->_renderHeader = $value;
    }

    public function setMsgType($type) {
        $this->_msg_type = $type;
    }

    public function setMsgStatus($status) {
        $this->_msg_status = $status;
    }

    public function setModule($value) {

        $this->module = $value;
    }

    public function setLimitLinkLength($limitLinkLength)
    {
        $this->_limitLinkLength = $limitLinkLength;
    }

    public function getLimitLinkLength()
    {
        return $this->_limitLinkLength;
    }
	
	public function setPersonFrom(EicPerson $oPersonFrom)
    {
        $this->_sSystemEmail = $oPersonFrom->EifUser->usr_email;
        $this->_sSystemName =(string) $oPersonFrom;
    }

    public function setNameFrom($sName){
        $this->_sSystemName = $sName;
    }

    public function setFrom($sFrom)
    {
        $this->_sSystemEmail = $sFrom;
    }

    public function setToHeaderCommId($comm_id) {
        $this->_commId = $comm_id;
    }

    public function getHeaderCommId() {
        return $this->_commId;
    }
	
	public function setReplyTo($email, $name = null)
    {
        $this->_replyToEmail = $email;
        $this->_replyToName = $name;
    }

    public function setTitle($sTmxTitle, array $aParams = array())
    {
        $this->_sTmxEmailTitle = $sTmxTitle;
        $this->_aTitleParams = $aParams;
    }

    public function setContent(array $aParams = array())
    {
        $sContent = $this->view->partial('_mail/'.$this->getTemplate().'.txt.phtml', $this->module,  $aParams);
        $this->_aContentParams = $this->url($sContent);
    }

    /*
    * ustawia gotowy content
    */
    public function setContentText($content)
    {
        $this->_aContentParams = $content;
    }

    public function setEmailHeader($sTmxHeader)
    {
        $this->_sTmxEmailHeader = $sTmxHeader;
    }

    public function setEmailFooter($sTmxFooter)
    {
        $this->_sTmxEmailFooter = $sTmxFooter;
    }
	
	public function setUseHtmlWrap($flag = true)
    {
        $this->_useHtmlWrap = (bool) $flag;
    }
	
	public function getEmail()
    {
        return $this->_sPersonEmail;
    }
	
	public function turnOnSendingEmails()
    {
        $this->_bEmailFlag = true;
    }


    public function setValue($data = array())
    {
        $this->_value = $data;
    }


    public function getValue()
    {
        return  $this->_value;
    }

    public function setTemplate($template)
    {
        $this->_template = $template;
    }


    public function getTemplate()
    {
        return $this->_template;
    }


    public function setPersonId($iPersonId) {
        $this->_iPersonId = $iPersonId;
    }
    
    public function addCC($email) {
        if($email){
            if (!is_array($email)) {
                $email = array($email);
            }

            foreach($email as $address) {
                $this->_msg_email_cc [] = $address;
            }
        }
    }
    
    public function addBCC($email) {
        if($email){
            if (!is_array($email)) {
                $email = array($email);
            }

            foreach($email as $address) {
                $this->_msg_email_bcc [] = $address;
            }
        }
    }
	
	public function setAttachmentFromContent($flag = true)
    {
        $this->_attachementFromContent = (bool) $flag;
    }

    public function getAttachmentFromContent()
    {
        return $this->_attachementFromContent;
    }

    private function getForcedCopyEmail($reset = true) {
        $email = $this->_forcedCopyEmail;

        if ($reset){
            $this->_forcedCopyEmail = null;
        }

        return $email;
    }
}