<?php
//
// +--------------------------------------------------------------------+
// | PEAR, the PHP Extension and Application Repository                 |
// +--------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                              |
// +--------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,     |
// | that is bundled with this package in the file LICENSE, and is      |
// | available through the world-wide-web at the following url:         |
// | http://www.php.net/license/3_0.txt.                                |
// | If you did not receive a copy of the PHP license and are unable to |
// | obtain it through the world-wide-web, please send a note to        |
// | license@php.net so we can mail you a copy immediately.             |
// +--------------------------------------------------------------------+
// | Authors: Sterling Hughes <sterling@php.net>                        |
// |          Stig Bakken <ssb@php.net>                                 |
// |          Tomas V.V.Cox <cox@idecnet.com>                           |
// +--------------------------------------------------------------------+
//
// $Id: PEAR.php,v 1.82.2.4 2004/10/22 05:53:36 cellog Exp $
//

define('PEAR_ERROR_RETURN',     1);
define('PEAR_ERROR_PRINT',      2);
define('PEAR_ERROR_TRIGGER',    4);
define('PEAR_ERROR_DIE',        8);
define('PEAR_ERROR_CALLBACK',  16);
/**
 * WARNING: obsolete
 * @deprecated
 */
define('PEAR_ERROR_EXCEPTION', 32);
define('PEAR_ZE2', (function_exists('version_compare') &&
                    version_compare(zend_version(), "2-dev", "ge")));

if (substr(PHP_OS, 0, 3) == 'WIN') {
    define('OS_WINDOWS', true);
    define('OS_UNIX',    false);
    define('PEAR_OS',    'Windows');
} else {
    define('OS_WINDOWS', false);
    define('OS_UNIX',    true);
    define('PEAR_OS',    'Unix'); // blatant assumption
}

// instant backwards compatibility
if (!defined('PATH_SEPARATOR')) {
    if (OS_WINDOWS) {
        define('PATH_SEPARATOR', ';');
    } else {
        define('PATH_SEPARATOR', ':');
    }
}

$GLOBALS['_PEAR_default_error_mode']     = PEAR_ERROR_RETURN;
$GLOBALS['_PEAR_default_error_options']  = E_USER_NOTICE;
$GLOBALS['_PEAR_destructor_object_list'] = array();
$GLOBALS['_PEAR_shutdown_funcs']         = array();
$GLOBALS['_PEAR_error_handler_stack']    = array();

ini_set('track_errors', true);

/**
 * Base class for other PEAR classes.  Provides rudimentary
 * emulation of destructors.
 *
 * If you want a destructor in your class, inherit PEAR and make a
 * destructor method called _yourclassname (same name as the
 * constructor, but with a "_" prefix).  Also, in your constructor you
 * have to call the PEAR constructor: $this->PEAR();.
 * The destructor method will be called without parameters.  Note that
 * at in some SAPI implementations (such as Apache), any output during
 * the request shutdown (in which destructors are called) seems to be
 * discarded.  If you need to get any debug information from your
 * destructor, use error_log(), syslog() or something similar.
 *
 * IMPORTANT! To use the emulated destructors you need to create the
 * objects by reference: $obj =& new PEAR_child;
 *
 * @since PHP 4.0.2
 * @author Stig Bakken <ssb@php.net>
 * @see http://pear.php.net/manual/
 */
class PEAR
{
    // {{{ properties

    /**
     * Whether to enable internal debug messages.
     *
     * @var     bool
     * @access  private
     */
    var $_debug = false;

    /**
     * Default error mode for this object.
     *
     * @var     int
     * @access  private
     */
    var $_default_error_mode = null;

    /**
     * Default error options used for this object when error mode
     * is PEAR_ERROR_TRIGGER.
     *
     * @var     int
     * @access  private
     */
    var $_default_error_options = null;

    /**
     * Default error handler (callback) for this object, if error mode is
     * PEAR_ERROR_CALLBACK.
     *
     * @var     string
     * @access  private
     */
    var $_default_error_handler = '';

    /**
     * Which class to use for error objects.
     *
     * @var     string
     * @access  private
     */
    var $_error_class = 'PEAR_Error';

    /**
     * An array of expected errors.
     *
     * @var     array
     * @access  private
     */
    var $_expected_errors = array();

    // }}}

    // {{{ constructor

    /**
     * Constructor.  Registers this object in
     * $_PEAR_destructor_object_list for destructor emulation if a
     * destructor object exists.
     *
     * @param string $error_class  (optional) which class to use for
     *        error objects, defaults to PEAR_Error.
     * @access public
     * @return void
     */
    function PEAR($error_class = null)
    {
        $classname = strtolower(get_class($this));
        if ($this->_debug) {
            print "PEAR constructor called, class=$classname\n";
        }
        if ($error_class !== null) {
            $this->_error_class = $error_class;
        }
        while ($classname && strcasecmp($classname, "pear")) {
            $destructor = "_$classname";
            if (method_exists($this, $destructor)) {
                global $_PEAR_destructor_object_list;
                $_PEAR_destructor_object_list[] = &$this;
                static $registered = false;
                if (!$registered) {
                    register_shutdown_function("_PEAR_call_destructors");
                    $registered = true;
                }
                break;
            } else {
                $classname = get_parent_class($classname);
            }
        }
    }

    // }}}
    // {{{ destructor

    /**
     * Destructor (the emulated type of...).  Does nothing right now,
     * but is included for forward compatibility, so subclass
     * destructors should always call it.
     *
     * See the note in the class desciption about output from
     * destructors.
     *
     * @access public
     * @return void
     */
    function _PEAR() {
        if ($this->_debug) {
            printf("PEAR destructor called, class=%s\n", strtolower(get_class($this)));
        }
    }

    // }}}
    // {{{ getStaticProperty()

    /**
    * If you have a class that's mostly/entirely static, and you need static
    * properties, you can use this method to simulate them. Eg. in your method(s)
    * do this: $myVar = &PEAR::getStaticProperty('myclass', 'myVar');
    * You MUST use a reference, or they will not persist!
    *
    * @access public
    * @param  string $class  The calling classname, to prevent clashes
    * @param  string $var    The variable to retrieve.
    * @return mixed   A reference to the variable. If not set it will be
    *                 auto initialised to NULL.
    */
    function &getStaticProperty($class, $var)
    {
        static $properties;
        return $properties[$class][$var];
    }

    // }}}
    // {{{ registerShutdownFunc()

    /**
    * Use this function to register a shutdown method for static
    * classes.
    *
    * @access public
    * @param  mixed $func  The function name (or array of class/method) to call
    * @param  mixed $args  The arguments to pass to the function
    * @return void
    */
    function registerShutdownFunc($func, $args = array())
    {
        $GLOBALS['_PEAR_shutdown_funcs'][] = array($func, $args);
    }

    // }}}
    // {{{ isError()

    /**
     * Tell whether a value is a PEAR error.
     *
     * @param   mixed $data   the value to test
     * @param   int   $code   if $data is an error object, return true
     *                        only if $code is a string and
     *                        $obj->getMessage() == $code or
     *                        $code is an integer and $obj->getCode() == $code
     * @access  public
     * @return  bool    true if parameter is an error
     */
    function isError($data, $code = null)
    {
        if (is_a($data, 'PEAR_Error')) {
            if (is_null($code)) {
                return true;
            } elseif (is_string($code)) {
                return $data->getMessage() == $code;
            } else {
                return $data->getCode() == $code;
            }
        }
        return false;
    }

    // }}}
    // {{{ setErrorHandling()

    /**
     * Sets how errors generated by this object should be handled.
     * Can be invoked both in objects and statically.  If called
     * statically, setErrorHandling sets the default behaviour for all
     * PEAR objects.  If called in an object, setErrorHandling sets
     * the default behaviour for that object.
     *
     * @param int $mode
     *        One of PEAR_ERROR_RETURN, PEAR_ERROR_PRINT,
     *        PEAR_ERROR_TRIGGER, PEAR_ERROR_DIE,
     *        PEAR_ERROR_CALLBACK or PEAR_ERROR_EXCEPTION.
     *
     * @param mixed $options
     *        When $mode is PEAR_ERROR_TRIGGER, this is the error level (one
     *        of E_USER_NOTICE, E_USER_WARNING or E_USER_ERROR).
     *
     *        When $mode is PEAR_ERROR_CALLBACK, this parameter is expected
     *        to be the callback function or method.  A callback
     *        function is a string with the name of the function, a
     *        callback method is an array of two elements: the element
     *        at index 0 is the object, and the element at index 1 is
     *        the name of the method to call in the object.
     *
     *        When $mode is PEAR_ERROR_PRINT or PEAR_ERROR_DIE, this is
     *        a printf format string used when printing the error
     *        message.
     *
     * @access public
     * @return void
     * @see PEAR_ERROR_RETURN
     * @see PEAR_ERROR_PRINT
     * @see PEAR_ERROR_TRIGGER
     * @see PEAR_ERROR_DIE
     * @see PEAR_ERROR_CALLBACK
     * @see PEAR_ERROR_EXCEPTION
     *
     * @since PHP 4.0.5
     */

    function setErrorHandling($mode = null, $options = null)
    {
        if (isset($this) && is_a($this, 'PEAR')) {
            $setmode     = &$this->_default_error_mode;
            $setoptions  = &$this->_default_error_options;
        } else {
            $setmode     = &$GLOBALS['_PEAR_default_error_mode'];
            $setoptions  = &$GLOBALS['_PEAR_default_error_options'];
        }

        switch ($mode) {
            case PEAR_ERROR_EXCEPTION:
            case PEAR_ERROR_RETURN:
            case PEAR_ERROR_PRINT:
            case PEAR_ERROR_TRIGGER:
            case PEAR_ERROR_DIE:
            case null:
                $setmode = $mode;
                $setoptions = $options;
                break;

            case PEAR_ERROR_CALLBACK:
                $setmode = $mode;
                // class/object method callback
                if (is_callable($options)) {
                    $setoptions = $options;
                } else {
                    trigger_error("invalid error callback", E_USER_WARNING);
                }
                break;

            default:
                trigger_error("invalid error mode", E_USER_WARNING);
                break;
        }
    }

    // }}}
    // {{{ expectError()

    /**
     * This method is used to tell which errors you expect to get.
     * Expected errors are always returned with error mode
     * PEAR_ERROR_RETURN.  Expected error codes are stored in a stack,
     * and this method pushes a new element onto it.  The list of
     * expected errors are in effect until they are popped off the
     * stack with the popExpect() method.
     *
     * Note that this method can not be called statically
     *
     * @param mixed $code a single error code or an array of error codes to expect
     *
     * @return int     the new depth of the "expected errors" stack
     * @access public
     */
    function expectError($code = '*')
    {
        if (is_array($code)) {
            array_push($this->_expected_errors, $code);
        } else {
            array_push($this->_expected_errors, array($code));
        }
        return sizeof($this->_expected_errors);
    }

