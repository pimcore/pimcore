<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker */

/**
 * Image_Tools_Blend
 *
 * PHP version 4 and 5
 *
 * LICENSE:
 * Copyright (c) 2008 Firman Wandayandi <firman@php.net>
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
 * @copyright   Copyright (c) 2008 Firman Wandayandi <firman@php.net>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     $Id: Blend.php,v 1.3 2008/05/26 09:31:23 firman Exp $
 * @since       File available since Release 1.0.0RC1
 */

/**
 * Image_Tools
 */
require_once 'Image/Tools.php';

/**
 * Image_Tools_Utils
 */
require_once 'Image/Tools/Utils.php';

/**
 * Class provide image blending functions.
 *
 * Algorithms base on article http://www.pegtop.net/delphi/articles/blendmodes
 * written by Jens Gruschel.
 *
 * @category    Images
 * @package     Image_Tools
 * @author      Firman Wandayandi <firman@php.net>
 * @copyright   Copyright (c) 2008 Firman Wandayandi <firman@php.net>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     Release: 1.0.0RC1
 * @since       Class available since Release 1.0.0RC1
 */
class Image_Tools_Blend extends Image_Tools
{
    /**
     * Image_Tools_Thumbnail API version.
     *
     * @var     string
     * @access  protected
     */
    var $version = '0.1';

    /**
     * Blend options:
     * <pre>
     * image1   mixed   First image or destination or background
     * image2   mixed   Second image or sample or foreground
     * mode     string  Blend mode, may one of normal, multiply,
     *                  screen, darken, lighten, difference, exclusion,
     *                  negation, interpolation, stamp, softlight, hardlight,
     *                  overlay, colordodge, colorburn, softdodge, softburn,
     *                  additive, subtractive, reflect, glow, freeze, heat,
     *                  logicXOR, logicAND or logicOR
     * x        int     X position of the second image on the first image
     * y        int     Y position of the second image on the first image
     * </pre>
     *
     * @var array
     * @access protected
     */
    var $options = array(
        'image1' => null,
        'image2' => null,
        'mode'   => 'normal',
        'x'      => 0,
        'y'      => 0,
        'params' => array()
    );

    /**
     * Available options.
     *
     * @var array
     * @access protected
     * @see Image_Tools_Utils::$options
     */
    var $availableOptions = array(
        'image1'    => 'mixed',
        'image2'    => 'mixed',
        'mode'      => 'string',
        'x'         => 'integer',
        'y'         => 'integer',
        'params'    => 'array'
    );

    /**
     * First image resource.
     *
     * @var resource
     * @access private
     */
    var $_image1;

    /**
     * Second image resource.
     *
     * @var resource
     * @access private
     */
    var $_image2;

    /**
     * Cosine table for interpolation mode.
     *
     * @var array
     * @access private
     * @see Image_Tools_Utils::interpolation()
     */
    var $_cosineTab = array();

    /**
     * Function which called before render.
     *
     * @return  bool|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access  protected
     * @see     Image_Tools::createImage()
     */
    function preRender()
    {
        $res = Image_Tools::createImage($this->options['image1']);
        if (PEAR::isError($res)) {
            return $res;
        }
        $this->_image1 = $res;

        $res = Image_Tools::createImage($this->options['image2']);
        if (PEAR::isError($res)) {
            return $res;
        }
        $this->_image2 = $res;

        // initialize an array if only its interpolation mode
        if ($this->options['mode'] == 'interpolation') {
            for ($i = 0; $i < 256; $i++) {
                $this->cosineTab[] = (int) (round(64 - cos($i * M_PI / 255) * 64));
            }
        }

        return true;
    }

    /**
     * Apply the blend mode.
     *
     * @return true|PEAR_Error
     * @access protected
     */
    function render()
    {
        $method = array($this, "_{$this->options['mode']}");
        if (!is_callable($method)) {
            return PEAR::raiseError('Invalid mode or not supported');
        }

        $width1 = imagesx($this->_image1);
        $height1 = imagesy($this->_image1);
        $width = imagesx($this->_image2);
        $height = imagesy($this->_image2);

        // sets out of bound flags
        $x_outbound = $y_outbound = false;

        // walking through pixels
        for ($x2 = 0, $x1 = $this->options['x']; $x2 < $width; $x2++, $x1++) {
            // x and y already out of bound, nothing to process
            if ($x_outbound && $y_outbound) {
                break;
            }

            $x_outbound = $y_outbound = false;

            // x is out bound
            if ($x1 >= $width1) {
                $x_outbound = true;
                continue;
            }

            for ($y2 = 0, $y1 = $this->options['y']; $y2 < $height; $y2++, $y1++) {
                // y is out bound
                if ($y1 >= $height1) {
                    $y_outbound = true;
                    continue;
                }

                $color1 = Image_Tools_Utils::colorToRGBA(imagecolorat($this->_image1, $x1, $y1));
                $color2 = Image_Tools_Utils::colorToRGBA(imagecolorat($this->_image2, $x2, $y2));

                // ignore transparencies
                if ($color2['a'] == 127) {
                    continue;
                }

                $params = array_merge(array($color1['r'], $color2['r']), $this->options['params']);
                $red = call_user_func_array($method, $params);

                $params = array_merge(array($color1['g'], $color2['g']), $this->options['params']);
                $green = call_user_func_array($method, $params);

                $params = array_merge(array($color1['b'], $color2['b']), $this->options['params']);
                $blue = call_user_func_array($method, $params);

                $color = imagecolorallocatealpha($this->_image1, $red, $green, $blue, $color2['a']);
                imagesetpixel($this->_image1, $x1, $y1, $color);
            }
        }

        $this->resultImage = $this->_image1;

        // frees up memory
        imagedestroy($this->_image2);

        return true;
    }

