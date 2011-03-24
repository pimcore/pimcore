<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker */

/**
 * This is a driver file contains the Image_Tools_Thumbnail class
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2006-2008 Ildar N. Shaimordanov <ildar-sh@mail.ru>
 *
 * This source file is subject to the BSD License license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.opensource.org/licenses/bsd-license.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to pear-dev@list.php.net so we can send you a copy immediately.
 *
 * @category    Images
 * @package     Image_Tools
 * @author      Ildar N. Shaimordanov <ildar-sh@mail.ru>
 * @copyright   Copyright (c) 2006-2008 Ildar N. Shaimordanov <ildar-sh@mail.ru>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     CVS: $Id: Thumbnail.php,v 1.8 2008/05/26 06:26:17 firman Exp $
 * @since       File available since Release 0.4.0
 */

/**
 * Image_Tools
 */
require_once 'Image/Tools.php';

/**
 * Image_Tools_Utils
 */
require_once 'Image/Tools/Utils.php';

// {{{ Constants

/**
* Maximal scaling
*/
define('IMAGE_TOOLS_THUMBNAIL_METHOD_SCALE_MAX', 0);

/**
* Minimal scaling
*/
define('IMAGE_TOOLS_THUMBNAIL_METHOD_SCALE_MIN', 1);

/**
* Cropping of fragment
*/
define('IMAGE_TOOLS_THUMBNAIL_METHOD_CROP',      2);

/**
* Align constants
*/
define('IMAGE_TOOLS_THUMBNAIL_ALIGN_CENTER', 0);
define('IMAGE_TOOLS_THUMBNAIL_ALIGN_LEFT',   -1);
define('IMAGE_TOOLS_THUMBNAIL_ALIGN_RIGHT',  +1);
define('IMAGE_TOOLS_THUMBNAIL_ALIGN_TOP',    -1);
define('IMAGE_TOOLS_THUMBNAIL_ALIGN_BOTTOM', +1);

// }}}
// {{{ Class: Image_Tools_Thumbnail

/**
 * This class provide thumbnail creating tool for manipulating an image
 *
 * @category    Images
 * @package     Image_Tools
 * @author      Ildar N. Shaimordanov <ildar-sh@mail.ru>
 * @copyright   Copyright (c) 2006-2008 Ildar N. Shaimordanov <ildar-sh@mail.ru>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     Release: 1.0.0RC1
 * @since       Class available since Release 0.4.0
*/
class Image_Tools_Thumbnail extends Image_Tools
{
    // {{{ Properties

    /**
     * Thumbnail options:
     * <pre>
     * image   mixed  Destination image, a filename or an image string data or a GD image resource
     * width   int    Width of thumbnail
     * height  int    Height of thumbnail
     * percent number Size of thumbnail per size of original image
     * method  int    Method of thumbnail creating
     * halign  int    Horizontal align
     * valign  int    Vertical align
     * </pre>
     *
     * @var     array
     * @access  protected
     */
    var $options = array(
        'image'   => null,
        'width'   => 100,
        'height'  => 100,
        'percent' => 0,
        'method'  => IMAGE_TOOLS_THUMBNAIL_METHOD_SCALE_MAX,
        'halign'  => IMAGE_TOOLS_THUMBNAIL_ALIGN_CENTER,
        'valign'  => IMAGE_TOOLS_THUMBNAIL_ALIGN_CENTER,
    );

    /**
     * Available options for Image_Tools_Thumbnail
     *
     * @var     array
     * @access  protected
     */
    var $availableOptions = array(
        'image'   => 'mixed',
        'width'   => 'int',
        'height'  => 'int',
        'percent' => 'number',
        'method'  => 'int',
        'halign'  => 'int',
        'valign'  => 'int',
    );

    /**
     * Image_Tools_Thumbnail API version.
     *
     * @var     string
     * @access  protected
     */
    var $version = '1.0';

    /**
     * Image info.
     *
     * @var     resource
     * @access  protected
     */
    var $imageInfo = null;

    // }}}
    // {{{ preRender()

    /**
     * Function which called before render.
     *
     * @return  bool|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access  protected
     * @see     Image_Tools::createImage()
     */
    function preRender()
    {
        // Create the source image
        $res = Image_Tools::createImage($this->options['image']);
        if ( PEAR::isError($res) ) {
            return $res;
        }

        $this->resultImage = $res;
        $this->_init();
    }
    // }}}
    // {{{ postRender()

