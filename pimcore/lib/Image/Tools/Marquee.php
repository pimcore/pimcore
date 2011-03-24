<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

// {{{ Header

/**
 * This is a driver file contains the Image_Tools_Marquee class.
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 *
 * Copyright (c) 2005-2008 Firman Wandayandi <firman@php.net>
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
 * @author      Firman Wandayandi <firman@php.net>
 * @copyright   Copyright (c) 2005-2006 Firman Wandayandi <firman@php.net>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     CVS: $Id: Marquee.php,v 1.4 2008/05/26 06:30:00 firman Exp $
 */

// }}}
// {{{ Dependencies

/**
 * Load Image_Tools as the base class.
 */
require_once 'Image/Tools.php';

// }}}
// {{{ Constants

/**
 * Image_Tools_Marquee rectangle marquee.
 *
 * @name IMAGE_TOOLS_MARQUEE_TYPE_RECTANGLE
 * @access public
 */
define('IMAGE_TOOLS_MARQUEE_TYPE_RECTANGLE', 'marquee_rectangle');

/**
 * Image_Tools_Marquee single column marquee.
 *
 * @name IMAGE_TOOLS_MARQUEE_TYPE_SINGLECOL
 * @access public
 */
define('IMAGE_TOOLS_MARQUEE_TYPE_SINGLECOL', 'marquee_singlecol');

/**
 * Image_Tools_Marquee single row marquee.
 *
 * @name IMAGE_TOOLS_MARQUEE_TYPE_SINGLEROW
 * @access public
 */
define('IMAGE_TOOLS_MARQUEE_TYPE_SINGLEROW', 'marquee_singlerow');

/**
 * Image_Tools_Marquee polygon marquee.
 * Notes: this constant is useless at this time.
 *
 * @name IMAGE_TOOLS_MARQUEE_TYPE_POLYGON
 * @access public
 */
define('IMAGE_TOOLS_MARQUEE_TYPE_POLYGON', 'marquee_polygon');

// }}}
// {{{ Class: Image_Tools_Marquee

/**
 * This class provide marquee extraction tool for manipulating an image.
 *
 * @category    Images
 * @package     Image_Tools
 * @author      Firman Wandayandi <firman@php.net>
 * @copyright   Copyright (c) 2005-2006 Firman Wandayandi <firman@php.net>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     Release: 1.0.0RC1
 */
class Image_Tools_Marquee extends Image_Tools
{
    // {{{ Properties

    /**
     * Marquee options:
     * <pre>
     * sample  mixed  Sample image, a GD image resource or string filename.
     * x       int    X position, this sets X position where marquee will be
     *                placed.
     * y       int    Y position, this sets Y position where marquee will be
     *                placed.
     * </pre>
     *
     * @var     array
     * @access  protected
     */
    var $options = array(
        'image'     => null,   // Destination image.
        'sample'    => null,   // Sample image.
        'x'         => 0,      // X position.
        'y'         => 0       // Y position.
    );

    /**
     * Available options for Image_Tools_Marquee.
     *
     * @var     array
     * @access  protected
     */
    var $availableOptions = array(
        'image'     => 'mixed',
        'sample'    => 'mixed',
        'x'         => 'int',
        'y'         => 'int'
    );

    /**
     * Available methods for Image_Tool_Marquee (only public methods).
     *
     * @var     array
     * @access  protected
     */
    var $availableMethods = array(
        'setRectangleMarquee' => array(
            'topLeftX'     => 'int',
            'topLeftY'     => 'int',
            'bottomRightX' => 'int',
            'bottomRightY' => 'int'
        ),
        'setSingleColMarquee' => array(
            'x' => 'int'
        ),
        'setSingleRowMarquee' => array(
            'y' => 'int'
        )
    );

    /**
     * Image_Tools_Marquee API version.
     *
     * @var     string
     * @access  protected
     */
    var $version = '1.0';

    /**
     * Marquee information.
     *
     * @var     array
     * @access  private
     */
    var $_marquee = array();

    /**
     * Sample image.
     *
     * @var     resource
     * @access  private
     */
    var $_sample = null;

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
        $res = Image_Tools::createImage($this->options['sample']);
        if (PEAR::isError($res)) {
            return $res;
        }
        $this->_sample = $res;

        $res = Image_Tools::createImage($this->options['image']);
        if (PEAR::isError($res)) {
            return $res;
        }
        $this->resultImage = $res;