    // }}}
    // {{{ popExpect()

    /**
     * This method pops one element off the expected error codes
     * stack.
     *
     * @return array   the list of error codes that were popped
     */
    function popExpect()
    {
        return array_pop($this->_expected_errors);
    }

    // }}}
    // {{{ _checkDelExpect()

    /**
     * This method checks unsets an error code if available
     *
     * @param mixed error code
     * @return bool true if the error code was unset, false otherwise
     * @access private
     * @since PHP 4.3.0
     */
    function _checkDelExpect($error_code)
    {
        $deleted = false;

        foreach ($this->_expected_errors AS $key => $error_array) {
            if (in_array($error_code, $error_array)) {
                unset($this->_expected_errors[$key][array_search($error_code, $error_array)]);
                $deleted = true;
            }

            // clean up empty arrays
            if (0 == count($this->_expected_errors[$key])) {
                unset($this->_expected_errors[$key]);
            }
        }
        return $deleted;
    }

    // }}}
    // {{{ delExpect()

    /**
     * This method deletes all occurences of the specified element from
     * the expected error codes stack.
     *
     * @param  mixed $error_code error code that should be deleted
     * @return mixed list of error codes that were deleted or error
     * @access public
     * @since PHP 4.3.0
     */
    function delExpect($error_code)
    {
        $deleted = false;

        if ((is_array($error_code) && (0 != count($error_code)))) {
            // $error_code is a non-empty array here;
            // we walk through it trying to unset all
            // values
            foreach($error_code as $key => $error) {
                if ($this->_checkDelExpect($error)) {
                    $deleted =  true;
                } else {
                    $deleted = false;
                }
            }
            return $deleted ? true : PEAR::raiseError("The expected error you submitted does not exist"); // IMPROVE ME
        } elseif (!empty($error_code)) {
            // $error_code comes alone, trying to unset it
            if ($this->_checkDelExpect($error_code)) {
                return true;
            } else {
                return PEAR::raiseError("The expected error you submitted does not exist"); // IMPROVE ME
            }
        } else {
            // $error_code is empty
            return PEAR::raiseError("The expected error you submitted is empty"); // IMPROVE ME
        }
    }

    // }}}
    // {{{ raiseError()

    /**
     * This method is a wrapper that returns an instance of the
     * configured error class with this object's default error
     * handling applied.  If the $mode and $options parameters are not
     * specified, the object's defaults are used.
     *
     * @param mixed $message a text error message or a PEAR error object
     *
     * @param int $code      a numeric error code (it is up to your class
     *                  to define these if you want to use codes)
     *
     * @param int $mode      One of PEAR_ERROR_RETURN, PEAR_ERROR_PRINT,
     *                  PEAR_ERROR_TRIGGER, PEAR_ERROR_DIE,
     *                  PEAR_ERROR_CALLBACK, PEAR_ERROR_EXCEPTION.
     *
     * @param mixed $options If $mode is PEAR_ERROR_TRIGGER, this parameter
     *                  specifies the PHP-internal error level (one of
     *                  E_USER_NOTICE, E_USER_WARNING or E_USER_ERROR).
     *                  If $mode is PEAR_ERROR_CALLBACK, this
     *                  parameter specifies the callback function or
     *                  method.  In other error modes this parameter
     *                  is ignored.
     *
     * @param string $userinfo If you need to pass along for example debug
     *                  information, this parameter is meant for that.
     *
     * @param string $error_class The returned error object will be
     *                  instantiated from this class, if specified.
     *
     * @param bool $skipmsg If true, raiseError will only pass error codes,
     *                  the error message parameter will be dropped.
     *
     * @access public
     * @return object   a PEAR error object
     * @see PEAR::setErrorHandling
     * @since PHP 4.0.5
     */
    function raiseError($message = null,
                         $code = null,
                         $mode = null,
                         $options = null,
                         $userinfo = null,
                         $error_class = null,
                         $skipmsg = false)
    {
        // The error is yet a PEAR error object
        if (is_object($message)) {
            $code        = $message->getCode();
            $userinfo    = $message->getUserInfo();
            $error_class = $message->getType();
            $message->error_message_prefix = '';
            $message     = $message->getMessage();
        }

        if (isset($this) && isset($this->_expected_errors) && sizeof($this->_expected_errors) > 0 && sizeof($exp = end($this->_expected_errors))) {
            if ($exp[0] == "*" ||
                (is_int(reset($exp)) && in_array($code, $exp)) ||
                (is_string(reset($exp)) && in_array($message, $exp))) {
                $mode = PEAR_ERROR_RETURN;
            }
        }
        // No mode given, try global ones
        if ($mode === null) {
            // Class error handler
            if (isset($this) && isset($this->_default_error_mode)) {
                $mode    = $this->_default_error_mode;
                $options = $this->_default_error_options;
            // Global error handler
            } elseif (isset($GLOBALS['_PEAR_default_error_mode'])) {
                $mode    = $GLOBALS['_PEAR_default_error_mode'];
                $options = $GLOBALS['_PEAR_default_error_options'];
            }
        }

        if ($error_class !== null) {
            $ec = $error_class;
        } elseif (isset($this) && isset($this->_error_class)) {
            $ec = $this->_error_class;
        } else {
            $ec = 'PEAR_Error';
        }
        if ($skipmsg) {
            return new $ec($code, $mode, $options, $userinfo);
        } else {
            return new $ec($message, $code, $mode, $options, $userinfo);
        }
    }

    // }}}
    // {{{ throwError()

    /**
     * Simpler form of raiseError with fewer options.  In most cases
     * message, code and userinfo are enough.
     *
     * @param string $message
     *
     */
    function throwError($message = null,
                         $code = null,
                         $userinfo = null)
    {
        if (isset($this) && is_a($this, 'PEAR')) {
            return $this->raiseError($message, $code, null, null, $userinfo);
        } else {
            return PEAR::raiseError($message, $code, null, null, $userinfo);
        }
    }

    // }}}
    function staticPushErrorHandling($mode, $options = null)
    {
        $stack = &$GLOBALS['_PEAR_error_handler_stack'];
        $def_mode    = &$GLOBALS['_PEAR_default_error_mode'];
        $def_options = &$GLOBALS['_PEAR_default_error_options'];
        $stack[] = array($def_mode, $def_options);
        switch ($mode) {
            case PEAR_ERROR_EXCEPTION:
            case PEAR_ERROR_RETURN:
            case PEAR_ERROR_PRINT:
            case PEAR_ERROR_TRIGGER:
            case PEAR_ERROR_DIE:
            case null:
                $def_mode = $mode;
                $def_options = $options;
                break;

            case PEAR_ERROR_CALLBACK:
                $def_mode = $mode;
                // class/object method callback
                if (is_callable($options)) {
                    $def_options = $options;
                } else {
                    trigger_error("invalid error callback", E_USER_WARNING);
                }
                break;

            default:
                trigger_error("invalid error mode", E_USER_WARNING);
                break;
        }
        $stack[] = array($mode, $options);
        return true;
    }

    function staticPopErrorHandling()
    {
        $stack = &$GLOBALS['_PEAR_error_handler_stack'];
        $setmode     = &$GLOBALS['_PEAR_default_error_mode'];
        $setoptions  = &$GLOBALS['_PEAR_default_error_options'];
        array_pop($stack);
        list($mode, $options) = $stack[sizeof($stack) - 1];
        array_pop($stack);
        switch ($mode) {
            case PEAR_ERROR_EXCEPTION:
            case PEAR_ERROR_RETURN:
            case PEAR_ERROR_PRINT:
            case PEAR_ERROR_TRIGGER:
            case PEAR_ERROR_DIE:
            case null:
                $setmode = $mode;
                $setoptions = $options;
                break;

            case PEAR_ERROR_CALLBACK:
                $setmode = $mode;
                // class/object method callback
                if (is_callable($options)) {
                    $setoptions = $options;
                } else {
                    trigger_error("invalid error callback", E_USER_WARNING);
                }
                break;

            default:
                trigger_error("invalid error mode", E_USER_WARNING);
                break;
        }
        return true;
    }

    // {{{ pushErrorHandling()

    /**
     * Push a new error handler on top of the error handler options stack. With this
     * you can easily override the actual error handler for some code and restore
     * it later with popErrorHandling.
     *
     * @param mixed $mode (same as setErrorHandling)
     * @param mixed $options (same as setErrorHandling)
     *
     * @return bool Always true
     *
     * @see PEAR::setErrorHandling
     */
    function pushErrorHandling($mode, $options = null)
    {
        $stack = &$GLOBALS['_PEAR_error_handler_stack'];
        if (isset($this) && is_a($this, 'PEAR')) {
            $def_mode    = &$this->_default_error_mode;
            $def_options = &$this->_default_error_options;
        } else {
            $def_mode    = &$GLOBALS['_PEAR_default_error_mode'];
            $def_options = &$GLOBALS['_PEAR_default_error_options'];
        }
        $stack[] = array($def_mode, $def_options);

        if (isset($this) && is_a($this, 'PEAR')) {
            $this->setErrorHandling($mode, $options);
        } else {
            PEAR::setErrorHandling($mode, $options);
        }
        $stack[] = array($mode, $options);
        return true;
    }

    // }}}
    // {{{ popErrorHandling()

    /**
    * Pop the last error handler used
    *
    * @return bool Always true
    *
    * @see PEAR::pushErrorHandling
    */
    function popErrorHandling()
    {
        $stack = &$GLOBALS['_PEAR_error_handler_stack'];
        array_pop($stack);
        list($mode, $options) = $stack[sizeof($stack) - 1];
        array_pop($stack);
        if (isset($this) && is_a($this, 'PEAR')) {
            $this->setErrorHandling($mode, $options);
        } else {
            PEAR::setErrorHandling($mode, $options);
        }
        return true;
    }

    // }}}
    // {{{ loadExtension()

    /**
    * OS independant PHP extension load. Remember to take care
    * on the correct extension name for case sensitive OSes.
    *
    * @param string $ext The extension name
    * @return bool Success or not on the dl() call
    */
    function loadExtension($ext)
    {
        if (!extension_loaded($ext)) {
            // if either returns true dl() will produce a FATAL error, stop that
            if ((ini_get('enable_dl') != 1) || (ini_get('safe_mode') == 1)) {
                return false;
            }
            if (OS_WINDOWS) {
                $suffix = '.dll';
            } elseif (PHP_OS == 'HP-UX') {
                $suffix = '.sl';
            } elseif (PHP_OS == 'AIX') {
                $suffix = '.a';
            } elseif (PHP_OS == 'OSX') {
                $suffix = '.bundle';
            } else {
                $suffix = '.so';
            }
            return @dl('php_'.$ext.$suffix) || @dl($ext.$suffix);
        }
        return true;
    }

