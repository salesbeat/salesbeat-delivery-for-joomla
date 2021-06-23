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

class JFormFieldShippings extends JFormField {

	public $type = 'shippings';

	protected function getInput(){
		require_once JPATH_SITE.'/components/com_jshopping/lib/factory.php'; 

		return JHTML::_( 'select.genericlist', array_merge(array('shipping_id'=>'', 'name'=>''), JTable::getInstance('ShippingMethod', 'jshop')->getAllShippingMethods(0)), $this->name, 'class="inputbox" size="1"', 'shipping_id', 'name', empty($this->value) ? '0' : $this->value );
	}

}