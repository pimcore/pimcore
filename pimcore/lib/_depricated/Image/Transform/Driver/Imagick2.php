<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * imagick PECL extension implementation for Image_Transform package
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Image
 * @package    Image_Transform
 * @subpackage Image_Transform_Driver_Imagick2
 * @author     Alan Knowles <alan@akbkhome.com>
 * @author     Peter Bowyer <peter@mapledesign.co.uk>
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2002-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Imagick2.php,v 1.10 2007/04/19 16:36:09 dufuz Exp $
 * @link       http://pear.php.net/package/Image_Transform
 */

require_once 'Image/Transform.php';

/**
 * imagick PECL extension implementation for Image_Transform package
 *
 * EXPERIMENTAL - please report bugs
 * Use the latest cvs version of imagick PECL
 *
 * @category   Image
 * @package    Image_Transform
 * @subpackage Image_Transform_Driver_Imagick2
 * @author     Alan Knowles <alan@akbkhome.com>
 * @author     Peter Bowyer <peter@mapledesign.co.uk>
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2002-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Image_Transform
 * @since      PHP 4.0
 */
class Image_Transform_Driver_Imagick2 extends Image_Transform
{
    /**
     * Handler of the imagick image ressource
     * @var array
     */
    var $imageHandle = null;

    /**
     * @see __construct()
     */
    function Image_Transform_Driver_Imagick2()
    {
        $this->__construct();
    } // End Image_Transform_Driver_Imagick2

    /**
     * @see http://www.imagemagick.org/www/formats.html
     */
    function __construct()
    {
        if (PEAR::loadExtension('imagick')) {
            include 'Image/Transform/Driver/Imagick/ImageTypes.php';
        } else {
            $this->isError(PEAR::raiseError('Couldn\'t find the imagick extension.',
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED));
        }
    }

