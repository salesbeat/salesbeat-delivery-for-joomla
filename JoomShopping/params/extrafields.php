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

class JFormFieldExtrafields extends JFormField {

	public $type = 'extrafields';

	protected function getInput(){
		require_once JPATH_SITE.'/components/com_jshopping/lib/factory.php';

		return JHTML::_('select.genericlist', array_merge(array(JText::_('JSELECT')), JSFactory::getAllProductExtraField()), $this->name, 'class="inputbox"" size=1', 'id', 'name', empty($this->value) ? '' : $this->value);
	}

}