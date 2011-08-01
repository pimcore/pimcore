<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Jason Rust <jrust@rustyparts.com>                           |
// +----------------------------------------------------------------------+
// $Id: Imlib.php,v 1.6 2007/04/19 16:36:09 dufuz Exp $
// {{{ requires

require_once 'Image/Transform.php';

// }}}
// {{{ example usage

//    $img    = new Image_Transform::factory('Imlib');
//    $angle  = -90;
//    $img->load('test.png');
//    $img->rotate($angle);
//    $img->addText(array('text'=>"Rotation $angle",'x'=>0,'y'=>100,'font'=>'arial.ttf','color'=>'#ffffff'));
//    $img->display();

// }}}
// {{{ class Image_Transform_Driver_Imlib

/**
 * Performs image manipulation with the imlib library.
 *
 * @see http://mmcc.cx/php_imlib/index.php
 * @version Revision: 1.0
 * @author  Jason Rust <jrust@rustyparts.com>
 * @package Image_Transform
 */

// }}}
class Image_Transform_Driver_Imlib extends Image_Transform {
    // {{{ properties

    /**
     * Holds the image file for manipulation
     */
    var $imageHandle = '';

    /**
     * Holds the original image file
     */
    var $oldHandle = '';

    // }}}
    // {{{ constructor

    /**
     * Check settings
     *
     * @see __construct()
     */
    function Image_Transform_Imlib()
    {
        $this->__construct();
    }

    /**
     * Check settings
     *
     * @return mixed true or  or a PEAR error object on error
     *
     * @see PEAR::isError()
     */
    function __construct()
    {
        if (!PEAR::loadExtension('imlib')) {
            $this->isError(PEAR::raiseError('Couldn\'t find the imlib extension.', true));
        }
    }

    // }}}
    // {{{ load()

    /**
     * Load image
     *
     * @param string filename
     *
     * @return mixed TRUE or a PEAR error object on error
     * @see PEAR::isError()
     */
    function load($image)
    {
        $this->image = $image;
        $this->imageHandle = imlib_load_image($this->image);
        $result =& $this->_get_image_details($image);
        if (PEAR::isError($result)) {
            return $result;
        }

        return true;
    }

    // }}}
    // {{{ addText()

    /**
     * Adds text to the image.  Note that the angle should be one of the following
     * constants:  IMLIB_TEXT_TO_RIGHT, IMLIB_TEXT_TO_LEFT, IMLIB_TEXT_TO_DOWN,
     * IMLIB_TEXT_TO_UP, IMLIB_TEXT_TO_ANGLE
     *
     * @param   array   options     Array contains options
     *                              array(
     *                                  'text'  The string to draw
     *                                  'x'     Horizontal position
     *                                  'y'     Vertical Position
     *                                  'color' Font color
     *                                  'font'  Font to be used
     *                                  'size'  Size of the fonts in pixel
     *                                  'angle' A imlib direction constant
     *                              )
     *
     * @return TRUE or PEAR Error object on error
     * @see PEAR::isError()
     */
    function addText($params)
    {
        $default_params = array(
                                'text' => 'This is Text',
                                'x' => 10,
                                'y' => 20,
                                'color' => array(255,0,0),
                                'font' => 'Arial.ttf',
                                'size' => '12',
                                'angle' => IMLIB_TEXT_TO_RIGHT,
                                );
        $params = array_merge($default_params, $params);
        extract($params);

        if (!is_array($color)){
            if ($color[0] == '#'){
                $color = $this->colorhex2colorarray($color);
            } else {
                include_once('Image/Transform/Driver/ColorsDefs.php');
                $color = isset($colornames[$color]) ? $colornames[$color] : false;
            }
        }

        $fontResource = imlib_load_font($font . '/' . $size);
        imlib_text_draw($this->imageHandle, $fontResource, $x, $y, $text, $angle, $color[0], $color[1], $color[2], 255);
        return true;
    }

    // }}}
    // {{{ rotate()

    /**
     * Rotate image by the given angle
     *
     * @param int       $angle      Rotation angle
     *
     * @return TRUE or PEAR Error object on error
     */
    function rotate($angle)
    {
        $this->oldHandle = $this->imageHandle;
        $this->imageHandle = imlib_create_rotated_image($this->imageHandle, $angle);
        $new_x = imlib_image_get_width($this->imageHandle);
        $new_y = imlib_image_get_height($this->imageHandle);
        // when rotating it creates a bigger picture than before so that it can rotate at any angle
        // so for right angles we crop it back to the original size
        if ($angle % 90 == 0) {
            if (abs($angle) == 90 || $angle == 270) {
                $y_pos = ($new_x - $this->img_x) / 2;
                $x_pos = ($new_y - $this->img_y) / 2;
                $y_pos++;
                $x_pos++;
                $this->crop($this->img_y, $this->img_x, $x_pos, $y_pos);
            }
            else {
                $x_pos = ($new_x - $this->img_x) / 2;
                $y_pos = ($new_y - $this->img_y) / 2;
                $this->crop($this->img_x, $this->img_y, $x_pos, $y_pos);
            }
        }
        else {
            $this->img_x = $new_x;
            $this->img_y = $new_y;
        }

        return true;
    }