    /**
     * Loads an image
     *
     * @param string $image filename
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function load($image)
    {
        if (!($this->imageHandle = imagick_readimage($image))) {
            $this->free();
            return $this->raiseError('Couldn\'t load image.',
                IMAGE_TRANSFORM_ERROR_IO);
        }
        if (imagick_iserror($this->imageHandle)) {
            return $this->raiseError('Couldn\'t load image.',
                IMAGE_TRANSFORM_ERROR_IO);
        }

        $this->image = $image;
        $result = $this->_get_image_details($image);
        if (PEAR::isError($result)) {
            return $result;
        }

        return true;
    } // End load

    /**
     * Resize Action
     *
     * @param int   $new_x   New width
     * @param int   $new_y   New height
     * @param mixed $options Optional parameters
     *
     * @return bool|PEAR_Error TRUE or PEAR_Error object on error
     * @access protected
     */
    function _resize($new_x, $new_y, $options = null)
    {
        if (!imagick_resize($this->imageHandle, $new_x, $new_y, IMAGICK_FILTER_UNKNOWN , 1)) {
            return $this->raiseError('Couldn\'t resize image.',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }

        $this->new_x = $new_x;
        $this->new_y = $new_y;
        return true;

    } // End resize

    /**
     * Rotates the current image
     * Note: color mask are currently not supported
     *
     * @param   int     Rotation angle in degree
     * @param   array   No options are currently supported
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function rotate($angle, $options = null)
    {
        if (($angle % 360) == 0) {
            return true;
        }
        if (!imagick_rotate($this->imageHandle, $angle)) {
            return $this->raiseError('Cannot create a new imagick image for the rotation.',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }

        $this->new_x = imagick_getwidth($this->imageHandle);
        $this->new_y = imagick_getheight($this->imageHandle);
        return true;

    } // End rotate

    /**
     * addText
     *
     * @param   array   options     Array contains options
     *                              array(
     *                                  'text'  The string to draw
     *                                  'x'     Horizontal position
     *                                  'y'     Vertical Position
     *                                  'Color' Font color
     *                                  'font'  Font to be used
     *                                  'size'  Size of the fonts in pixel
     *                                  'resize_first'  Tell if the image has to be resized
     *                                                  before drawing the text
     *                              )
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function addText($params)
    {
        static $default_params = array(
                                'text'          => 'This is a Text',
                                'x'             => 10,
                                'y'             => 20,
                                'size'          => 12,
                                'color'         => 'red',
                                'font'          => 'Helvetica',
                                'resize_first'  => false // Carry out the scaling of the image before annotation?
                                );
        $params = array_merge($default_params, $params);


        $params['color']= is_array($params['color'])?$this->colorarray2colorhex($params['color']):strtolower($params['color']);


        static $cmds = array(
            'setfillcolor' => 'color',
            'setfontsize'  => 'size',
            'setfontface'  => 'font'
        );
        imagick_begindraw($this->imageHandle ) ;

        foreach ($cmds as $cmd => $v) {
            if (!call_user_func('imagick_' . $cmd, $this->imageHandle, $parms[$v])) {
                return $this->raiseError("Problem with adding Text::{$v} = {$parms[$v]}",
                    IMAGE_TRANSFORM_ERROR_FAILED);
            }
        }
        if (!imagick_drawannotation($this->imageHandle, $params['x'], $params['y'], $params['text'])) {
            return $this->raiseError('Problem with adding Text',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }

        return true;

    } // End addText


    /**
     * Saves the image to a file
     *
     * @param $filename string the name of the file to write to
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function save($filename, $type = '', $quality = null)
    {
        $options = (is_array($quality)) ? $quality : array();
        if (is_numeric($quality)) {
            $options['quality'] = $quality;
        }
        $quality = $this->_getOption('quality', $options, 75);
        imagick_setcompressionquality($this->imageHandle, $quality);

        if ($type && strcasecmp($type, $this->type)
            && !imagick_convert($this->imageHandle, $type)) {
            return $this->raiseError('Couldn\'t save image to file (conversion failed).',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }

        if (!imagick_write($this->imageHandle, $filename)) {
            return $this->raiseError('Couldn\'t save image to file.',
                IMAGE_TRANSFORM_ERROR_IO);
        }
        $this->free();
        return true;

    } // End save

    /**
     * Displays image without saving and lose changes
     *
     * This method adds the Content-type HTTP header
     *
     * @param string type (JPG,PNG...);
     * @param int quality 75
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function display($type = '', $quality = null)
    {
        $options = (is_array($quality)) ? $quality : array();
        if (is_numeric($quality)) {
            $options['quality'] = $quality;
        }
        $quality = $this->_getOption('quality', $options, 75);
        imagick_setcompressionquality($this->imageHandle, $quality);

        if ($type && strcasecomp($type, $this->type)
            && !imagick_convert($this->imageHandle, $type)) {
            return $this->raiseError('Couldn\'t save image to file (conversion failed).',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }
        if (!($image = imagick_image2blob($this->imageHandle))) {
            return $this->raiseError('Couldn\'t display image.',
                IMAGE_TRANSFORM_ERROR_IO);
        }
        header('Content-type: ' . imagick_getmimetype($this->imageHandle));
        echo $image;
        $this->free();
        return true;
    }

    /**
     * Adjusts the image gamma
     *
     * @param float $outputgamma
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function gamma($outputgamma = 1.0) {
        if ($outputgamma != 1.0) {
            imagick_gamma($this->imageHandle, $outputgamma);
        }
        return true;
    }

    /**
     * Crops the image
     *
     * @param int width Cropped image width
     * @param int height Cropped image height
     * @param int x X-coordinate to crop at
     * @param int y Y-coordinate to crop at
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function crop($width, $height, $x = 0, $y = 0)
    {
        // Sanity check
        if (!$this->intersects($width, $height, $x, $y)) {
            return PEAR::raiseError('Nothing to crop', IMAGE_TRANSFORM_ERROR_OUTOFBOUND);
        }
        if (!imagick_crop($this->imageHandle, $x, $y, $width, $height)) {
            return $this->raiseError('Couldn\'t crop image.',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }

        // I think that setting img_x/y is wrong, but scaleByLength() & friends
        // mess up the aspect after a crop otherwise.
        $this->new_x = $width;
        $this->new_y = $height;

        return true;
    }

    /**
     * Horizontal mirroring
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function mirror()
    {
        if (!imagick_flop($this->imageHandle)) {
            return $this->raiseError('Couldn\'t mirror the image.',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }
        return true;
    }

    /**
     * Vertical mirroring
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function flip()
    {
        if (!imagick_flip($this->imageHandle)) {
            return $this->raiseError('Couldn\'t flip the image.',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }
        return true;
    }

    /**
     * Destroy image handle
     *
     * @access public
     */
    function free()
    {
        if (is_resource($this->imageHandle)) {
            imagick_destroyhandle($this->imageHandle);
        }
        $this->imageHandle = null;
    }

    /**
     * RaiseError Method - shows imagick Raw errors.
     *
     * @param string $message message = prefixed message..
     * @param int    $code error code
     * @return PEAR error object
     * @access protected
     */
    function raiseError($message, $code = 0)
    {
        if (is_resource($this->imageHandle)) {
            $message .= "\nReason: "
                        .  imagick_failedreason($this->imageHandle)
                        . "\nDescription: "
                        . imagick_faileddescription($this->imageHandle);
        }
        return PEAR::raiseError($message, $code);
    }

} // End class Image_Transform_Driver_Imagick2