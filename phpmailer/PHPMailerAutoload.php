<?php
/**
 * PHPMailer SPL autoloader.
 * PHP Version 5.6+
 * @package PHPMailer
 * @link https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 * @author Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author Brent R. Matzelle (original founder)
 * @copyright 2012 - 2024 Marcus Bointon
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * PHPMailer SPL autoloader.
 * @param string $classname The name of the class to load
 * @return bool Whether the class was loaded
 */
function PHPMailerAutoload($classname)
{
    // Ensure we're only autoloading PHPMailer classes
    if (strpos($classname, 'PHPMailer') === false) {
        return false;
    }

    // Use __DIR__ for PHP 5.3+ compatibility
    $filename = __DIR__ . DIRECTORY_SEPARATOR . 'class.' . strtolower($classname) . '.php';
    
    if (is_readable($filename)) {
        require_once $filename;
        return true;
    }
    
    return false;
}

// Ensure spl_autoload_register is used
if (function_exists('spl_autoload_register')) {
    // Use anonymous function for modern PHP versions
    spl_autoload_register(function($class) {
        return PHPMailerAutoload($class);
    }, true, true);
} else {
    // Fallback for extremely old PHP versions (not recommended)
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'class.phpmailer.php';
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'class.smtp.php';
    
    $pop3_file = __DIR__ . DIRECTORY_SEPARATOR . 'class.pop3.php';
    if (file_exists($pop3_file)) {
        require_once $pop3_file;
    }
}