    // }}}
    // {{{ crop()

    /**
     * Crops the current image to a specified height and width
     *
     * @param int $in_cropWidth The width of the new image
     * @param int $in_cropHeight The height of the new image
     * @param int $in_cropX The X coordinate on the image to start the crop
     * @param int $in_cropY The Y coordinate on the image to start the crop
     *
     * @access public
     * @return TRUE or PEAR Error object on error
     */
    function crop($in_cropWidth, $in_cropHeight, $in_cropX, $in_cropY)
    {
        // Sanity check
        if (!$this->_intersects($in_cropWidth, $in_cropHeight, $in_cropX, $in_cropY)) {
            return PEAR::raiseError('Nothing to crop', IMAGE_TRANSFORM_ERROR_OUTOFBOUND);
        }
        $this->oldHandle = $this->imageHandle;
        $this->imageHandle = imlib_create_cropped_image($this->imageHandle, $in_cropX, $in_cropY, $in_cropWidth, $in_cropHeight);
        $this->img_x = $in_cropWidth;
        $this->img_y = $in_cropHeight;
        return true;
    }

    // }}}
    // {{{ save()

    /**
     * Save the image file.  Determines what type of image to save based on extension.
     *
     * @param $filename string  the name of the file to write to
     * @param $type     string  (optional) define the output format, default
     *                          is the current used format
     * @param $quality  int     (optional) output DPI, default is 75
     *
     * @return TRUE on success or PEAR Error object on error
     */
    function save($filename, $type = '', $quality = 75)
    {
        if (!is_resource($this->imageHandle)) {
            return PEAR::raiseError('Invalid image', true);
        }

        $err = 0;
        $type    = ($type == '') ? $this->type : $type;
        $quality = (is_null($quality)) ? $this->_options['quality'] : $quality;
        imlib_image_set_format($this->imageHandle, $type);
        $return = imlib_save_image($this->imageHandle, $filename, $err, $quality);
        $this->imageHandle = $this->oldHandle;
        $this->resized = false;
        if (!$return) {
            return PEAR::raiseError('Couldn\'t save image. Reason: ' . $err, true);
        }
        return true;
    }

    // }}}
    // {{{ display()

    /**
     * Display image without saving and lose changes
     *
     * This method adds the Content-type HTTP header
     *
     * @param string $type (optional) (JPG,PNG...);
     * @param int $quality (optional) 75
     *
     * @return TRUE on success or PEAR Error object on error
     */
    function display($type = '', $quality = null)
    {
        if (!is_resource($this->imageHandle)) {
            return PEAR::raiseError('Invalid image', true);
        }

        $type    = ($type == '') ? $this->type : $type;
        $quality = (is_null($quality)) ? $this->_options['quality'] : $quality;
        imlib_image_set_format($this->imageHandle, $type);
        $err = 0;
        header('Content-type: ' . $this->getMimeType($type));
        $return = imlib_dump_image($this->imageHandle, $err, $quality);
        $this->imageHandle = $this->oldHandle;
        $this->resized = false;
        imlib_free_image($this->oldHandle);
        if (!$return) {
            return PEAR::raiseError('Couldn\'t output image. Reason: ' . $err, true);
        }
        return true;
    }

    // }}}
    // {{{ free()

    /**
     * Destroy image handle
     *
     * @return void
     */
    function free()
    {
        if (is_resource($this->imageHandle)) {
            imlib_free_image($this->imageHandle);
        }
    }

    // }}}
    // {{{ _resize()

    /**
     * Resize the image.
     *
     * @access private
     *
     * @param int   $new_x   New width
     * @param int   $new_y   New height
     * @param mixed $options Optional parameters
     *
     * @return TRUE on success or PEAR Error object on error
     * @see PEAR::isError()
     */
    function _resize($new_x, $new_y, $options = null)
    {
        if ($this->resized === true) {
            return PEAR::raiseError('You have already resized the image without saving it.  Your previous resizing will be overwritten', null, PEAR_ERROR_TRIGGER, E_USER_NOTICE);
        }

        $this->oldHandle = $this->imageHandle;
        $this->imageHandle = imlib_create_scaled_image($this->imageHandle, $new_x, $new_y);
        $this->img_x = $new_x;
        $this->img_y = $new_y;
        $this->resized = true;
        return true;
    }

    // }}}
    // {{{ _get_image_details()

    /**
     * Gets the image details
     *
     * @access private
     * @return TRUE on success or PEAR Error object on error
     */
    function _get_image_details()
    {
        $this->img_x = imlib_image_get_width($this->imageHandle);
        $this->img_y = imlib_image_get_height($this->imageHandle);
        $this->type = imlib_image_format($this->imageHandle);
        $this->type = ($this->type == '') ? 'png' : $this->type;
        return true;
    }

    // }}}

    /**
     * Horizontal mirroring
     *
     * @return TRUE on success, PEAR Error object on error
     */
    function mirror()
    {
        imlib_image_flip_horizontal($this->imageHandle);
        return true;
    }

    /**
     * Vertical mirroring
     *
     * @return TRUE on success, PEAR Error object on error
     */
    function flip()
    {
        imlib_image_flip_vertical($this->imageHandle);
        return true;
    }
}