    // }}}
}

// {{{ _PEAR_call_destructors()

function _PEAR_call_destructors()
{
    global $_PEAR_destructor_object_list;
    if (is_array($_PEAR_destructor_object_list) &&
        sizeof($_PEAR_destructor_object_list))
    {
        reset($_PEAR_destructor_object_list);
        if (@PEAR::getStaticProperty('PEAR', 'destructlifo')) {
            $_PEAR_destructor_object_list = array_reverse($_PEAR_destructor_object_list);
        }
        while (list($k, $objref) = each($_PEAR_destructor_object_list)) {
            $classname = get_class($objref);
            while ($classname) {
                $destructor = "_$classname";
                if (method_exists($objref, $destructor)) {
                    $objref->$destructor();
                    break;
                } else {
                    $classname = get_parent_class($classname);
                }
            }
        }
        // Empty the object list to ensure that destructors are
        // not called more than once.
        $_PEAR_destructor_object_list = array();
    }

    // Now call the shutdown functions
    if (is_array($GLOBALS['_PEAR_shutdown_funcs']) AND !empty($GLOBALS['_PEAR_shutdown_funcs'])) {
        foreach ($GLOBALS['_PEAR_shutdown_funcs'] as $value) {
            call_user_func_array($value[0], $value[1]);
        }
    }
}

// }}}

class PEAR_Error
{
    // {{{ properties

    var $error_message_prefix = '';
    var $mode                 = PEAR_ERROR_RETURN;
    var $level                = E_USER_NOTICE;
    var $code                 = -1;
    var $message              = '';
    var $userinfo             = '';
    var $backtrace            = null;

    // }}}
    // {{{ constructor

    /**
     * PEAR_Error constructor
     *
     * @param string $message  message
     *
     * @param int $code     (optional) error code
     *
     * @param int $mode     (optional) error mode, one of: PEAR_ERROR_RETURN,
     * PEAR_ERROR_PRINT, PEAR_ERROR_DIE, PEAR_ERROR_TRIGGER,
     * PEAR_ERROR_CALLBACK or PEAR_ERROR_EXCEPTION
     *
     * @param mixed $options   (optional) error level, _OR_ in the case of
     * PEAR_ERROR_CALLBACK, the callback function or object/method
     * tuple.
     *
     * @param string $userinfo (optional) additional user/debug info
     *
     * @access public
     *
     */
    function PEAR_Error($message = 'unknown error', $code = null,
                        $mode = null, $options = null, $userinfo = null)
    {
        if ($mode === null) {
            $mode = PEAR_ERROR_RETURN;
        }
        $this->message   = $message;
        $this->code      = $code;
        $this->mode      = $mode;
        $this->userinfo  = $userinfo;
        if (function_exists("debug_backtrace")) {
            if (@!PEAR::getStaticProperty('PEAR_Error', 'skiptrace')) {
                $this->backtrace = debug_backtrace();
            }
        }
        if ($mode & PEAR_ERROR_CALLBACK) {
            $this->level = E_USER_NOTICE;
            $this->callback = $options;
        } else {
            if ($options === null) {
                $options = E_USER_NOTICE;
            }
            $this->level = $options;
            $this->callback = null;
        }
        if ($this->mode & PEAR_ERROR_PRINT) {
            if (is_null($options) || is_int($options)) {
                $format = "%s";
            } else {
                $format = $options;
            }
            printf($format, $this->getMessage());
        }
        if ($this->mode & PEAR_ERROR_TRIGGER) {
            trigger_error($this->getMessage(), $this->level);
        }
        if ($this->mode & PEAR_ERROR_DIE) {
            $msg = $this->getMessage();
            if (is_null($options) || is_int($options)) {
                $format = "%s";
                if (substr($msg, -1) != "\n") {
                    $msg .= "\n";
                }
            } else {
                $format = $options;
            }
            die(sprintf($format, $msg));
        }
        if ($this->mode & PEAR_ERROR_CALLBACK) {
            if (is_callable($this->callback)) {
                call_user_func($this->callback, $this);
            }
        }
        if ($this->mode & PEAR_ERROR_EXCEPTION) {
            trigger_error("PEAR_ERROR_EXCEPTION is obsolete, use class PEAR_ErrorStack for exceptions", E_USER_WARNING);
            eval('$e = new Exception($this->message, $this->code);$e->PEAR_Error = $this;throw($e);');
        }
    }

    // }}}
    // {{{ getMode()

    /**
     * Get the error mode from an error object.
     *
     * @return int error mode
     * @access public
     */
    function getMode() {
        return $this->mode;
    }

    // }}}
    // {{{ getCallback()

    /**
     * Get the callback function/method from an error object.
     *
     * @return mixed callback function or object/method array
     * @access public
     */
    function getCallback() {
        return $this->callback;
    }

    // }}}
    // {{{ getMessage()


    /**
     * Get the error message from an error object.
     *
     * @return  string  full error message
     * @access public
     */
    function getMessage()
    {
        return ($this->error_message_prefix . $this->message);
    }


    // }}}
    // {{{ getCode()

    /**
     * Get error code from an error object
     *
     * @return int error code
     * @access public
     */
     function getCode()
     {
        return $this->code;
     }

    // }}}
    // {{{ getType()

    /**
     * Get the name of this error/exception.
     *
     * @return string error/exception name (type)
     * @access public
     */
    function getType()
    {
        return get_class($this);
    }

    // }}}
    // {{{ getUserInfo()

    /**
     * Get additional user-supplied information.
     *
     * @return string user-supplied information
     * @access public
     */
    function getUserInfo()
    {
        return $this->userinfo;
    }

    // }}}
    // {{{ getDebugInfo()

    /**
     * Get additional debug information supplied by the application.
     *
     * @return string debug information
     * @access public
     */
    function getDebugInfo()
    {
        return $this->getUserInfo();
    }

    // }}}
    // {{{ getBacktrace()

    /**
     * Get the call backtrace from where the error was generated.
     * Supported with PHP 4.3.0 or newer.
     *
     * @param int $frame (optional) what frame to fetch
     * @return array Backtrace, or NULL if not available.
     * @access public
     */
    function getBacktrace($frame = null)
    {
        if ($frame === null) {
            return $this->backtrace;
        }
        return $this->backtrace[$frame];
    }

    // }}}
    // {{{ addUserInfo()

    function addUserInfo($info)
    {
        if (empty($this->userinfo)) {
            $this->userinfo = $info;
        } else {
            $this->userinfo .= " ** $info";
        }
    }

    // }}}
    // {{{ toString()

    /**
     * Make a string representation of this object.
     *
     * @return string a string with an object summary
     * @access public
     */
    function toString() {
        $modes = array();
        $levels = array(E_USER_NOTICE  => 'notice',
                        E_USER_WARNING => 'warning',
                        E_USER_ERROR   => 'error');
        if ($this->mode & PEAR_ERROR_CALLBACK) {
            if (is_array($this->callback)) {
                $callback = (is_object($this->callback[0]) ?
                    strtolower(get_class($this->callback[0])) :
                    $this->callback[0]) . '::' .
                    $this->callback[1];
            } else {
                $callback = $this->callback;
            }
            return sprintf('[%s: message="%s" code=%d mode=callback '.
                           'callback=%s prefix="%s" info="%s"]',
                           strtolower(get_class($this)), $this->message, $this->code,
                           $callback, $this->error_message_prefix,
                           $this->userinfo);
        }
        if ($this->mode & PEAR_ERROR_PRINT) {
            $modes[] = 'print';
        }
        if ($this->mode & PEAR_ERROR_TRIGGER) {
            $modes[] = 'trigger';
        }
        if ($this->mode & PEAR_ERROR_DIE) {
            $modes[] = 'die';
        }
        if ($this->mode & PEAR_ERROR_RETURN) {
            $modes[] = 'return';
        }
        return sprintf('[%s: message="%s" code=%d mode=%s level=%s '.
                       'prefix="%s" info="%s"]',
                       strtolower(get_class($this)), $this->message, $this->code,
                       implode("|", $modes), $levels[$this->level],
                       $this->error_message_prefix,
                       $this->userinfo);
    }

    // }}}
}

/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?><?php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002  Richard Heyes                                     |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard@phpguru.org>                           |
// +-----------------------------------------------------------------------+

/**
*
*  Raw mime encoding class
*
* What is it?
*   This class enables you to manipulate and build
*   a mime email from the ground up.
*
* Why use this instead of mime.php?
*   mime.php is a userfriendly api to this class for
*   people who aren't interested in the internals of
*   mime mail. This class however allows full control
*   over the email.
*
* Eg.
*
* // Since multipart/mixed has no real body, (the body is
* // the subpart), we set the body argument to blank.
*
* $params['content_type'] = 'multipart/mixed';
* $email = new Mail_mimePart('', $params);
*
* // Here we add a text part to the multipart we have
* // already. Assume $body contains plain text.
*
* $params['content_type'] = 'text/plain';
* $params['encoding']     = '7bit';
* $text = $email->addSubPart($body, $params);
*
* // Now add an attachment. Assume $attach is
* the contents of the attachment
*
* $params['content_type'] = 'application/zip';
* $params['encoding']     = 'base64';
* $params['disposition']  = 'attachment';
* $params['dfilename']    = 'example.zip';
* $attach =& $email->addSubPart($body, $params);
*
* // Now build the email. Note that the encode
* // function returns an associative array containing two
* // elements, body and headers. You will need to add extra
* // headers, (eg. Mime-Version) before sending.
*
* $email = $message->encode();
* $email['headers'][] = 'Mime-Version: 1.0';
*
*
* Further examples are available at http://www.phpguru.org
*
* TODO:
*  - Set encode() to return the $obj->encoded if encode()
*    has already been run. Unless a flag is passed to specifically
*    re-build the message.
*
* @author  Richard Heyes <richard@phpguru.org>
* @version $Revision: 1.10 $
* @package Mail
*/

class Mail_mimePart {

   /**
    * The encoding type of this part
    * @var string
    */
    var $_encoding;

   /**
    * An array of subparts
    * @var array
    */
    var $_subparts;

   /**
    * The output of this part after being built
    * @var string
    */
    var $_encoded;

   /**
    * Headers for this part
    * @var array
    */
    var $_headers;

   /**
    * The body of this part (not encoded)
    * @var string
    */
    var $_body;

