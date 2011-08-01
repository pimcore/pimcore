<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Image Transformation interface using old ImageMagick extension
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
 * @author     Peter Bowyer <peter@mapledesign.co.uk>
 * @copyright  2002-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Imagick.php,v 1.9 2007/04/19 16:36:09 dufuz Exp $
 * @deprecated
 * @link       http://pear.php.net/package/Image_Transform
 */

/**
 * Include of base class
 */
require_once 'Image/Transform.php';


/**
 * Image Transformation interface using old ImageMagick extension
 *
 * DEPRECATED: current CVS/release imagick extension should use
 * the Imagick2 driver
 *
 * @deprecated
 */
class Image_Transform_Driver_Imagick extends Image_Transform
{
    /**
     * Handler of the imagick image ressource
     * @var array
     */
    var $imageHandle;

    /**
     * Handler of the image ressource before
     * the last transformation
     * @var array
     */
    var $oldImage;

    /**
     *
     *
     */
    function Image_Transform_Driver_Imagick()
    {
        if (!PEAR::loadExtension('imagick')) {
            return PEAR::raiseError('The imagick extension can not be found.', true);
        }
        include 'Image/Transform/Driver/Imagick/ImageTypes.php';
        return true;
    } // End Image_IM

    /**
     * Load image
     *
     * @param string filename
     *
     * @return mixed none or a PEAR error object on error
     * @see PEAR::isError()
     */
    function load($image)
    {
        $this->imageHandle = imagick_create();
        if ( !is_resource( $this->imageHandle ) ) {
            return PEAR::raiseError('Cannot initialize imagick image.', true);
        }

        if ( !imagick_read($this->imageHandle, $image) ){
            return PEAR::raiseError('The image file ' . $image . ' does\'t exist', true);
        }
        $this->image = $image;
        $result = $this->_get_image_details($image);
        if (PEAR::isError($result)) {
            return $result;
        }
    } // End load

    /**
     * Resize Action
     *
     * @param int   new_x   new width
     * @param int   new_y   new width
     * @param mixed $options Optional parameters
     *
     * @return none
     * @see PEAR::isError()
     */
    function _resize($new_x, $new_y, $options = null)
    {
        if ($img2 = imagick_copy_resize($this->imageHandle, $new_x, $new_y, IMAGICK_FILTER_CUBIC, 1)){
            $this->oldImage = $this->imageHandle;
            $this->imageHandle =$img2;
            $this->new_x = $new_x;
            $this->new_y = $new_y;
        } else {
            return PEAR::raiseError("Cannot create a new imagick imagick image for the resize.", true);
        }
    } // End resize

    /**
     * rotate
     * Note: color mask are currently not supported
     *
     * @param   int     Rotation angle in degree
     * @param   array   No option are actually allowed
     *
     * @return none
     * @see PEAR::isError()
     */
    function rotate($angle,$options=null)
    {
        if ($img2 = imagick_copy_rotate ($this->imageHandle, $angle)){
            $this->oldImage     = $this->imageHandle;
            $this->imageHandle  = $img2;
            $this->new_x = imagick_get_attribute($img2,'width');
            $this->new_y = imagick_get_attribute($img2,'height');
        } else {
            return PEAR::raiseError("Cannot create a new imagick imagick image for the resize.", true);
        }
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
     * @return none
     * @see PEAR::isError()
     */
    function addText($params)
    {
        $default_params = array(
                                'text'          => 'This is a Text',
                                'x'             => 10,
                                'y'             => 20,
                                'size'          => 12,
                                'color'         => 'red',
                                'font'          => 'Arial.ttf',
                                'resize_first'  => false // Carry out the scaling of the image before annotation?
                                );
        $params = array_merge($default_params, $params);
        extract($params);

        $color = is_array($color)?$this->colorarray2colorhex($color):strtolower($color);

        imagick_annotate($this->imageHandle,array(
                    "primitive"     => "text $x,$y ".$text,
                    "pointsize"     => $size,
                    "antialias"     => 0,
                    "fill"          => $color,
                    "font"          => $font,
                    ));
    } // End addText

    /**
     * Save the image file
     *
     * @param $filename string the name of the file to write to
     *
     * @return none
     */
    function save($filename, $type='', $quality = 75)
    {
        if (function_exists('imagick_setcompressionquality')) {
            imagick_setcompressionquality($this->imageHandle, $quality);
        }
        if ($type != '') {
            $type = strtoupper($type);
            imagick_write($this->imageHandle, $filename, $type);
        } else {
            imagick_write($this->imageHandle, $filename);
        }
        imagick_free($handle);
    } // End save

    /**
     * Display image without saving and lose changes
     *
     * @param string type (JPG,PNG...);
     * @param int quality 75
     *
     * @return none
     */
    function display($type = '', $quality = 75)
    {
        if ($type == '') {
            header('Content-type: image/' . $this->type);
            if (!imagick_dump($this->imageHandle));
        } else {
            header('Content-type: image/' . $type);
            if (!imagick_dump($this->imageHandle, $this->type));
        }
        $this->free();
    }


    /**
     * Destroy image handle
     *
     * @return none
     */
    function free()
    {
        if (is_resource($this->imageHandle)){
            imagick_free($this->imageHandle);
        }
        if (is_resource($this->oldImage)){
            imagick_free($this->oldImage);
        }
        return true;
    }

} // End class ImageIM