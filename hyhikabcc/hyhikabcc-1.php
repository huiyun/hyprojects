<?php
/**
 * @package	HikaShop for Joomla!
 * @version	2.1.3
 * @author	hikashop.com
 * @copyright	(C) 2010-2013 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');

class plgHikashopBccmail extends JPlugin
{
	function plgHikashopBccmail(&$subject, $config){
		parent::__construct($subject, $config);
		if(!isset($this->params)){
			$plugin = JPluginHelper::getPlugin('hikashop', 'bccmail');
			if(version_compare(JVERSION,'2.5','<')){
				jimport('joomla.html.parameter');
				$this->params = new JParameter($plugin->params);
			} else {
				$this->params = new JRegistry($plugin->params);
			}
		}
	}

	function onBeforeMailSend(&$mail,&$mailer){
			
		$bcc = explode(",",$this->params->get('bccmail_emails', ''));	
		foreach($bcc as $b) {
			$mailer->addBCC($b);
		}
    
	}	
}