    /**
     * Constructor.
     *
     * Sets up the object.
     *
     * @param $body   - The body of the mime part if any.
     * @param $params - An associative array of parameters:
     *                  content_type - The content type for this part eg multipart/mixed
     *                  encoding     - The encoding to use, 7bit, 8bit, base64, or quoted-printable
     *                  cid          - Content ID to apply
     *                  disposition  - Content disposition, inline or attachment
     *                  dfilename    - Optional filename parameter for content disposition
     *                  description  - Content description
     *                  charset      - Character set to use
     * @access public
     */
    function Mail_mimePart($body = '', $params = array())
    {
        if (!defined('MAIL_MIMEPART_CRLF')) {
            define('MAIL_MIMEPART_CRLF', defined('MAIL_MIME_CRLF') ? MAIL_MIME_CRLF : "\r\n", TRUE);
        }

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'content_type':
                    $headers['Content-Type'] = $value . (isset($charset) ? '; charset="' . $charset . '"' : '');
                    break;

                case 'encoding':
                    $this->_encoding = $value;
                    $headers['Content-Transfer-Encoding'] = $value;
                    break;

                case 'cid':
                    $headers['Content-ID'] = '<' . $value . '>';
                    break;

                case 'disposition':
                    $headers['Content-Disposition'] = $value . (isset($dfilename) ? '; filename="' . $dfilename . '"' : '');
                    break;

                case 'dfilename':
                    if (isset($headers['Content-Disposition'])) {
                        $headers['Content-Disposition'] .= '; filename="' . $value . '"';
                    } else {
                        $dfilename = $value;
                    }
                    break;

                case 'description':
                    $headers['Content-Description'] = $value;
                    break;

                case 'charset':
                    if (isset($headers['Content-Type'])) {
                        $headers['Content-Type'] .= '; charset="' . $value . '"';
                    } else {
                        $charset = $value;
                    }
                    break;
            }
        }

        // Default content-type
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/plain';
        }

        //Default encoding
        if (!isset($this->_encoding)) {
            $this->_encoding = '7bit';
        }

        // Assign stuff to member variables
        $this->_encoded  = array();
        $this->_headers  = $headers;
        $this->_body     = $body;
    }

    /**
     * encode()
     *
     * Encodes and returns the email. Also stores
     * it in the encoded member variable
     *
     * @return An associative array containing two elements,
     *         body and headers. The headers element is itself
     *         an indexed array.
     * @access public
     */
    function encode()
    {
        $encoded =& $this->_encoded;

        if (!empty($this->_subparts)) {
            srand((double)microtime()*1000000);
            $boundary = '=_' . md5(uniqid(rand()) . microtime());
            $this->_headers['Content-Type'] .= ';' . MAIL_MIMEPART_CRLF . "\t" . 'boundary="' . $boundary . '"';

            // Add body parts to $subparts
            for ($i = 0; $i < count($this->_subparts); $i++) {
                $headers = array();
                $tmp = $this->_subparts[$i]->encode();
                foreach ($tmp['headers'] as $key => $value) {
                    $headers[] = $key . ': ' . $value;
                }
                $subparts[] = implode(MAIL_MIMEPART_CRLF, $headers) . MAIL_MIMEPART_CRLF . MAIL_MIMEPART_CRLF . $tmp['body'];
            }

            $encoded['body'] = '--' . $boundary . MAIL_MIMEPART_CRLF .
                               implode('--' . $boundary . MAIL_MIMEPART_CRLF, $subparts) .
                               '--' . $boundary.'--' . MAIL_MIMEPART_CRLF;

        } else {
            $encoded['body'] = $this->_getEncodedData($this->_body, $this->_encoding) . MAIL_MIMEPART_CRLF;
        }

        // Add headers to $encoded
        $encoded['headers'] =& $this->_headers;

        return $encoded;
    }

    /**
     * &addSubPart()
     *
     * Adds a subpart to current mime part and returns
     * a reference to it
     *
     * @param $body   The body of the subpart, if any.
     * @param $params The parameters for the subpart, same
     *                as the $params argument for constructor.
     * @return A reference to the part you just added. It is
     *         crucial if using multipart/* in your subparts that
     *         you use =& in your script when calling this function,
     *         otherwise you will not be able to add further subparts.
     * @access public
     */
    function &addSubPart($body, $params)
    {
        $this->_subparts[] = new Mail_mimePart($body, $params);
        return $this->_subparts[count($this->_subparts) - 1];
    }

    /**
     * _getEncodedData()
     *
     * Returns encoded data based upon encoding passed to it
     *
     * @param $data     The data to encode.
     * @param $encoding The encoding type to use, 7bit, base64,
     *                  or quoted-printable.
     * @access private
     */
    function _getEncodedData($data, $encoding)
    {
        switch ($encoding) {
            case '8bit':
            case '7bit':
                return $data;
                break;

            case 'quoted-printable':
                return $this->_quotedPrintableEncode($data);
                break;

            case 'base64':
                return rtrim(chunk_split(base64_encode($data), 76, MAIL_MIMEPART_CRLF));
                break;

            default:
                return $data;
        }
    }

    /**
     * quoteadPrintableEncode()
     *
     * Encodes data to quoted-printable standard.
     *
     * @param $input    The data to encode
     * @param $line_max Optional max line length. Should
     *                  not be more than 76 chars
     *
     * @access private
     */
    function _quotedPrintableEncode($input , $line_max = 76)
    {
        $lines  = preg_split("/\r?\n/", $input);
        $eol    = MAIL_MIMEPART_CRLF;
        $escape = '=';
        $output = '';

        while(list(, $line) = each($lines)){

            $linlen     = strlen($line);
            $newline = '';

            for ($i = 0; $i < $linlen; $i++) {
                $char = substr($line, $i, 1);
                $dec  = ord($char);

                if (($dec == 32) AND ($i == ($linlen - 1))){    // convert space at eol only
                    $char = '=20';

                } elseif($dec == 9) {
                    ; // Do nothing if a tab.
                } elseif(($dec == 61) OR ($dec < 32 ) OR ($dec > 126)) {
                    $char = $escape . strtoupper(sprintf('%02s', dechex($dec)));
                }

                if ((strlen($newline) + strlen($char)) >= $line_max) {        // MAIL_MIMEPART_CRLF is not counted
                    $output  .= $newline . $escape . $eol;                    // soft line break; " =\r\n" is okay
                    $newline  = '';
                }
                $newline .= $char;
            } // end of for
            $output .= $newline . $eol;
        }
        $output = substr($output, 0, -1 * strlen($eol)); // Don't want last crlf
        return $output;
    }
} // End of class
?><?Php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002  Richard Heyes                                     |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard@phpguru.org>                           |
// +-----------------------------------------------------------------------+


/**
*  +----------------------------- IMPORTANT ------------------------------+
*  | Usage of this class compared to native php extensions such as        |
*  | mailparse or imap, is slow and may be feature deficient. If available|
*  | you are STRONGLY recommended to use the php extensions.              |
*  +----------------------------------------------------------------------+
*
* Mime Decoding class
*
* This class will parse a raw mime email and return
* the structure. Returned structure is similar to
* that returned by imap_fetchstructure().
*
* USAGE: (assume $input is your raw email)
*
* $decode = new Mail_mimeDecode($input, "\r\n");
* $structure = $decode->decode();
* print_r($structure);
*
* Or statically:
*
* $params['input'] = $input;
* $structure = Mail_mimeDecode::decode($params);
* print_r($structure);
*
* TODO:
*  - Implement further content types, eg. multipart/parallel,
*    perhaps even message/partial.
*
* @author  Richard Heyes <richard@phpguru.org>
* @version $Revision: 1.34 $
* @package Mail
*/

class Mail_mimeDecode extends PEAR
{

    /**
     * The raw email to decode
     * @var    string
     */
    var $_input;

    /**
     * The header part of the input
     * @var    string
     */
    var $_header;

    /**
     * The body part of the input
     * @var    string
     */
    var $_body;

    /**
     * If an error occurs, this is used to store the message
     * @var    string
     */
    var $_error;

    /**
     * Flag to determine whether to include bodies in the
     * returned object.
     * @var    boolean
     */
    var $_include_bodies;

    /**
     * Flag to determine whether to decode bodies
     * @var    boolean
     */
    var $_decode_bodies;

    /**
     * Flag to determine whether to decode headers
     * @var    boolean
     */
    var $_decode_headers;

    /**
    * If invoked from a class, $this will be set. This has problematic
    * connotations for calling decode() statically. Hence this variable
    * is used to determine if we are indeed being called statically or
    * via an object.
    */
    var $mailMimeDecode;

    /**
     * Constructor.
     *
     * Sets up the object, initialise the variables, and splits and
     * stores the header and body of the input.
     *
     * @param string The input to decode
     * @access public
     */
    function Mail_mimeDecode($input)
    {
        list($header, $body)   = $this->_splitBodyHeader($input);

        $this->_input          = $input;
        $this->_header         = $header;
        $this->_body           = $body;
        $this->_decode_bodies  = false;
        $this->_include_bodies = true;
        
        $this->mailMimeDecode  = true;
    }

    /**
     * Begins the decoding process. If called statically
     * it will create an object and call the decode() method
     * of it.
     *
     * @param array An array of various parameters that determine
     *              various things:
     *              include_bodies - Whether to include the body in the returned
     *                               object.
     *              decode_bodies  - Whether to decode the bodies
     *                               of the parts. (Transfer encoding)
     *              decode_headers - Whether to decode headers
     *              input          - If called statically, this will be treated
     *                               as the input
     * @return object Decoded results
     * @access public
     */
    function decode($params = null)
    {

        // Have we been called statically? If so, create an object and pass details to that.
        if (!isset($this->mailMimeDecode) AND isset($params['input'])) {

            $obj = new Mail_mimeDecode($params['input']);
            $structure = $obj->decode($params);

        // Called statically but no input
        } elseif (!isset($this->mailMimeDecode)) {
            return PEAR::raiseError('Called statically and no input given');

        // Called via an object
        } else {
            $this->_include_bodies = isset($params['include_bodies'])  ? $params['include_bodies']  : false;
            $this->_decode_bodies  = isset($params['decode_bodies'])   ? $params['decode_bodies']   : false;
            $this->_decode_headers = isset($params['decode_headers'])  ? $params['decode_headers']  : false;

            $structure = $this->_decode($this->_header, $this->_body);
            if ($structure === false) {
                $structure = $this->raiseError($this->_error);
            }
        }

        return $structure;
    }