        return true;
    }

    // }}}
    // {{{ setRectangleMarquee()

    /**
     * Set marquee using rectangle.
     *
     * @param   int $topLeftX Top left x coordinate.
     * @param   int $topLeftY Top left y coordinate.
     * @param   int $bottomRightX Bottom right x coordinate.
     * @param   int $bottomRightY Bottom right y coordinate.
     *
     * @return  bool Always TRUE.
     * @access  public
     * @see     _setMarquee()
     */
    function setRectangleMarquee($topLeftX, $topLeftY,
                                 $bottomRightX, $bottomRightY)
    {
        if ($topLeftX > $bottomRightX) {
            $tmp = $topLeftX;
            $topLeftX = $bottomRightX;
            $bottomRightX = $tmp;
        }

        if ($topLeftY > $bottomRightY) {
            $tmp = $bottomRightY;
            $topLeftY = $bottomRightY;
            $bottomRightY = $tmp;
        }

        $points = array($topLeftX, $topLeftY, $bottomRightX, $bottomRightY);
        $this->_setMarquee(IMAGE_TOOLS_MARQUEE_TYPE_RECTANGLE, $points);
        return true;
    }

    // }}}
    // {{{ setSingleColumnMarquee()

    /**
     * Set marquee using single column marquee.
     *
     * @param   int $x X coordinate.
     *
     * @return  bool Always TRUE.
     * @access  public
     * @see     _setMarquee()
     */
    function setSingleColumnMarquee($x)
    {
        $this->_setMarquee(IMAGE_EXTRACT_MARQUEE_TYPE_SINGLECOL, $x);
        return true;
    }

    // }}}
    // {{{ setSingleRowMarquee()

    /**
     * Set marquee using single row marquee.
     *
     * @param int $y Y coordinate.
     *
     * @return bool Always TRUE.
     * @access public
     * @see Image_Tools_Marquee::_setMarquee()
     */
    function setSingleRowMarquee($y)
    {
        $this->_setMarquee(IMAGE_EXTRACT_MARQUEE_TYPE_SINGLEROW, $y);
        return true;
    }

    // }}}
    // {{{ render()

    /**
     * Draw extraction result to resource.
     *
     * @return bool|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access public
     * @see Image_Tools_Marquee::_extractRectangle(),
     *      Image_Tools_Marquee::_extractSingleCol(),
     *      Image_Tools_Marquee::_extractSingleRow()
     */
    function render()
    {
        if (!Image_Tools::isGDImageResource($this->resultImage)) {
            return PEAR::raiseError('Invalid image resource Image_Tools_Mask::$_resultImage');
        }

        $x = $this->options['x'];
        $y = $this->options['y'];

        switch ($this->_marquee['type']) {
            case IMAGE_TOOLS_MARQUEE_TYPE_RECTANGLE:
                $this->_extractRectangle($this->resultImage, $x, $y);
                break;
            case IMAGE_TOOLS_MARQUEE_TYPE_SINGLECOL:
                $this->_extractSingleColumn($this->resultImage, $x, $y);
                break;
            case IMAGE_TOOLS_MARQUEE_TYPE_SINGLEROW:
                $this->_extractSingleRow($this->resultImage, $x, $y);
                break;
        }

        return true;
    }

    // }}}
    // {{{ _setMarquee()

    /**
     * Set marquee informations.
     *
     * @param string $type Marquee type. Use marquee type defined constants.
     * @param mixed $points Marquee coordinate points.
     *
     * @access private
     */
    function _setMarquee($type, $points)
    {
        $this->_marquee = array(
                            'type' => $type,
                            'points' => $points
                          );
    }

    // }}}
    // {{{ _extractRectangle()

    /**
     * Extract sample image using rectangle marquee and
     * draw it on destination image.
     *
     * @param resource $img GD image resource.
     * @param int $dstX Top left x coordinate.
     * @param int $dstY Top left y coordinate.
     *
     * @access private
     */
    function _extractRectangle(&$img, $dstX, $dstY)
    {
        $srcX = $this->_marquee['points'][0];
        $srcY = $this->_marquee['points'][1];
        $srcW = $this->_marquee['points'][2] - $srcX;
        $srcH = $this->_marquee['points'][3] - $srcY;
        imagecopymerge($img, $this->_sample,
                       $dstX, $dstY, $srcX, $srcY,
                       $srcW, $srcH, 100);
    }

    // }}}
    // {{{ _extractSingleColumn()

    /**
     * Extract sample image using single column marquee and
     * draw it on destination image.
     *
     * @param resource $img GD image resource.
     * @param int $dstX Top left x coordinate.
     * @param int $dstY Top left y coordinate.
     *
     * @access private
     */
    function _extractSingleColumn(&$img, $dstX, $dstY)
    {
        $srcX = $this->_marquee['points'];
        $srcY = 0;
        $srcW = 1;
        $srcH = imagesy($this->_sample);
        imagecopymerge($img, $this->_sample,
                       $dstX, $dstY, $srcX, $srcY,
                       $srcW, $srcH, 100);
    }

    // }}}
    // {{{ _extractSingleRow()

    /**
     * Extract sample image using single column marquee and
     * draw it on destination image.
     *
     * @param resource $img GD image resource.
     * @param int $dstX Top left x coordinate.
     * @param int $dstY Top left y coordinate.
     *
     * @access private
     */
    function _extractSingleRow(&$img, $dstX, $dstY)
    {
        $srcX = 0;
        $srcY = $this->_marquee['points'];
        $srcW = imagesx($this->_sample);
        $srcH = 1;
        imagecopymerge($img, $this->_sample,
                       $dstX, $dstY, $srcX, $srcY,
                       $srcW, $srcH, 100);
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
