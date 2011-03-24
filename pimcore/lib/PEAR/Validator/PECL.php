<?php
/**
 * Channel Validator for the pecl.php.net channel
 *
 * PHP 4 and PHP 5
 *
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2006 The PHP Group
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    CVS: $Id: PECL.php,v 1.10 2009/02/24 23:39:19 dufuz Exp $
 * @link       http://pear.php.net/package/PEAR
 * @since      File available since Release 1.4.0a5
 */
/**
 * This is the parent class for all validators
 */
require_once 'PEAR/Validate.php';
/**
 * Channel Validator for the pecl.php.net channel
 * @category   pear
 * @package    PEAR
 * @author     Greg Beaver <cellog@php.net>
 * @copyright  1997-2009 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.8.1
 * @link       http://pear.php.net/package/PEAR
 * @since      Class available since Release 1.4.0a5
 */
class PEAR_Validator_PECL extends PEAR_Validate
{
    function validateVersion()
    {
        if ($this->_state == PEAR_VALIDATE_PACKAGING) {
            $version = $this->_packagexml->getVersion();
            $versioncomponents = explode('.', $version);
            $last = array_pop($versioncomponents);
            if (substr($last, 1, 2) == 'rc') {
                $this->_addFailure('version', 'Release Candidate versions must have ' .
                'upper-case RC, not lower-case rc');
                return false;
            }
        }
        return true;
    }

    function validatePackageName()
    {
        $ret = parent::validatePackageName();
        if ($this->_packagexml->getPackageType() == 'extsrc' ||
              $this->_packagexml->getPackageType() == 'zendextsrc') {
            if (strtolower($this->_packagexml->getPackage()) !=
                  strtolower($this->_packagexml->getProvidesExtension())) {
                $this->_addWarning('providesextension', 'package name "' .
                    $this->_packagexml->getPackage() . '" is different from extension name "' .
                    $this->_packagexml->getProvidesExtension() . '"');
            }
        }
        return $ret;
    }
}
?>