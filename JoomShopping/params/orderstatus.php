<?php
/**
* @package Joomla
* @subpackage JoomShopping
* @author Nevigen.com
* @website https://nevigen.com/
* @email support@nevigen.com
* @copyright Copyright © Nevigen.com. All rights reserved.
* @license Proprietary. Copyrighted Commercial Software
* @license agreement https://nevigen.com/license-agreement.html
**/

defined('_JEXEC') or die;

class JFormFieldOrderstatus extends JFormField {

	public $type = 'orderstatus';

	protected function getInput(){
		require_once JPATH_SITE.'/components/com_jshopping/lib/factory.php';

		$db = JFactory::getDBO(); 
		$lang = JSFactory::getLang();
		$query = "SELECT status_id, `".$lang->get('name')."` as name FROM `#__jshopping_order_status` ORDER BY status_id";
		$db->setQuery($query);
		$status = $db->loadObjectList();

		return JHTML::_('select.genericlist', $status, $this->name.'[]', 'class="inputbox" multiple="multiple" size=3', 'status_id', 'name', empty($this->value) ? '' : $this->value);
	}

}