    /**
     * Normal blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _normal($a, $b)
    {
        return $b;
    }

    /**
     * Multiply blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _multiply($a, $b)
    {
        return (int)($a * $b / 255);
    }

    /**
     * Screen blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _screen($a, $b)
    {
        return (int)(255 - ((255 - $a) * (255 - $b) / 255));
    }

    /**
     * Darken blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _darken($a, $b)
    {
        if ($a < $b) {
            return $a;
        }
        return $b;
    }

    /**
     * Lighten blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _lighten($a, $b)
    {
        if ($a > $b) {
            return $a;
        }
        return $b;
    }

    /**
     * Difference blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _difference($a, $b)
    {
        return abs($a - $b);
    }

    /**
     * Exclusion blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _exclusion($a, $b)
    {
        return (int)($a + $b - ($a * $b / 127));
    }

    /**
     * Negation blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _negation($a, $b)
    {
        return 255 - abs(255 - $a - $b);
    }

    /**
     * Interpolation blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _interpolation($a, $b)
    {
        $c = $this->cosineTab[$b] + $this->cosineTab[$a];
        if ($c > 255) {
            return 255;
        } else {
            return $c;
        }
    }

    /**
     * Stamp blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _stamp($a, $b)
    {
        $c = $a + 2*$b - 256;
        if ($c < 0) {
            return 0;
        } else if ($c > 255) {
            return 255;
        } else {
            return $c;
        }
    }

    /**
     * Soft Light blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _softlight($a, $b)
    {
        $c = (int)($a * $b / 255);
        return (int)($c + $a * (255 - ((255-$a) * (255-$b) / 255) - $c) / 255);
    }

    /**
     * Hard Light blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _hardlight($a, $b)
    {
        if ($b < 127) {
            $result = ($a*$b) / 127;
        } else {
            $result = 255 - ((255-$b) * (255-$a) / 127);
        }
        return (int) $result;
    }

    /**
     * Overlay blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _overlay($a, $b)
    {
        if ($a < 128) {
        $result = $a * $b / 127;
        } else {
        $result = 255 - ((255 - $a) * (255 - $b) / 127);
        }
        return (int) $result;
    }

    /**
     * Color Dodge blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _colordodge($a, $b)
    {
        if ($b == 255) {
            return 255;
        } else {
            $c = (int)(($a << 8) / (255-$b));
            if ($c > 255) {
                return 255;
            } else {
                return $c;
            }
        }
    }

    /**
     * Color Burn blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _colorburn($a, $b)
    {
        if ($b == 0) {
            return 0;
        } else {
            $c = (int)(255 - (((255-$a) << 8) / $b));
            if ($c < 0) {
                return 0;
            } else {
                return $c;
            }
        }
    }

    /**
     * Soft Dodge blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _softdodge($a, $b)
    {
        if ($a + $b < 256) {
            if ($b == 255) {
                return 255;
            } else {
                $c = (int)(($a << 7) / (255-$b));
                if ($c > 255) {
                    return 255;
                } else {
                    return $c;
                }
            }
        } else {
            $c = (int)(255 - (((255-$b) << 7) / $a));
            if ($c < 0) {
                return 0;
            } else {
                return $c;
            }
        }
    }

    /**
     * Soft Burn blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _softburn($a, $b)
    {
        if ($a+$b < 256) {
            if ($a = 255) {
                return 255;
            } else {
                $c = (int)(($b << 7) / (255-$a));
                if ($c > 255) {
                    return 255;
                } else {
                    return $c;
                }
            }
        } else {
            $c = (int)(255 - (((255-$a) << 7) / $b));
            if ($c < 0) {
                return 0;
            } else {
                return $c;
            }
        }
    }

    /**
     * Additive blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _additive($a, $b)
    {
        $c = $a + $b;
        if ($c > 255) {
            return 255;
        } else {
            return $c;
        }
    }

    /**
     * Subtractive blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _subtractive($a, $b)
    {
        $c = $a + $b - 255;
        if ($c < 0) {
            return 0;
        } else {
            return $c;
        }
    }

    /**
     * Reflect blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _reflect($a, $b)
    {
        if ($b == 255) {
            return 255;
        } else {
            $c = (int)($a * $a / (255-$b));
            if ($c > 255) {
                return 255;
            } else {
                return $c;
            }
        }
    }

    /**
     * Glow blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _glow($a, $b)
    {
        if ($a == 255) {
            return 255;
        } else {
            $c = (int)($b*$b / (255-$a));
            if ($c > 255) {
                return 255;
            } else {
                return $c;
            }
        }
    }

    /**
     * Freeze blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _freeze($a, $b)
    {
        if ($b == 0) {
            return 0;
        } else {
            $c = (int)(255 - sqrt(255-$a) / $b);
            if ($c < 0) {
                return 0;
            } else {
                return $c;
            }
        }
    }

    /**
     * Heat blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _heat($a, $b)
    {
        if ($a == 0) {
            return 0;
        } else {
            $c = (int)(255 - sqrt(255-$b) / $a);
            if ($c < 0) {
                return 0;
            } else {
                return $c;
            }
        }
    }

    /**
     * Logic XOR blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _logicXOR($a, $b)
    {
        return $a ^ $b;
    }

    /**
     * Logic AND blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _logicAND($a, $b)
    {
        return $a & $b;
    }

    /**
     * Logic OR blend mode.
     *
     * @param integer $a Background color (0 to 255)
     * @param integer $b Foreground color (0 to 255)
     * @return integer
     * @access private
     */
    function _logicOR($a, $b)
    {
        return $a | $b;
    }
}