    /**
     * Performs the decoding. Decodes the body string passed to it
     * If it finds certain content-types it will call itself in a
     * recursive fashion
     *
     * @param string Header section
     * @param string Body section
     * @return object Results of decoding process
     * @access private
     */
    function _decode($headers, $body, $default_ctype = 'text/plain')
    {
        $return = new stdClass;
        $headers = $this->_parseHeaders($headers);

        foreach ($headers as $value) {
            if (isset($return->headers[strtolower($value['name'])]) AND !is_array($return->headers[strtolower($value['name'])])) {
                $return->headers[strtolower($value['name'])]   = array($return->headers[strtolower($value['name'])]);
                $return->headers[strtolower($value['name'])][] = $value['value'];

            } elseif (isset($return->headers[strtolower($value['name'])])) {
                $return->headers[strtolower($value['name'])][] = $value['value'];

            } else {
                $return->headers[strtolower($value['name'])] = $value['value'];
            }
        }

        reset($headers);
        while (list($key, $value) = each($headers)) {
            $headers[$key]['name'] = strtolower($headers[$key]['name']);
            switch ($headers[$key]['name']) {

                case 'content-type':
                    $content_type = $this->_parseHeaderValue($headers[$key]['value']);

                    if (preg_match('/([0-9a-z+.-]+)\/([0-9a-z+.-]+)/i', $content_type['value'], $regs)) {
                        $return->ctype_primary   = $regs[1];
                        $return->ctype_secondary = $regs[2];
                    }

                    if (isset($content_type['other'])) {
                        while (list($p_name, $p_value) = each($content_type['other'])) {
                            $return->ctype_parameters[$p_name] = $p_value;
                        }
                    }
                    break;

                case 'content-disposition';
                    $content_disposition = $this->_parseHeaderValue($headers[$key]['value']);
                    $return->disposition   = $content_disposition['value'];
                    if (isset($content_disposition['other'])) {
                        while (list($p_name, $p_value) = each($content_disposition['other'])) {
                            $return->d_parameters[$p_name] = $p_value;
                        }
                    }
                    break;

                case 'content-transfer-encoding':
                    $content_transfer_encoding = $this->_parseHeaderValue($headers[$key]['value']);
                    break;
            }
        }

        if (isset($content_type)) {
            switch (strtolower($content_type['value'])) {
                case 'text/plain':
                    $encoding = isset($content_transfer_encoding) ? $content_transfer_encoding['value'] : '7bit';
                    $this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $encoding) : $body) : null;
                    break;

                case 'text/html':
                    $encoding = isset($content_transfer_encoding) ? $content_transfer_encoding['value'] : '7bit';
                    $this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $encoding) : $body) : null;
                    break;

                case 'multipart/parallel':
                case 'multipart/report': // RFC1892
                case 'multipart/signed': // PGP
                case 'multipart/digest':
                case 'multipart/alternative':
                case 'multipart/related':
                case 'multipart/mixed':
                    if(!isset($content_type['other']['boundary'])){
                        $this->_error = 'No boundary found for ' . $content_type['value'] . ' part';
                        return false;
                    }

                    $default_ctype = (strtolower($content_type['value']) === 'multipart/digest') ? 'message/rfc822' : 'text/plain';

                    $parts = $this->_boundarySplit($body, $content_type['other']['boundary']);
                    for ($i = 0; $i < count($parts); $i++) {
                        list($part_header, $part_body) = $this->_splitBodyHeader($parts[$i]);
                        $part = $this->_decode($part_header, $part_body, $default_ctype);
                        if($part === false)
                            $part = $this->raiseError($this->_error);
                        $return->parts[] = $part;
                    }
                    break;

                case 'message/rfc822':
                    $obj = &new Mail_mimeDecode($body);
                    $return->parts[] = $obj->decode(array('include_bodies' => $this->_include_bodies));
                    unset($obj);
                    break;

                default:
                    if(!isset($content_transfer_encoding['value']))
                        $content_transfer_encoding['value'] = '7bit';
                    $this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $content_transfer_encoding['value']) : $body) : null;
                    break;
            }

        } else {
            $ctype = explode('/', $default_ctype);
            $return->ctype_primary   = $ctype[0];
            $return->ctype_secondary = $ctype[1];
            $this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body) : $body) : null;
        }

        return $return;
    }

    /**
     * Given the output of the above function, this will return an
     * array of references to the parts, indexed by mime number.
     *
     * @param  object $structure   The structure to go through
     * @param  string $mime_number Internal use only.
     * @return array               Mime numbers
     */
    function &getMimeNumbers(&$structure, $no_refs = false, $mime_number = '', $prepend = '')
    {
        $return = array();
        if (!empty($structure->parts)) {
            if ($mime_number != '') {
                $structure->mime_id = $prepend . $mime_number;
                $return[$prepend . $mime_number] = &$structure;
            }
            for ($i = 0; $i < count($structure->parts); $i++) {

            
                if (!empty($structure->headers['content-type']) AND substr(strtolower($structure->headers['content-type']), 0, 8) == 'message/') {
                    $prepend      = $prepend . $mime_number . '.';
                    $_mime_number = '';
                } else {
                    $_mime_number = ($mime_number == '' ? $i + 1 : sprintf('%s.%s', $mime_number, $i + 1));
                }

                $arr = &Mail_mimeDecode::getMimeNumbers($structure->parts[$i], $no_refs, $_mime_number, $prepend);
                foreach ($arr as $key => $val) {
                    $no_refs ? $return[$key] = '' : $return[$key] = &$arr[$key];
                }
            }
        } else {
            if ($mime_number == '') {
                $mime_number = '1';
            }
            $structure->mime_id = $prepend . $mime_number;
            $no_refs ? $return[$prepend . $mime_number] = '' : $return[$prepend . $mime_number] = &$structure;
        }
        
        return $return;
    }

    /**
     * Given a string containing a header and body
     * section, this function will split them (at the first
     * blank line) and return them.
     *
     * @param string Input to split apart
     * @return array Contains header and body section
     * @access private
     */
    function _splitBodyHeader($input)
    {
        if (preg_match("/^(.*?)\r?\n\r?\n(.*)/s", $input, $match)) {
            return array($match[1], $match[2]);
        }
        $this->_error = 'Could not split header and body';
        return false;
    }

    /**
     * Parse headers given in $input and return
     * as assoc array.
     *
     * @param string Headers to parse
     * @return array Contains parsed headers
     * @access private
     */
    function _parseHeaders($input)
    {

        if ($input !== '') {
            // Unfold the input
            $input   = preg_replace("/\r?\n/", "\r\n", $input);
            $input   = preg_replace("/\r\n(\t| )+/", ' ', $input);
            $headers = explode("\r\n", trim($input));

            foreach ($headers as $value) {
                $hdr_name = substr($value, 0, $pos = strpos($value, ':'));
                $hdr_value = substr($value, $pos+1);
                if($hdr_value[0] == ' ')
                    $hdr_value = substr($hdr_value, 1);

                $return[] = array(
                                  'name'  => $hdr_name,
                                  'value' => $this->_decode_headers ? $this->_decodeHeader($hdr_value) : $hdr_value
                                 );
            }
        } else {
            $return = array();
        }

        return $return;
    }

    /**
     * Function to parse a header value,
     * extract first part, and any secondary
     * parts (after ;) This function is not as
     * robust as it could be. Eg. header comments
     * in the wrong place will probably break it.
     *
     * @param string Header value to parse
     * @return array Contains parsed result
     * @access private
     */
    function _parseHeaderValue($input)
    {

        if (($pos = strpos($input, ';')) !== false) {

            $return['value'] = trim(substr($input, 0, $pos));
            $input = trim(substr($input, $pos+1));

            if (strlen($input) > 0) {

                // This splits on a semi-colon, if there's no preceeding backslash
                // Can't handle if it's in double quotes however. (Of course anyone
                // sending that needs a good slap).
                $parameters = preg_split('/\s*(?<!\\\\);\s*/i', $input);

                for ($i = 0; $i < count($parameters); $i++) {
                    $param_name  = substr($parameters[$i], 0, $pos = strpos($parameters[$i], '='));
                    $param_value = substr($parameters[$i], $pos + 1);
                    if ($param_value[0] == '"') {
                        $param_value = substr($param_value, 1, -1);
                    }
                    $return['other'][$param_name] = $param_value;
                    $return['other'][strtolower($param_name)] = $param_value;
                }
            }
        } else {
            $return['value'] = trim($input);
        }

        return $return;
    }

    /**
     * This function splits the input based
     * on the given boundary
     *
     * @param string Input to parse
     * @return array Contains array of resulting mime parts
     * @access private
     */
    function _boundarySplit($input, $boundary)
    {
        $tmp = explode('--'.$boundary, $input);

        for ($i=1; $i<count($tmp)-1; $i++) {
            $parts[] = $tmp[$i];
        }

        return $parts;
    }

    /**
     * Given a header, this function will decode it
     * according to RFC2047. Probably not *exactly*
     * conformant, but it does pass all the given
     * examples (in RFC2047).
     *
     * @param string Input header value to decode
     * @return string Decoded header value
     * @access private
     */
    function _decodeHeader($input)
    {
        // Remove white space between encoded-words
        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);

        // For each encoded-word...
        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {

            $encoded  = $matches[1];
            $charset  = $matches[2];
            $encoding = $matches[3];
            $text     = $matches[4];

            switch (strtolower($encoding)) {
                case 'b':
                    $text = base64_decode($text);
                    break;

                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach($matches[1] as $value)
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    break;
            }

            $input = str_replace($encoded, $text, $input);
        }

        return $input;
    }

    /**
     * Given a body string and an encoding type,
     * this function will decode and return it.
     *
     * @param  string Input body to decode
     * @param  string Encoding type to use.
     * @return string Decoded body
     * @access private
     */
    function _decodeBody($input, $encoding = '7bit')
    {
        switch ($encoding) {
            case '7bit':
                return $input;
                break;

            case 'quoted-printable':
                return $this->_quotedPrintableDecode($input);
                break;

            case 'base64':
                return base64_decode($input);
                break;

            default:
                return $input;
        }
    }

    /**
     * Given a quoted-printable string, this
     * function will decode and return it.
     *
     * @param  string Input body to decode
     * @return string Decoded body
     * @access private
     */
    function _quotedPrintableDecode($input)
    {
        // Remove soft line breaks
        $input = preg_replace("/=\r?\n/", '', $input);

        // Replace encoded characters
		$input = preg_replace('/=([a-f0-9]{2})/ie', "chr(hexdec('\\1'))", $input);

        return $input;
    }

    /**
     * Checks the input for uuencoded files and returns
     * an array of them. Can be called statically, eg:
     *
     * $files =& Mail_mimeDecode::uudecode($some_text);
     *
     * It will check for the begin 666 ... end syntax
     * however and won't just blindly decode whatever you
     * pass it.
     *
     * @param  string Input body to look for attahcments in
     * @return array  Decoded bodies, filenames and permissions
     * @access public
     * @author Unknown
     */
    function &uudecode($input)
    {
        // Find all uuencoded sections
        preg_match_all("/begin ([0-7]{3}) (.+)\r?\n(.+)\r?\nend/Us", $input, $matches);

        for ($j = 0; $j < count($matches[3]); $j++) {

            $str      = $matches[3][$j];
            $filename = $matches[2][$j];
            $fileperm = $matches[1][$j];

            $file = '';
            $str = preg_split("/\r?\n/", trim($str));
            $strlen = count($str);

            for ($i = 0; $i < $strlen; $i++) {
                $pos = 1;
                $d = 0;
                $len=(int)(((ord(substr($str[$i],0,1)) -32) - ' ') & 077);

                while (($d + 3 <= $len) AND ($pos + 4 <= strlen($str[$i]))) {
                    $c0 = (ord(substr($str[$i],$pos,1)) ^ 0x20);
                    $c1 = (ord(substr($str[$i],$pos+1,1)) ^ 0x20);
                    $c2 = (ord(substr($str[$i],$pos+2,1)) ^ 0x20);
                    $c3 = (ord(substr($str[$i],$pos+3,1)) ^ 0x20);
                    $file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));

                    $file .= chr(((($c1 - ' ') & 077) << 4) | ((($c2 - ' ') & 077) >> 2));

                    $file .= chr(((($c2 - ' ') & 077) << 6) |  (($c3 - ' ') & 077));

                    $pos += 4;
                    $d += 3;
                }

                if (($d + 2 <= $len) && ($pos + 3 <= strlen($str[$i]))) {
                    $c0 = (ord(substr($str[$i],$pos,1)) ^ 0x20);
                    $c1 = (ord(substr($str[$i],$pos+1,1)) ^ 0x20);
                    $c2 = (ord(substr($str[$i],$pos+2,1)) ^ 0x20);
                    $file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));

                    $file .= chr(((($c1 - ' ') & 077) << 4) | ((($c2 - ' ') & 077) >> 2));

                    $pos += 3;
                    $d += 2;
                }

                if (($d + 1 <= $len) && ($pos + 2 <= strlen($str[$i]))) {
                    $c0 = (ord(substr($str[$i],$pos,1)) ^ 0x20);
                    $c1 = (ord(substr($str[$i],$pos+1,1)) ^ 0x20);
                    $file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));

                }
            }
            $files[] = array('filename' => $filename, 'fileperm' => $fileperm, 'filedata' => $file);
        }

        return $files;
    }

    /**
     * getSendArray() returns the arguments required for Mail::send()
     * used to build the arguments for a mail::send() call 
     *
     * Usage:
     * $mailtext = Full email (for example generated by a template)
     * $decoder = new Mail_mimeDecode($mailtext);
     * $parts =  $decoder->getSendArray();
     * if (!PEAR::isError($parts) {
     *     list($recipents,$headers,$body) = $parts;
     *     $mail = Mail::factory('smtp');
     *     $mail->send($recipents,$headers,$body);
     * } else {
     *     echo $parts->message;
     * }
     * @return mixed   array of recipeint, headers,body or Pear_Error
     * @access public
     * @author Alan Knowles <alan@akbkhome.com>
     */
    function getSendArray()
    {
        // prevent warning if this is not set
        $this->_decode_headers = FALSE;
        $headerlist =$this->_parseHeaders($this->_header);
        $to = "";
        if (!$headerlist) {
            return $this->raiseError("Message did not contain headers");
        }
        foreach($headerlist as $item) {
            $header[$item['name']] = $item['value'];
            switch (strtolower($item['name'])) {
                case "to":
                case "cc":
                case "bcc":
                    $to = ",".$item['value'];
                default:
                   break;
            }
        }
        if ($to == "") {
            return $this->raiseError("Message did not contain any recipents");
        }
        $to = substr($to,1);
        return array($to,$header,$this->_body);
    } 









    /**
     * Returns a xml copy of the output of
     * Mail_mimeDecode::decode. Pass the output in as the
     * argument. This function can be called statically. Eg:
     *
     * $output = $obj->decode();
     * $xml    = Mail_mimeDecode::getXML($output);
     *
     * The DTD used for this should have been in the package. Or
     * alternatively you can get it from cvs, or here:
     * http://www.phpguru.org/xmail/xmail.dtd.
     *
     * @param  object Input to convert to xml. This should be the
     *                output of the Mail_mimeDecode::decode function
     * @return string XML version of input
     * @access public
     */
    function getXML($input)
    {
        $crlf    =  "\r\n";
        $output  = '<?xml version=\'1.0\'?>' . $crlf .
                   '<!DOCTYPE email SYSTEM "http://www.phpguru.org/xmail/xmail.dtd">' . $crlf .
                   '<email>' . $crlf .
                   Mail_mimeDecode::_getXML($input) .
                   '</email>';

        return $output;
    }

    /**
     * Function that does the actual conversion to xml. Does a single
     * mimepart at a time.
     *
     * @param  object  Input to convert to xml. This is a mimepart object.
     *                 It may or may not contain subparts.
     * @param  integer Number of tabs to indent
     * @return string  XML version of input
     * @access private
     */
    function _getXML($input, $indent = 1)
    {
        $htab    =  "\t";
        $crlf    =  "\r\n";
        $output  =  '';
        $headers = @(array)$input->headers;

        foreach ($headers as $hdr_name => $hdr_value) {

            // Multiple headers with this name
            if (is_array($headers[$hdr_name])) {
                for ($i = 0; $i < count($hdr_value); $i++) {
                    $output .= Mail_mimeDecode::_getXML_helper($hdr_name, $hdr_value[$i], $indent);
                }

            // Only one header of this sort
            } else {
                $output .= Mail_mimeDecode::_getXML_helper($hdr_name, $hdr_value, $indent);
            }
        }

        if (!empty($input->parts)) {
            for ($i = 0; $i < count($input->parts); $i++) {
                $output .= $crlf . str_repeat($htab, $indent) . '<mimepart>' . $crlf .
                           Mail_mimeDecode::_getXML($input->parts[$i], $indent+1) .
                           str_repeat($htab, $indent) . '</mimepart>' . $crlf;
            }
        } elseif (isset($input->body)) {
            $output .= $crlf . str_repeat($htab, $indent) . '<body><![CDATA[' .
                       $input->body . ']]></body>' . $crlf;
        }

        return $output;
    }

    /**
     * Helper function to _getXML(). Returns xml of a header.
     *
     * @param  string  Name of header
     * @param  string  Value of header
     * @param  integer Number of tabs to indent
     * @return string  XML version of input
     * @access private
     */
    function _getXML_helper($hdr_name, $hdr_value, $indent)
    {
        $htab   = "\t";
        $crlf   = "\r\n";
        $return = '';

        $new_hdr_value = ($hdr_name != 'received') ? Mail_mimeDecode::_parseHeaderValue($hdr_value) : array('value' => $hdr_value);
        $new_hdr_name  = str_replace(' ', '-', ucwords(str_replace('-', ' ', $hdr_name)));

        // Sort out any parameters
        if (!empty($new_hdr_value['other'])) {
            foreach ($new_hdr_value['other'] as $paramname => $paramvalue) {
                $params[] = str_repeat($htab, $indent) . $htab . '<parameter>' . $crlf .
                            str_repeat($htab, $indent) . $htab . $htab . '<paramname>' . htmlspecialchars($paramname) . '</paramname>' . $crlf .
                            str_repeat($htab, $indent) . $htab . $htab . '<paramvalue>' . htmlspecialchars($paramvalue) . '</paramvalue>' . $crlf .
                            str_repeat($htab, $indent) . $htab . '</parameter>' . $crlf;
            }

            $params = implode('', $params);
        } else {
            $params = '';
        }

        $return = str_repeat($htab, $indent) . '<header>' . $crlf .
                  str_repeat($htab, $indent) . $htab . '<headername>' . htmlspecialchars($new_hdr_name) . '</headername>' . $crlf .
                  str_repeat($htab, $indent) . $htab . '<headervalue>' . htmlspecialchars($new_hdr_value['value']) . '</headervalue>' . $crlf .
                  $params .
                  str_repeat($htab, $indent) . '</header>' . $crlf;

        return $return;
    }

} // End of class
?><?php
// +-----------------------------------------------------------------------+
// | Copyright (c) 2002  Richard Heyes                                     |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// | o The names of the authors may not be used to endorse or promote      |
// |   products derived from this software without specific prior written  |
// |   permission.                                                         |
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Richard Heyes <richard@phpguru.org>                           |
// |         Tomas V.V.Cox <cox@idecnet.com> (port to PEAR)                |
// +-----------------------------------------------------------------------+
//
// $Id: mime.php,v 1.23 2002/07/27 14:37:53 richard Exp $



