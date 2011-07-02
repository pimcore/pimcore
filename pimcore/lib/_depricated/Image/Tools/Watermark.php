<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker */

/**
 * This is a driver file contains the Image_Tools_Watermark class
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008 Charles Brunet <charles.fmj@gmail.com>
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
 * @author      Charles Brunet <charles.fmj@gmail.com>
 * @copyright   Copyright (c) 2008 Charles Brunet <charles.fmj@gmail.com>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @since       File available since Release 1.0.0RC1
 */

require_once 'Image/Tools.php';

// {{{ Constants

/**
* Position for the logo
*/
define('IMAGE_TOOLS_WATERMARK_POSITION_LEFT', 0);
define('IMAGE_TOOLS_WATERMARK_POSITION_CENTER', 1);
define('IMAGE_TOOLS_WATERMARK_POSITION_RIGHT', 2);
define('IMAGE_TOOLS_WATERMARK_POSITION_TOP', 0);
define('IMAGE_TOOLS_WATERMARK_POSITION_MIDDLE', 1);
define('IMAGE_TOOLS_WATERMARK_POSITION_BOTTOM', 2);

// }}}
// {{{ Class: Image_Tools_Watermark

/**
 * This class provide a tool to add a little logo on an image
 *
 * @category    Images
 * @package     Image_Tools
 * @author      Charles Brunet <charles.fmj@gmail.com>
 * @copyright   Copyright (c) 2008 Charles Brunet <charles.fmj@gmail.com>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     Release: 1.0.0RC1
 * @since       Class available since Release 1.0.0RC1
*/
class Image_Tools_Watermark extends Image_Tools
{
    // {{{ Properties

    /**
     * Thumbnail options:
     * <pre>
     * image    mixed  Destination image, a filename or an image string data or a GD image resource
     * logo     mixed  Source logo, a filename or an image string data or a GD image resource
     * width    int    Width of logo; 0 to keep original width
     * height   int    Height of logo; 0 to keep original height
     * marginx  int    Horizontal margin from the border of the image
     * marginy  int    Vertical margin from the border of the image
     * horipos  number Horizontal position of the logo on destination image
     * vertpos  number Vertical position of the logo on destination image
     * blend    string Blending mode to use on composite operation, see
     *                 Image_Tools_Blend of the supported blend mode. Default
     *                 is none (no blending).
     * </pre>
     *
     * @var     array
     * @access  protected
     */
    var $options = array(
        'image'   => null,
        'mark'    => null,
        'width'   => 0,
        'height'  => 0,
        'marginx' => 0,
        'marginy' => 0,
        'horipos' => IMAGE_TOOLS_WATERMARK_POSITION_RIGHT,
        'vertpos' => IMAGE_TOOLS_WATERMARK_POSITION_BOTTOM,
        'blend'   => 'none'
    );

    /**
     * Available options for Image_Tools_Watermark
     *
     * @var     array
     * @access  protected
     */
    var $availableOptions = array(
        'image'   => 'mixed',
        'mark'    => 'mixed',
        'width'   => 'int',
        'height'  => 'int',
        'marginx' => 'int',
        'marginy' => 'int',
        'horipos' => 'number',
        'vertpos' => 'number',
        'blend'   => 'string'
    );

    /**
     * Image_Tools_Watermark API version.
     *
     * @var     string
     * @access  protected
     */
    var $version = '0.2';

    /**
     * Resource of source image.
     *
     * @var resource
     * @access private
     */
    var $_source = null;

    /**
     * Resource of mark image.
     *
     * @var resource
     * @access private
     */
    var $_mark = null;

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
        $source = Image_Tools::createImage($this->options['image']);
        if (PEAR::isError($source)) {
            return $source;
        }
        $this->_source = $source;

        // Create the mark image
        $mark = Image_Tools::createImage($this->options['mark']);
        if (PEAR::isError($mark)) {
            return $mark;
        }
        $this->_mark = $mark;

        // includes the Image_Tools_Blend if blend mode enable
        if ($this->options['blend'] != 'none') {
            require_once 'Image/Tools/Blend.php';
        }
    }

    // }}}
    // {{{ render()

    /**
     * Draw image with logo result to resource.
     *
     * @return  bool|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access  public
     */
    function render()
    {
        $W = imagesx($this->_source);
        $H = imagesy($this->_source);

        $MW = imagesx($this->_mark);
        $MH = imagesy($this->_mark);

        // resize the logo image
        if ($this->options['width'] == 0 && $this->options['height'] == 0) {
            $this->options['width'] = $MW;
            $this->options['height'] = $MH;
        } else if ($this->options['width'] == 0) {
            $this->options['width'] = ceil($this->options['height'] * $MW / $MH);
        } else if ($this->options['height'] == 0) {
            $this->options['height'] = ceil($this->options['width'] * $MH / $MW);
        }

        // calculates the x position
        switch ($this->options['horipos']) {
            case IMAGE_TOOLS_WATERMARK_POSITION_LEFT:
                $posx = $this->options['marginx'];
                break;
            case IMAGE_TOOLS_WATERMARK_POSITION_CENTER:
                $posx = round(($W - $this->options['width'])/2);
                break;
            case IMAGE_TOOLS_WATERMARK_POSITION_RIGHT:
            default:
                $posx = $W - $this->options['width'] - $this->options['marginx'];
                break;
        }

        // calculates the y position
        switch ($this->options['vertpos']) {
            case IMAGE_TOOLS_WATERMARK_POSITION_TOP:
                $posy = $this->options['marginy'];
                break;
            case IMAGE_TOOLS_WATERMARK_POSITION_MIDDLE:
                $posy = round(($H - $this->options['height'])/2);
                break;
            case IMAGE_TOOLS_WATERMARK_POSITION_BOTTOM:
            default:
                $posy = $H - $this->options['height'] - $this->options['marginy'];
            break;
        }

        settype($posx, 'integer');
        settype($posy, 'integer');

        // Create the target image
        imagealphablending($this->_mark, false);

        // resize the mark image
        if ($MH != $this->options['height'] && $MW != $this->options['width']) {
            if (function_exists('imagecreatetruecolor')) {
                $mark = imagecreatetruecolor($this->options['width'], $this->options['height']);
            } else {
                $mark = imagecreate($this->options['width'], $this->options['height']);
            }

            imagealphablending($mark, false);

            if (function_exists('imagecoypresampled')) {
                $result = imagecopyresampled(
                    $mark, $this->_mark, 0, 0, 0, 0,
                    $this->options['width'], $this->options['height'], $MW, $MH
                );
            } else {
                $result = imagecopyresized(
                    $mark, $this->_mark, 0, 0, 0, 0,
                    $this->options['width'], $this->options['height'], $MW, $MH
                );
            }

            $this->_mark = $mark;
        }

        if ($this->options['blend'] != 'none') {
            $blend = Image_Tools::factory('blend');
            $blend->set('image1', $this->_source);
            $blend->set('image2', $this->_mark);
            $blend->set('x', $posx);
            $blend->set('y', $posy);
            $blend->set('mode', $this->options['blend']);

            // applies the blending mode.
            $this->_source = $blend->getResultImage();
        } else {
            $result = imagecopy($this->_source, $this->_mark, $posx, $posy, 0, 0, $MW, $MH);
            if (!$result) {
                return PEAR::raiseError('Cannot copy logo image');
            }
        }

        $this->resultImage = $this->_source;
        unset($this->_mark);

        return true;
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