    /**
     * Function which called after render.
     *
     * @return  bool|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access  protected
     * @see render()
     */
    function postRender()
    {
        $this->_init();
    }
    // }}}
    // {{{ render()

    /**
     * Draw thumbnail result to resource.
     *
     * @return  bool|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access  public
     */
    function render()
    {
        if (!Image_Tools::isGDImageResource($this->resultImage)) {
            return PEAR::raiseError('Invalid image resource');
        }

        // Estimate a rectangular portion of the source image and a size of the target image
        if ($this->options['method'] == IMAGE_TOOLS_THUMBNAIL_METHOD_CROP) {
            if ( $this->options['percent'] ) {
                $W = floor($this->options['percent'] / 100 * $this->imageInfo['width']);
                $H = floor($this->options['percent'] / 100 * $this->imageInfo['height']);
            } else {
                $W = $this->options['width'];
                $H = $this->options['height'];
            }

            $width = $W;
            $height = $H;

            $Y = $this->_coord('valign', 'height', $H);
            $X = $this->_coord('halign', 'width', $W);
        } else {
            $W = $this->imageInfo['width'];
            $H = $this->imageInfo['height'];

            $X = 0;
            $Y = 0;

            if ($this->options['percent']) {
                $width = floor($this->options['percent'] / 100 * $W);
                $height = floor($this->options['percent'] / 100 * $H);
            } else {
                $width = $this->options['width'];
                $height = $this->options['height'];

                if ($this->options['method'] == IMAGE_TOOLS_THUMBNAIL_METHOD_SCALE_MIN) {
                    $Ww = $W / $width;
                    $Hh = $H / $height;
                    if ( $Ww > $Hh ) {
                        $W = floor($width * $Hh);
                        $X = $this->_coord('halign', 'width', $W);
                    } else {
                        $H = floor($height * $Ww);
                        $Y = $this->_coord('valign', 'height', $H);
                    }
                } else {
                    if ( $H > $W ) {
                        $width = floor($height / $H * $W);
                    } else {
                        $height = floor($width / $W * $H);
                    }
                }
            }
        }

        // Create the target image
        if (function_exists('imagecreatetruecolor')) {
            $target = imagecreatetruecolor($width, $height);
        } else {
            $target = imagecreate($width, $height);
        }
        if (!Image_Tools::isGDImageResource($target)) {
            return PEAR::raiseError('Cannot initialize new GD image stream');
        }

        // enable transparency if supported
        if (Image_Tools_Utils::compareGDVersion('2.0.28', '>=')) {
            // imagealphablending() and imagesavealpha() requires GD 2.0.38
            imagealphablending($target, false);
            imagesavealpha($target, true);
        }

        // Copy the source image to the target image
        if ($this->options['method'] == IMAGE_TOOLS_THUMBNAIL_METHOD_CROP) {
            $result = imagecopy($target, $this->resultImage, 0, 0, $X, $Y, $W, $H);
        } elseif (function_exists('imagecopyresampled')) {
            $result = imagecopyresampled($target, $this->resultImage, 0, 0, $X, $Y, $width, $height, $W, $H);
        } else {
            $result = imagecopyresized($target, $this->resultImage, 0, 0, $X, $Y, $width, $height, $W, $H);
        }
        if (!$result) {
            return PEAR::raiseError('Cannot resize image');
        }

        // Free a memory from the source image and save the resulting thumbnail
        imagedestroy($this->resultImage);
        $this->resultImage = $target;

        return true;
    }

    // }}}
    // {{{ _init()

    /**
     * Initialization function.
     */
    function _init() {
        $this->imageInfo = array(
            'width'  => imagesx($this->resultImage),
            'height' => imagesy($this->resultImage),
        );
    }

    // }}}
    // {{{ _coord()

    /**
     * Calculate the right coordinate depend on alignment.
     *
     * @param string $align Direction of alignment (halign or valign).
     * @param string $param Parameter (width or height)
     * @param integer $src Source value
     *
     * @return integer
     * @access private
     */
    function _coord($align, $param, $src)
    {
        if ($this->options[$align] < IMAGE_TOOLS_THUMBNAIL_ALIGN_CENTER) {
            $result = 0;
        } elseif ($this->options[$align] > IMAGE_TOOLS_THUMBNAIL_ALIGN_CENTER) {
            $result = $this->imageInfo[$param] - $src;
        } else {
            $result = ($this->imageInfo[$param] - $src) >> 1;
        }
        return $result;
    }

    // }}}

}

// }}}

/*
 * Local variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