/**
* Mime mail composer class. Can handle: text and html bodies, embedded html
* images and attachments.
* Documentation and examples of this class are avaible here:
* http://pear.php.net/manual/
*
* @notes This class is based on HTML Mime Mail class from
*   Richard Heyes <richard@phpguru.org> which was based also
*   in the mime_mail.class by Tobias Ratschiller <tobias@dnet.it> and
*   Sascha Schumann <sascha@schumann.cx>.
*
* @author Richard Heyes <richard.heyes@heyes-computing.net>
* @author Tomas V.V.Cox <cox@idecnet.com>
* @package Mail
* @access public
*/
class Mail_mime
{
    /**
    * Contains the plain text part of the email
    * @var string
    */
    var $_txtbody;
    /**
    * Contains the html part of the email
    * @var string
    */
    var $_htmlbody;
    /**
    * contains the mime encoded text
    * @var string
    */
    var $_mime;
    /**
    * contains the multipart content
    * @var string
    */
    var $_multipart;
    /**
    * list of the attached images
    * @var array
    */
    var $_html_images = array();
    /**
    * list of the attachements
    * @var array
    */
    var $_parts = array();
    /**
    * Build parameters
    * @var array
    */
    var $_build_params = array();
    /**
    * Headers for the mail
    * @var array
    */
    var $_headers = array();


    /*
    * Constructor function
    *
    * @access public
    */
    function Mail_mime($crlf = "\r\n")
    {
        if (!defined('MAIL_MIME_CRLF')) {
            define('MAIL_MIME_CRLF', $crlf, true);
        }

        $this->_boundary = '=_' . md5(uniqid(time()));

        $this->_build_params = array(
                                     'text_encoding' => '7bit',
                                     'html_encoding' => 'quoted-printable',
                                     '7bit_wrap'     => 998,
                                     'html_charset'  => 'ISO-8859-1',
                                     'text_charset'  => 'ISO-8859-1',
                                     'head_charset'  => 'ISO-8859-1'
                                    );
    }

    /*
    * Accessor function to set the body text. Body text is used if
    * it's not an html mail being sent or else is used to fill the
    * text/plain part that emails clients who don't support
    * html should show.
    *
    * @param string $data Either a string or the file name with the
    *        contents
    * @param bool $isfile If true the first param should be trated
    *        as a file name, else as a string (default)
    * @param bool If true the text or file is appended to the
    *        existing body, else the old body is overwritten
    * @return mixed true on success or PEAR_Error object
    * @access public
    */
    function setTXTBody($data, $isfile = false, $append = false)
    {
        if (!$isfile) {
            if (!$append) {
                $this->_txtbody = $data;
            } else {
                $this->_txtbody .= $data;
            }
        } else {
            $cont = $this->_file2str($data);
            if (PEAR::isError($cont)) {
                return $cont;
            }
            if (!$append) {
                 $this->_txtbody = $cont;
             } else {
                 $this->_txtbody .= $cont;
             }
        }
        return true;
    }

