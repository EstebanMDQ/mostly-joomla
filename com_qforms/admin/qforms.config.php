<?php
/*
 *    This file is part of QForms
 *
 *    qForms is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    qForms is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with qForms.  If not, see <http://www.gnu.org/licenses/>.
 */


if(defined('QFORMS_PATH')) return;

/**
* Constantes Dependientes del servidor, o dinámicas.
**/
if(!defined('QFORMS_PATH')) define('QFORMS_PATH',  dirname(__FILE__).'/' );

define('QFORMS_LIBS',      QFORMS_PATH);
if( !defined('SITE_LOG_FILE'))
	define('QFORMS_DEBUG_FILE',	'/tmp/qforms.debug.log');
else
	define('QFORMS_DEBUG_FILE',	SITE_LOG_FILE );//dirname(QFORMS_PATH).'/tmp/qforms.debug.log');
ini_set('display_errors',   'on');

@session_start();


/**
* Constantes comunes a cualquier configuración
**/
if( !defined('SITE_URI') )
	define('SITE_URI','');
define('QFORMS_URI',            'components/com_qforms/');

define('QFORMS_PATH_TEMPLATES', QFORMS_PATH.'templates/');
define('JQUERY_URI', QFORMS_PATH.'templates/');


// Archivos de logging, debuggin, etc.
// Por defecto se toma el mismo archivo donde estï¿½ el error_log de PHP, si lo hay.

if(!defined('SITE_DEBUG_FILE'))
    define('SITE_DEBUG_FILE', ini_get('error_log')?ini_get('error_log'):QFORMS_PATH.'error.log');

if(!defined('SEND_MULTIPART_MAIL_LOG'))
    define('SEND_MULTIPART_MAIL_LOG', SITE_DEBUG_FILE);

if( !defined('QFORMS_DEFAULT_CURRENCY_SYMBOL') )
	define('QFORMS_DEFAULT_CURRENCY_SYMBOL','$');

/**
* Configuración PHP (puede no andar, dependiendo del servidor).
* NOTA: con las PEAR, la unica forma de hacer andar todo, es teniendo su path en el include_path
**/
ini_set('include_path', QFORMS_LIBS.':'.QFORMS_LIBS.'pear/');
ini_set('log_errors','on');
ini_set('display_errors','on');
ini_set('error_log', SITE_DEBUG_FILE);
ini_set('error_reporting', E_ALL);
ini_set('magic_quotes_runtime', 'off');
ini_set('magic_quotes_gpc', 'on');
ini_set('memory_limit', '64M');

/**
* Anti-cache
**/
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header("Pragma: no-cache");                          // HTTP/1.0

require_once(QFORMS_LIBS.'lwutils.php');
#require_once(QFORMS_LIBS.'mailer.lib.php');
//require_once(QFORMS_LIBS.'functions.php');
require_once(QFORMS_PATH.'modulo.qforms.php');


/**
* Cargo la configuración general.
**/
# db_connect();

$GLOBALS['SQLQuery_default_mode']='mysql';


/**
* Profiling stuff
**/
$GLOBALS['LWUTILS_DEBUGIT_EXTRA'] = @intval($_SESSION['loggeduser']);
LWUtils::ProfilerPageStart();
//session_destroy();
//variables de configuracion



?>