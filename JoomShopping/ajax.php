<?php

define('_JEXEC', 1);
define('JPATH_BASE', $_SERVER['DOCUMENT_ROOT']);

define('DS', DIRECTORY_SEPARATOR);

require_once(JPATH_BASE . DS . 'includes' . DS . 'defines.php');
require_once(JPATH_BASE . DS . 'includes' . DS . 'framework.php');
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

$strJson = file_get_contents('php://input');
$arResult = json_decode($strJson, true);

$session = &JFactory::getSession();
$session->set('salesbeat', $arResult);