    /*
    * Adds a html part to the mail
    *
    * @param string $data Either a string or the file name with the
    *        contents
    * @param bool $isfile If true the first param should be trated
    *        as a file name, else as a string (default)
    * @return mixed true on success or PEAR_Error object
    * @access public
    */
    function setHTMLBody($data, $isfile = false)
    {
        if (!$isfile) {
            $this->_htmlbody = $data;
        } else {
            $cont = $this->_file2str($data);
            if (PEAR::isError($cont)) {
                return $cont;
            }
            $this->_htmlbody = $cont;
        }

        return true;
    }

    /*
    * Adds an image to the list of embedded images.
    *
    * @param string $file The image file name OR image data itself
    * @param string $c_type The content type
    * @param string $name The filename of the image. Only use if $file is the image data
    * @param bool $isfilename Whether $file is a filename or not. Defaults to true
    * @return mixed true on success or PEAR_Error object
    * @access public
    */
    function addHTMLImage($file, $c_type='application/octet-stream', $name = '', $isfilename = true)
    {
        $filedata = ($isfilename === true) ? $this->_file2str($file) : $file;
        $filename = ($isfilename === true) ? basename($file) : basename($name);
        if (PEAR::isError($filedata)) {
            return $filedata;
        }
        $this->_html_images[] = array(
                                      'body'   => $filedata,
                                      'name'   => $filename,
                                      'c_type' => $c_type,
                                      'cid'    => md5(uniqid(time()))
                                     );
        return true;
    }

    /*
    * Adds a file to the list of attachments.
    *
    * @param string $file The file name of the file to attach OR the file data itself
    * @param string $c_type The content type
    * @param string $name The filename of the attachment. Only use if $file is the file data
    * @param bool $isFilename Whether $file is a filename or not. Defaults to true
    * @return mixed true on success or PEAR_Error object
    * @access public
    */
    function addAttachment($file, $c_type='application/octet-stream', $name = '', $isfilename = true, $encoding = 'base64')
    {
        $filedata = ($isfilename === true) ? $this->_file2str($file) : $file;
        if ($isfilename === true) {
            // Force the name the user supplied, otherwise use $file
            $filename = (!empty($name)) ? $name : $file;
        } else {
            $filename = $name;
        }
        if (empty($filename)) {
            return PEAR::raiseError('The supplied filename for the attachment can\'t be empty');
        }
        $filename = basename($filename);
        if (PEAR::isError($filedata)) {
            return $filedata;
        }

        $this->_parts[] = array(
                                'body'     => $filedata,
                                'name'     => $filename,
                                'c_type'   => $c_type,
                                'encoding' => $encoding
                               );
        return true;
    }

    /*
    * Returns the contents of the given file name as string
    * @param string $file_name
    * @return string
    * @acces private
    */
    function & _file2str($file_name)
    {
        if (!is_readable($file_name)) {
            return PEAR::raiseError('File is not readable ' . $file_name);
        }
        if (!$fd = fopen($file_name, 'rb')) {
            return PEAR::raiseError('Could not open ' . $file_name);
        }
        $cont = fread($fd, filesize($file_name));
        fclose($fd);
        return $cont;
    }

    /*
    * Adds a text subpart to the mimePart object and
    * returns it during the build process.
    *
    * @param mixed    The object to add the part to, or
    *                 null if a new object is to be created.
    * @param string   The text to add.
    * @return object  The text mimePart object
    * @access private
    */
    function &_addTextPart(&$obj, $text){

        $params['content_type'] = 'text/plain';
        $params['encoding']     = $this->_build_params['text_encoding'];
        $params['charset']      = $this->_build_params['text_charset'];
        if (is_object($obj)) {
            return $obj->addSubpart($text, $params);
        } else {
            $mail_mpart=new Mail_mimePart($text, $params);
            return $mail_mpart;
        }
    }

    /*
    * Adds a html subpart to the mimePart object and
    * returns it during the build process.
    *
    * @param mixed    The object to add the part to, or
    *                 null if a new object is to be created.
    * @return object  The html mimePart object
    * @access private
    */
    function &_addHtmlPart(&$obj){

        $params['content_type'] = 'text/html';
        $params['encoding']     = $this->_build_params['html_encoding'];
        $params['charset']      = $this->_build_params['html_charset'];
        if (is_object($obj)) {
            return $obj->addSubpart($this->_htmlbody, $params);
        } else {
            return new Mail_mimePart($this->_htmlbody, $params);
        }
    }

    /*
    * Creates a new mimePart object, using multipart/mixed as
    * the initial content-type and returns it during the
    * build process.
    *
    * @return object  The multipart/mixed mimePart object
    * @access private
    */
    function &_addMixedPart(){

        $params['content_type'] = 'multipart/mixed';
        return new Mail_mimePart('', $params);
    }

    /*
    * Adds a multipart/alternative part to a mimePart
    * object, (or creates one), and returns it  during
    * the build process.
    *
    * @param mixed    The object to add the part to, or
    *                 null if a new object is to be created.
    * @return object  The multipart/mixed mimePart object
    * @access private
    */
    function &_addAlternativePart(&$obj){

        $params['content_type'] = 'multipart/alternative';
        if (is_object($obj)) {
            return $obj->addSubpart('', $params);
        } else {
            $result = new Mail_mimePart('', $params);
            return $result;
        }
    }

    /*
    * Adds a multipart/related part to a mimePart
    * object, (or creates one), and returns it  during
    * the build process.
    *
    * @param mixed    The object to add the part to, or
    *                 null if a new object is to be created.
    * @return object  The multipart/mixed mimePart object
    * @access private
    */
    function &_addRelatedPart(&$obj){

        $params['content_type'] = 'multipart/related';
        if (is_object($obj)) {
            return $obj->addSubpart('', $params);
        } else {
            return new Mail_mimePart('', $params);
        }
    }

    /*
    * Adds an html image subpart to a mimePart object
    * and returns it during the build process.
    *
    * @param  object  The mimePart to add the image to
    * @param  array   The image information
    * @return object  The image mimePart object
    * @access private
    */
    function &_addHtmlImagePart(&$obj, $value){

        $params['content_type'] = $value['c_type'];
        $params['encoding']     = 'base64';
        $params['disposition']  = 'inline';
        $params['dfilename']    = $value['name'];
        $params['cid']          = $value['cid'];
        $obj->addSubpart($value['body'], $params);
    }

    /*
    * Adds an attachment subpart to a mimePart object
    * and returns it during the build process.
    *
    * @param  object  The mimePart to add the image to
    * @param  array   The attachment information
    * @return object  The image mimePart object
    * @access private
    */
    function &_addAttachmentPart(&$obj, $value){

        $params['content_type'] = $value['c_type'];
        $params['encoding']     = $value['encoding'];
        $params['disposition']  = 'attachment';
        $params['dfilename']    = $value['name'];
        $obj->addSubpart($value['body'], $params);
    }

    /*
    * Builds the multipart message from the list ($this->_parts) and
    * returns the mime content.
    *
    * @param  array  Build parameters that change the way the email
    *                is built. Should be associative. Can contain:
    *                text_encoding  -  What encoding to use for plain text
    *                                  Default is 7bit
    *                html_encoding  -  What encoding to use for html
    *                                  Default is quoted-printable
    *                7bit_wrap      -  Number of characters before text is
    *                                  wrapped in 7bit encoding
    *                                  Default is 998
    *                html_charset   -  The character set to use for html.
    *                                  Default is iso-8859-1
    *                text_charset   -  The character set to use for text.
    *                                  Default is iso-8859-1
    *                head_charset   -  The character set to use for headers.
    *                                  Default is iso-8859-1
    * @return string The mime content
    * @access public
    */
    function &get($build_params = null)
    {
        if (isset($build_params)) {
            while (list($key, $value) = each($build_params)) {
                $this->_build_params[$key] = $value;
            }
        }

        if (!empty($this->_html_images) AND isset($this->_htmlbody)) {
            foreach ($this->_html_images as $value) {
                $this->_htmlbody = str_replace($value['name'], 'cid:'.$value['cid'], $this->_htmlbody);
            }
        }

        $null        = null;
        $attachments = !empty($this->_parts)                ? TRUE : FALSE;
        $html_images = !empty($this->_html_images)          ? TRUE : FALSE;
        $html        = !empty($this->_htmlbody)             ? TRUE : FALSE;
        $text        = (!$html AND !empty($this->_txtbody)) ? TRUE : FALSE;

        switch (TRUE) {
            case $text AND !$attachments:
                $message =& $this->_addTextPart($null, $this->_txtbody);
                break;

            case !$text AND !$html AND $attachments:
                $message =& $this->_addMixedPart();

                for ($i = 0; $i < count($this->_parts); $i++) {
                    $this->_addAttachmentPart($message, $this->_parts[$i]);
                }
                break;

            case $text AND $attachments:
                $message =& $this->_addMixedPart();
                $this->_addTextPart($message, $this->_txtbody);

                for ($i = 0; $i < count($this->_parts); $i++) {
                    $this->_addAttachmentPart($message, $this->_parts[$i]);
                }
                break;

            case $html AND !$attachments AND !$html_images:
                if (isset($this->_txtbody)) {
                    $message =& $this->_addAlternativePart($null);
                       $this->_addTextPart($message, $this->_txtbody);
                    $this->_addHtmlPart($message);

                } else {
                    $message =& $this->_addHtmlPart($null);
                }
                break;

            case $html AND !$attachments AND $html_images:
                if (isset($this->_txtbody)) {
                    $message =& $this->_addAlternativePart($null);
                    $this->_addTextPart($message, $this->_txtbody);
                    $related =& $this->_addRelatedPart($message);
                } else {
                    $message =& $this->_addRelatedPart($null);
                    $related =& $message;
                }
                $this->_addHtmlPart($related);
                for ($i = 0; $i < count($this->_html_images); $i++) {
                    $this->_addHtmlImagePart($related, $this->_html_images[$i]);
                }
                break;

            case $html AND $attachments AND !$html_images:
                $message =& $this->_addMixedPart();
                if (isset($this->_txtbody)) {
                    $alt =& $this->_addAlternativePart($message);
                    $this->_addTextPart($alt, $this->_txtbody);
                    $this->_addHtmlPart($alt);
                } else {
                    $this->_addHtmlPart($message);
                }
                for ($i = 0; $i < count($this->_parts); $i++) {
                    $this->_addAttachmentPart($message, $this->_parts[$i]);
                }
                break;

            case $html AND $attachments AND $html_images:
                $message =& $this->_addMixedPart();
                if (isset($this->_txtbody)) {
                    $alt =& $this->_addAlternativePart($message);
                    $this->_addTextPart($alt, $this->_txtbody);
                    $rel =& $this->_addRelatedPart($alt);
                } else {
                    $rel =& $this->_addRelatedPart($message);
                }
                $this->_addHtmlPart($rel);
                for ($i = 0; $i < count($this->_html_images); $i++) {
                    $this->_addHtmlImagePart($rel, $this->_html_images[$i]);
                }
                for ($i = 0; $i < count($this->_parts); $i++) {
                    $this->_addAttachmentPart($message, $this->_parts[$i]);
                }
                break;

        }

        if (isset($message)) {
            $output = $message->encode();
            $this->_headers = array_merge($this->_headers, $output['headers']);

            return $output['body'];

        } else {
        	$false = false;
            return $false;
        }
    }

