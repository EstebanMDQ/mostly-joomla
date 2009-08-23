<?php
/**
* @version		$Id: mod_login.php 10381 2008-06-01 03:35:53Z pasamio $
* @package		Joomla
* @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

$logged_position = $params->get('logged_position');
$unlogged_position = $params->get('unlogged_position');

$user = & JFactory::getUser();
if (!$user->get('guest'))
    $position = $params->get('logged_position');
else
    $position = $params->get('unlogged_position');


jimport('joomla.application.module.helper');
$mods = JModuleHelper::getModules($position);
if( !empty($mods) ) {
    foreach( $mods as $m ) {
	    echo JModuleHelper::renderModule($m);
    }
} else {
    echo "<h3>No contents to show on position $position</h3>";
}