    /*
    * Returns an array with the headers needed to prepend to the email
    * (MIME-Version and Content-Type). Format of argument is:
    * $array['header-name'] = 'header-value';
    *
    * @param  array $xtra_headers Assoc array with any extra headers. Optional.
    * @return array Assoc array with the mime headers
    * @access public
    */
    function &headers($xtra_headers = null)
    {
        // Content-Type header should already be present,
        // So just add mime version header
        $headers['MIME-Version'] = '1.0';
        if (isset($xtra_headers)) {
            $headers = array_merge($headers, $xtra_headers);
        }
        $this->_headers = array_merge($headers, $this->_headers);
        $result = $this->_encodeHeaders($this->_headers);
        return $result;
    }

    /**
    * Get the text version of the headers
    * (usefull if you want to use the PHP mail() function)
    *
    * @param  array $xtra_headers Assoc array with any extra headers. Optional.
    * @return string Plain text headers
    * @access public
    */
    function txtHeaders($xtra_headers = null)
    {
        $headers = $this->headers($xtra_headers);
        $ret = '';
        foreach ($headers as $key => $val) {
            $ret .= "$key: $val" . MAIL_MIME_CRLF;
        }
        return $ret;
    }

    /**
    * Sets the Subject header
    * 
    * @param  string $subject String to set the subject to
    * access  public
    */
    function setSubject($subject)
    {
        $this->_headers['Subject'] = $subject;
    }

    /**
    * Set an email to the From (the sender) header
    *
    * @param string $email The email direction to add
    * @access public
    */
    function setFrom($email)
    {
        $this->_headers['From'] = $email;
    }

    /**
    * Add an email to the Cc (carbon copy) header
    * (multiple calls to this method is allowed)
    *
    * @param string $email The email direction to add
    * @access public
    */
    function addCc($email)
    {
        if (isset($this->_headers['Cc'])) {
            $this->_headers['Cc'] .= ", $email";
        } else {
            $this->_headers['Cc'] = $email;
        }
    }

    /**
    * Add an email to the Bcc (blank carbon copy) header
    * (multiple calls to this method is allowed)
    *
    * @param string $email The email direction to add
    * @access public
    */
    function addBcc($email)
    {
        if (isset($this->_headers['Bcc'])) {
            $this->_headers['Bcc'] .= ", $email";
        } else {
            $this->_headers['Bcc'] = $email;
        }
    }
    
    /**
    * Encodes a header as per RFC2047
    *
    * @param  string  $input The header data to encode
    * @return string         Encoded data
    * @access private
    */
    function _encodeHeaders($input)
    {
        foreach ($input as $hdr_name => $hdr_value) {
            preg_match_all('/(\w*[\x80-\xFF]+\w*)/', $hdr_value, $matches);
            foreach ($matches[1] as $value) {
                $replacement = preg_replace('/([\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
                $hdr_value = str_replace($value, '=?' . $this->_build_params['head_charset'] . '?Q?' . $replacement . '?=', $hdr_value);
            }
            $input[$hdr_name] = $hdr_value;
        }
        
        return $input;
    }

} // End of class
?><?php
/**

send_multipart_mail($from, $to, $subject, $text_html, $attaches=array(), $hdrs=array());

Envía un mensaje texto o html o texto/html opcionalmente con attaches

$text_html:
    si es STRING se toma como el TEXTO del mensaje de texto
    si es un ARRAY con solo 1 parametro se toma como el HTML de un mensaje sólo HTML.
    si es un ARRAY con solo 2 parametros se toman como el HTML y TEXTO de un mensaje HTML/TEXTO , si el TEXTO (2do parámetro está vacío) se convierte el HTML en TEXTO.

$attaches:
    es un array de elementos que:
        si es STRING se toma como un path completo a un archivo que se va a attachear.
        si es ARRAY con solo 1 parametro se toma como el contenido de una imágen para el HTML.
        si es un ARRAY con solo 2 parametros se tomas como el contenido de un archivo y su nombre.

Si existe el archivo '/tmp/send_multipart_mail.log', el código completo del mensaje va a ser logueado en ese archivo.

si está definida la constante 'SEND_MULTIPART_MAIL_TESTING' NO SE ENVÍAN LOS MENSAJES!
si está definida la constante 'SEND_MULTIPART_MAIL_LOG', se registra cada mensaje enviado. (cuidado! no dejar en un site en producción)

**/

/**
* Cargo PEAR MIME tocado para que esté todo en un solo dir.
**/

function send_multipart_mail_chars2text($html) {
        $html = preg_replace('/\&nbsp\;?/i'," ",$html);
        $html = preg_replace('/\&gt\;?/i'," ",$html);
        $html = preg_replace('/\&lt\;?/i'," ",$html);
        $html = preg_replace('/\&aacute\;?/i'," ",$html);
        $html = preg_replace('/\&eacute\;?/i'," ",$html);
        $html = preg_replace('/\&iacute\;?/i'," ",$html);
        $html = preg_replace('/\&oacute\;?/i'," ",$html);
        $html = preg_replace('/\&uacute\;?/i'," ",$html);
        return $html;
}

function        send_multipart_mail_html2text( $html ) {
    $html = send_multipart_mail_chars2text($html);
    $html = preg_replace("/\n/iU"," ",$html);
    $html = preg_replace("/\<\?[^\>]*\>/iU","",$html);
    $html = preg_replace("/\<o\:[^\>]*\>/iU","",$html);
    $html = preg_replace("/\<\/o\:[^\>]*\>/iU","",$html);
        $html = preg_replace("/\<p[^\>]*\>/iU","\n",$html);
        $html = preg_replace("/\<\/p.*\>/iU","",$html);
        $html = preg_replace("/\<br.*\>/iU","\n",$html);
        $html = strip_tags($html);
        return $html;
}
function    send_multipart_mail($from, $to, $subject, $text_html, $attaches=array(), $hdrs=array() ) {
    $html = null; $text = null;
    if(is_array($text_html)) {
        if(count($text_html)>1) {
            $html = $text_html[0];
            $text = $text_html[1];
            if(empty($text))
                $text = send_multipart_mail_html2text($text_html[0]);
        }else{
            $html = $text_html[0];
        }
    }else{
        $text = $text_html;
    }
    if( !defined('MAIL_MIMEPART_CRLF') )
        define('MAIL_MIMEPART_CRLF', "\n", TRUE);

    $mime = new Mail_mime();

    if($html!==null)
        $mime->setHTMLBody($html);
    if($text!==null)
        $mime->setTXTBody($text);
    foreach($attaches as $attach) {
        if(is_array($attach)) {
            if(count($attach)>1) {
                $mime->addAttachment( $attach[0], 'application/octet-stream', $attach[1], false);
            }else{
                $mime->addHTMLImage( $attach[0], 'application/octet-stream', 'image-'.md5($attach[0]), false);
            }
        }else{
            $mime->addAttachment( $attach );
        }
    }

    $hdrs['From'] = $from;
    $hdrs['Subject'] = $subject;
    $body = $mime->get();
    $hdrs = $mime->headers($hdrs);

    if(defined('SEND_MULTIPART_MAIL_LOG')) {
        error_log("\n-#-------------------------------------------------------------------------#-\n",3,SEND_MULTIPART_MAIL_LOG);
        error_log(date('Y-m-d H:i:s').": send_multipart_mail($from, $to, $subject, (body), ...);\n",3,SEND_MULTIPART_MAIL_LOG);
        $str_hdrs='';
        foreach($hdrs as $k=>$v) $str_hdrs .= "$k: ".trim($v).MAIL_MIMEPART_CRLF;
        @error_log($str_hdrs,3,SEND_MULTIPART_MAIL_LOG);
        @error_log("BODY:\n".$body.$extra_body,3,SEND_MULTIPART_MAIL_LOG);
        // DEBUG MAIL
        $extra_body = "\n---------------------------------------------------------\n";
        $extra_body .= "_GET=".var_export($_GET,true);
        $extra_body .= "_POST=".var_export($_POST,true);
        $extra_body .= "_SERVER=".var_export($_SERVER,true);
        error_log("\n-#-------------------------------------------------------------------------#-\n",3,SEND_MULTIPART_MAIL_LOG);
    }

    if(!defined('SEND_MULTIPART_MAIL_TESTING')) {
        if(defined('SEND_MULTIPART_MAIL_LOG')) { error_log("SENT...\n",3,SEND_MULTIPART_MAIL_LOG); }
        $str_hdrs='';
        foreach($hdrs as $k=>$v) $str_hdrs .= "$k: ".trim($v).MAIL_MIMEPART_CRLF;
        mail($to, $subject, $body, $str_hdrs);

        // DEBUG MAIL
        $extra_body = "\n---------------------------------------------------------\n";
        $extra_body .= "_GET=".var_export($_GET,true);
        $extra_body .= "_POST=".var_export($_POST,true);
        $extra_body .= "_SERVER=".var_export($_SERVER,true);
        if(defined('SEND_MULTIPART_MAIL_CC')) {
            mail(SEND_MULTIPART_MAIL_CC, "SMM: ".$subject, $body."\n\n####### $str_hdrs -f$from\n$extra_body", $str_hdrs);
        }
    }else{
        if(defined('SEND_MULTIPART_MAIL_LOG')) { error_log("SENT...\n",3,SEND_MULTIPART_MAIL_LOG); }
        $str_hdrs='';
        foreach($hdrs as $k=>$v) $str_hdrs .= "$k: ".trim($v).MAIL_MIMEPART_CRLF;
        mail('esteban@mondaytech.com', $subject, $body, $str_hdrs);
        mail('mcalvo@calcopietro.com', $subject, $body, $str_hdrs);

        // DEBUG MAIL
        $extra_body = "\n---------------------------------------------------------\n";
        $extra_body .= "_GET=".var_export($_GET,true);
        $extra_body .= "_POST=".var_export($_POST,true);
        $extra_body .= "_SERVER=".var_export($_SERVER,true);
        if(defined('SEND_MULTIPART_MAIL_CC')) {
            mail(SEND_MULTIPART_MAIL_CC, "SMM: ".$subject, $body."\n\n####### $str_hdrs -f$from\n$extra_body", $str_hdrs);
        }
    }
}

?>
