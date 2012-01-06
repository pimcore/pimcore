<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */

// {{{ Header

/**
 * This is a main file of Image_Tools package.
 *
 * PHP versions 4 and 5
 *
 * LICENSE:
 * Copyright (c) 2005-2008
 *  Tobias Schlitt <toby@php.net>,
 *  Firman Wandayandi <firman@php.net>
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
 * @author      Tobias Schlitt <toby@php.net>
 * @author      Firman Wandayandi <firman@php.net>
 * @copyright   Copyright (c) 2003-2008
 *                Tobias Schlitt <toby@php.net>,
 *                Firman Wandayandi <firman@php.net>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     CVS: $Id: Tools.php,v 1.13 2008/05/26 09:18:09 firman Exp $
 */

// }}}
// {{{ Dependencies

/**
 * load PEAR for error handling.
 */
require_once 'PEAR.php';

/**
 * load PHP_Compat for PHP backward compatibility.
 */
require_once 'PHP/Compat.php';

/**
 * is_a(), since PHP 4.2.0
 */
@PHP_Compat::loadFunction('is_a');

/**
 * image_type_to_mime_type, since PHP 4.3.0
 */
@PHP_Compat::loadFunction('image_type_to_mime_type');

// }}}
// {{{ Constants

/**
 * Image_Tools error, indicating that the given tool was not found.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_CLASS_INVALID
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_CLASS_INVALID', -1);

/**
 * Image_Tools error, indicating that the given tool object could not be
 * instanciated.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_INSTANCIATION_FAILED
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_INSTANCIATION_FAILED', -2);

/**
 * Image_Tools error, indicating that you may not instanciate
 * the base class Image_Tools directly.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_INSTANCIATION_FAILED
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_INSTANCIATION_FORBIDEN', -3);

/**
 * Image_Tools error, indicating that the given option was of a wrong type.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_OPTION_INVALID
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_OPTION_INVALID', -4);

/**
 * Image_Tools error, indicating that the given option is not
 * supported by the tool.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_OPTION_UNSUPPORTED
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_OPTION_UNSUPPORTED', -5);

/**
 * Image_Tools error, indicating that the given method is not
 * supported by the tool.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_METHOD_UNSUPPORTED
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_METHOD_UNSUPPORTED', -6);

/**
 * Image_Tools error, indicating that the given option is not
 * set.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_OPTION_NOTSET
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_OPTION_NOTSET', -7);

/**
 * Image_Tools error, indicating that the HTTP headers have been sent
 * before the display() method was called. Ensure that no output started
 * before that.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_HEADERSEND_FAILED
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_HEADERSEND_FAILED', -8);

/**
 * Image_Tools error, indicating that the image type you selected for
 * displaying the image is not supported. Please use a supported image
 * type.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_IMAGETYPE_UNSUPPORTED
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_IMAGETYPE_UNSUPPORTED', -9);

/**
 * Image_Tools error, indicating that the image could not be saved.
 * Check the permissions on the desired path first.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_SAVEIMAGE_FAILED
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_SAVEIMAGE_FAILED', -10);

/**
 * Image_Tools error, indicating that you called a static method non
 * statically, which may only be called statically.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_NONSTATIC_FAILED
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_NONSTATIC_FAILED', -11);

/**
 * Image_Tools error, indicating that you called a static method
 * statically, which may only be called non statically.
 *
 * @name    IMAGE_TOOLS_BASE_ERR_STATIC_FAILED
 * @access  public
 */
define('IMAGE_TOOLS_BASE_ERR_STATIC_FAILED', -12);

// }}}
// {{{ Class: Image_Tools

/**
 * This is the Image_Tools base class
 * Every image-tool has to derive from this class and implement several methods
 * itself. Other methods are implemented in the base class directly, but may
 * only be called on the sub-classes. The only method being called directly from
 * the base class is the factory() method.
 *
 * @category    Images
 * @package     Image_Tools
 * @author      Tobias Schlitt <toby@php.net>
 * @author      Firman Wandayandi <firman@php.net>
 * @copyright   Copyright (c) 2003-2006
 *                Tobias Schlitt <toby@php.net>,
 *                Firman Wandayandi <firman@php.net>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 *              BSD License
 * @version     Release: 1.0.0RC1
 */
class Image_Tools
{
    // {{{ Properties

    /**
     * Contain the options inside all subclasses.
     *
     * @var array
     * @access protected
     */
    var $options = array();

    /**
     * Has to contain all available options of a subclass. The index of
     * the array is the option name, the value is either a PHP variable
     * type (e.g. int, string, float, resource,...) or a class name
     * (e.g. Image_Tools, DB,...). Also allowed the are the special values
     * 'number' and 'mixed'. The structure of that array is as follows:
     *
     * $availableOptions = array(
     *      'foo'   =>  'int',
     *      'bar'   =>  'float',
     *      'db'    =>  'DB',
     *      'img'   =>  'resource'
     * );
     *
     * @var array
     * @access protected
     */
    var $availableOptions = array();

    /**
     * Has to contain all available methods (as the keys of the array)
     * and the expected parameters (in an array) as the values. The structure
     * of this array is as follows:
     *
     * $availableMethods = array(
     *      'myMethod'  =>  array(
     *          'foo'   =>  'int',
     *          'db'    =>  'DB'
     *      'fooBar'    =>  array(
     *          'baz'   =>  'resource',
     *          'bun'   =>  'mixed'
     *      )
     * );
     *
     * @var array
     * @access protected
     */
    var $availableMethods = array();

    /**
     * Contains the version of the specific subclass. A version has to follow the PEAR
     * version naming standard, which can be found here:
     * http://pear.php.net/group/docs/20040226-vn.php
     *
     * @var string
     * @access protected
     */
    var $version = '';

    /**
     * Contains the api-version of the baseclass. The API version has to follow the
     * PEAR version naming standard.
     *
     * @var string
     * @access protected
     */
    var $apiVersion = '1.0';

    /**
     * Result rendered image.
     *
     * @var resource
     * @access protected
     */
    var $resultImage = null;

    // }}}
    // {{{ Constructor

    /**
     * Constructor
     *
     * this method has no purpose for Image_Tools, but defines the API for
     * all base classes. Calling this directly with $foo = new Image_Tools(...);
     * will cause an error.
     *
     * @param   array $options optional Options.
     * @access  protected
     * @see     IMAGE_TOOLS_BASE_ERR_INSTANCIATION_FORBIDEN
     */
    function Image_Tools($options = array())
    {
        PEAR::setErrorHandling(PEAR_ERROR_TRIGGER);
        if (is_a($this, 'Image_Tools') && !is_subclass_of($this, 'Image_Tools')) {
            PEAR::raiseError('Cannot instanciate Image_Tools directly',
                             IMAGE_TOOLS_BASE_ERR_INSTANCIATION_FORBIDEN);
        }

        $this->set($options);
        PEAR::setErrorHandling(PEAR_ERROR_RETURN);
    }

    // }}}
    // {{{ factory()

    /**
     * Create a new instance of an image tool
     *
     * This method will create a new image tool, defined by the $tool var,
     * which is a string it will call the constructor of the specific tool
     * and return it (if no error occurs)
     *
     * @param string $tool Tool name
     * @param array $options optional Options
     *
     * @return  object New image tool on success or PEAR_Error on failure
     * @access  public
     * @static
     * @see     IMAGE_TOOLS_BASE_ERR_INSTANCIATION_FAILED,
     *          IMAGE_TOOLS_BASE_ERR_CLASS_INVALID,
     *          IMAGE_TOOLS_BASE_ERR_WRONGOPTIONTYPE,
     *          IMAGE_TOOLS_BASE_ERR_OPTION_UNSUPPORTED
     */
    function &factory($tool, $options = array())
    {
        if (isset($this) && strtolower(get_class($this)) == 'image_tools') {
            return PEAR::raiseError('This method may only be called statically',
                                    IMAGE_TOOLS_BASE_ERR_NONSTATIC_FAILED);
        }

        // Renice the tool name
        $toolParts = @explode('_', $tool);
        if (empty($toolParts) || !is_array($toolParts)) {
            $toolParts[] = $tool;
        }

        foreach ($toolParts as $key => $value) {
            $toolParts[$key] = ucfirst($value);
        }
        $tool = implode('_', $toolParts);
        $toolPath = 'Image/Tools/'.$tool.'.php';

        // Include the tool class
        require_once($toolPath);

        $className = "Image_Tools_${tool}";
        if (!Pimcore_Tool::classExists($className)) {
            return PEAR::raiseError('File not found ' . $toolPath .
                                    ' or undefined class '.$className,
                                    IMAGE_TOOLS_BASE_ERR_CLASS_INVALID);
        }

        @$obj =& new $className($options);
        if (!is_object($obj) || !is_a($obj, $className)) {
            return PEAR::raiseError('Could not instanciate image tool '.$className,
                                    IMAGE_TOOLS_BASE_ERR_INSTANCIATION_FAILED);
        }
        $res = $obj->set($options);
        if (PEAR::isError($res)) {
            return $res;
        }
        return $obj;
    }

    // }}}
    // {{{ set()

    /**
     * Set the option(s)
     * Set a single or multiple options. The parameter of that method may
     * either be a single options array or a key/value pair setting a single
     * option. This method has not to be reimplemented by the Image_Tools
     * sub classes.
     *
     * @param mixed $option A single option name or the options array
     * @param mixed $value Option value if $option is string
     *
     * @return  bool|PEAR_Error TRUE on success or PEAR_Error on failure
     * @access  protected
     * @see     IMAGE_TOOLS_BASE_ERR_WRONGOPTIONTYPE,
     *          IMAGE_TOOLS_BASE_ERR_OPTION_UNSUPPORTED
     */
    function set($option, $value = null)
    {
        if (!isset($this)) {
            return PEAR::raiseError('This method may only be called non statically',
                                    IMAGE_TOOLS_BASE_ERR_STATIC_FAILED);
        }
        if (is_array($option)) {
            foreach ($option as $key => $value) {
                $res = $this->set($key, @$value);
                if (PEAR::isError($res)) {
                    return $res;
                }
            }
            return true;
        }
        $res = $this->isValidOption($option, @$value);
        if (PEAR::isError($res)) {
            return $res;
        }
        $this->options[$option] = $value;
        return true;
    }

    // }}}
    // {{{ isValidOption()

    /**
     * Has the option a valid value?
     * Determines, if the value given is valid for the option.
     *
     * @param mixed $option A single option name
     * @param mixed $value Option value
     *
     * @return  bool|PEAR_Error TRUE on acceptans or PEAR_Error on failure
     * @access  protected
     * @see     IMAGE_TOOLS_BASE_ERR_WRONGOPTIONTYPE,
     *          IMAGE_TOOLS_BASE_ERR_OPTION_UNSUPPORTED
     */
    function isValidOption($name, $value)
    {
        $res = $this->supportsOption($name);
        if (PEAR::isError($res)) {
            return $res;
        }
        $type = $this->availableOptions[$name];
        switch ($type) {
            case 'mixed':
                return true;
                break;
            case 'bool':
            case 'int':
            case 'integer':
            case 'float':
            case 'double':
            case 'string':
            case 'array':
            case 'resource':
            case 'object':
                $typeCheck = 'is_'.$type;
                if ($typeCheck($value)) {
                    return true;
                }
                break;
            case 'number':
                if (is_int($value) || is_float($value)) {
                    return true;
                }
                break;
            default:
                if (is_a($value, $type)) {
                    return true;
                }
                break;
        }
        return PEAR::raiseError('Wrong type for option ' . $name .
                                '. Requires '. $type .', but is ' . gettype($value),
                                IMAGE_TOOLS_BASE_ERR_OPTION_INVALID);
    }

    // }}}
    // {{{ get()

    /**
     * Get the value of the option
     *
     * returns the value of an option of, an error if the option is not
     * available.
     *
     * @param string $option Option name.
     *
     * @return  mixed|PEAR_Error Option value on success or
     *                           PEAR_Error on failure.
     * @access  public
     * @see     IMAGE_TOOLS_BASE_ERR_OPTION_UNSUPPORTED,
     *          IMAGE_TOOLS_BASE_ERR_OPTION_NOTSET,
     *          IMAGE_TOOLS_BASE_ERR_STATIC_FAILED
     */
    function get($option)
    {
        if (!isset($this)) {
            return PEAR::raiseError('This method may only be called non statically',
                                    IMAGE_TOOLS_BASE_ERR_STATIC_FAILED);
        }
        $res = $this->supportsOption($option);
        if (PEAR::isError($res)) {
            return $res;
        }
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
        return PEAR::raiseError('Option '.$option.' not set',
                                IMAGE_TOOLS_BASE_ERR_OPTION_NOTSET);
    }

    // }}}
    // {{{ createImageFromFile()

    /**
     * Create a GD image resource from file (JPEG, PNG, WBMP and XBM support).
     *
     * @param string $filename The image filename.
     *
     * @return mixed GD image resource on success, PEAR_Error on failure.
     * @access public
     * @static
     */
    function createImageFromFile($filename)
    {
        if (!is_file($filename) || !is_readable($filename)) {
            return PEAR::raiseError('Unable to open file "' . $filename . '"');
        }

        // determine image format
        list( , , $imgType) = getimagesize($filename);

        switch ($imgType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filename);
                break;
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filename);
                break;
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filename);
                break;
            case IMAGETYPE_WBMP:
                return imagecreatefromwbmp($filename);
                break;
            case IMAGETYPE_XBM:
                return imagecreatefromxbm($filename);
                break;
            default:
                return PEAR::raiseError('Unsupport image type');
        }
    }

    // }}}
    // {{{ createImageFromString()

    /**
     * Create a GD image resource from a string data.
     *
     * @param string $data The string image data.
     *
     * @return mixed GD image resource on success, PEAR_Error on failure.
     * @access public
     * @static
     */
    function createImageFromString($data)
    {
        if (!is_string($data) || empty($data)) {
            PEAR::raiseError('Invalid data value.');
        }

        $img = imagecreatefromstring($data);
        if ($img === false) {
            return PEAR::raiseError('Failed to create image from string data');
        }
        return $img;
    }

    // }}}
    // {{{ createImage()

    /**
     * Create a GD image resource from given input.
     *
     * This method tried to detect what the input, if it is a file the
     * createImageFromFile will be called, otherwise createImageFromString().
     *
     * @param   mixed $input The input for creating an image resource. The value
     *                       may a string of filename, string of image data or
     *                       GD image resource.
     *
     * @return  resource|PEAR_Error An GD image resource on success or
     *                              PEAR_Error on failure.
     * @access  public
     * @see     createImageFromFile()
     * @see     createImageFromString()
     */
    function createImage($input)
    {
        if (is_file($input)) {
            return Image_Tools::createImageFromFile($input);
        } else if (is_string($input)) {
            return Image_Tools::createImageFromString($input);
        } else if (Image_Tools::isGDImageResource($input)) {
            return $input;
        }
        return PEAR::raiseError('Invalid source image given, valid to create image resource.');
    }

    // }}}
    // {{{ isGDImageResource()

    /**
     * Find the whether a value is the GD image resource or not.
     *
     * @param mixed $value Value to evaluate.
     *
     * @return bool TRUE if a value is the GD image resource, otherwise FALSE.
     * @access public
     * @static
     */
    function isGDImageResource($value)
    {
        if (is_resource($value) && get_resource_type($value) == 'gd') {
            return true;
        }
        return false;
    }

    // }}}
    // {{{ preRender()

    /**
     * Function which called before render.
     *
     * Use this method to place any routine before calling render(). This
     * method is optional to extended and returns TRUE as default.
     *
     * @return true|PEAR_Error
     * @access protected
     * @see getResultImage()
     * @since Method available since Release 1.0.0RC1
     */
    function preRender()
    {
        return true;
    }

    // }}}
    // {{{ postRender()

    /**
     * Function which called after render.
     *
     * Use this method to place any routine after calling render(). This
     * method is optional to extended and return TRUE as default.
     *
     * @return true|PEAR_Error
     * @access protected
     * @see getResultImage()
     * @since Method available since Release 1.0.0RC1
     */
    function postRender()
    {
        return true;
    }
    // }}}
    // {{{ render()

    /**
     * Render the result of a tool to the given image
     *
     * Since that's the purpose of all image tools, this method should stay
     * with it's api as is. The rendering itself must be tool specific. The
     * method gets a GD2 image as it's parameter. The creation of images inside
     * Image_Tools is not supported.
     *
     * @access  protected
     * @see     IMAGE_TOOLS_BASE_ERR_STATIC_FAILED
     */
    function render()
    {
        if (!isset($this)) {
            return PEAR::raiseError('This method may only be called non statically',
                                    IMAGE_TOOLS_BASE_ERR_STATIC_FAILED);
        }
    }

    // }}}
    // {{{ _render

    /**
     * Helper function to combine calls between pre render, render and
     * post render.
     *
     * @return TRUE|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access private
     * @see preRender()
     * @see postRender()
     * @see render()
     * @see getImageResult()
     * @since Method available since Release 1.0.0RC1
     */
    function _render()
    {
        if (!isset($this)) {
            return PEAR::raiseError('This method may only be called non statically',
                                    IMAGE_TOOLS_BASE_ERR_STATIC_FAILED);
        }

        // calls the pre render routine
        $res = $this->preRender();
        if (PEAR::isError($res)) {
            return $res;
        }

        // calls the extended render function
        $res = $this->render();
        if (PEAR::isError($res)) {
            return $res;
        }

        // calls the post render routine
        $res = $this->postRender();
        if (PEAR::isError($res)) {
            return $res;
        }

        return true;
    }

    // }}}
    // {{{ getResultImage()

    /**
     * Get rendered image.
     *
     * @param boolean $force Force render or not, whether the image should be
     *                       re-render or leave the one if its already rendered
     *
     * @return resource The GD image resource of rendered result.
     * @access public
     */
    function getResultImage($force = false)
    {
        if (!Image_Tools::isGDImageResource($this->resultImage) || $force) {
            $res = $this->_render();
            if (PEAR::isError($res)) {
                return $res;
            }
        }
        return $this->resultImage;
    }

    // }}}
    // {{{ display()

    /**
     * Display rendered image (send it to browser).
     * This method is a common implementation to render and output an image.
     * The method calls the render() method automatically and outputs the
     * image to the browser.
     *
     * @param   int $type optional Type of format image (use defined image
     *                             type constants), default is IMAGETYPE_PNG.
     * @param boolean $force Force render or not, whether the image should be
     *                       re-render or leave the one if its already rendered
     *
     * @return  bool|PEAR_Error TRUE on success or PEAR_Error on failure.
     * @access  public
     * @see     IMAGE_TOOLS_BASE_ERR_HEADERSEND_FAILED,
     *          IMAGE_TOOLS_BASE_ERR_IMAGETYPE_UNSUPPORTED
     */
    function display($type = IMAGETYPE_PNG, $force = false)
    {
        $res = $this->getResultImage($force);
        if (PEAR::isError($res)) {
            return $res;
        }

        if (!Image_Tools::isGDImageResource($this->resultImage)) {
            return PEAR::raiseError('Image_Tools::$resultImage is not an image resource');
        }

        $ctype = image_type_to_mime_type($type);
        if (!headers_sent()) {
            header('Content-Type: ' . $ctype);
        } else {
            return PEAR::raiseError('Headers have already been sent. Could not display image.',
                                    IMAGE_TOOLS_BASE_ERR_HEADERSEND_FAILED);
        }

        switch ($type) {
            case IMAGETYPE_GIF:
                imagegif($this->resultImage);
                break;
            case IMAGETYPE_PNG:
                imagepng($this->resultImage);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($this->resultImage);
                break;
            case IMAGETYPE_WBMP:
                imagewbmp($this->resultImage);
                break;
            default:
                return PEAR::raiseError('Image type '.$ctype.' not supported by PHP',
                                        IMAGE_TOOLS_BASE_ERR_IMAGETYPE_UNSUPPORTED);
                break;
        }
        return true;
    }

    // }}}
    // {{{ save()

    /**
     * Save rendered image to a file.
     *
     * @param string $path Path to destination filename.
     * @param int $type optional Type of image format (use defined image type
     *                           constants), default is IMAGETYPE_PNG.
     * @param boolean $force Force render or not, whether the image should be
     *                       re-render or leave the one if its already rendered
     *
     * @return  bool|PEAR_Error TRUE on success, PEAR_Error on failure.
     * @access  public
     * @see     IMAGE_TOOLS_BASE_ERR_HEADERSEND_FAILED,
                IMAGE_TOOLS_BASE_ERR_IMAGETYPE_UNSUPPORTED
     */
    function save($path, $type = IMAGETYPE_PNG, $force = false)
    {
        $res = $this->getResultImage($force);
        if (PEAR::isError($res)) {
            return $res;
        }

        if (!Image_Tools::isGDImageResource($this->resultImage)) {
            return PEAR::raiseError('Image_Tools::$resultImage is not an image resource');
        }

        switch ($type) {
            case IMAGETYPE_GIF:
                $res = imagegif($this->resultImage, $path);
                break;
            case IMAGETYPE_PNG:
                $res = imagepng($this->resultImage, $path);
                break;
            case IMAGETYPE_JPEG:
                $res = imagejpeg($this->resultImage, $path);
                break;
            case IMAGETYPE_WBMP:
                $res = imagewbmp($this->resultImage, $path);
                break;
            default:
                return PEAR::raiseError('Image type '.$ctype.' not supported by PHP',
                                        IMAGE_TOOLS_BASE_ERR_IMAGETYPE_UNSUPPORTED);
                break;
        }

        if (!$res) {
            PEAR::raiseError('Saving image '.$path.' failed',
                             IMAGE_TOOLS_BASE_ERR_SAVEIMAGE_FAILED);
        }
        return true;
    }

    // }}}
    // {{{ supportsOption()

    /**
     * Find out the whether a subclass supports the option
     *
     * @param string $option Option name.
     *
     * @return  bool TRUE if supported, otherwise FALSE
     * @access  protected
     * @see     IMAGE_TOOLS_BASE_ERR_OPTION_UNSUPPORTED,
     *          IMAGE_TOOLS_BASE_ERR_STATIC_FAILED
     */
    function supportsOption($option)
    {
        if (!isset($this)) {
            return PEAR::raiseError('This method may only be called non statically',
                                    IMAGE_TOOLS_BASE_ERR_STATIC_FAILED);
        }
        if (isset($this->availableOptions[$option])) {
            return true;
        }
        return PEAR::raiseError('Unsupported option '.$option,
                                IMAGE_TOOLS_BASE_ERR_OPTION_UNSUPPORTED);
    }

    // }}}
    // {{{ availableOptions()

    /**
     * Get all available options of the subclass
     *
     * @return  array Available options.
     * @access  protected
     * @see     IMAGE_TOOLS_BASE_ERR_STATIC_FAILED
     */
    function availableOptions()
    {
        if (!isset($this)) {
            return PEAR::raiseError('This method may only be called non statically',
                                    IMAGE_TOOLS_BASE_ERR_STATIC_FAILED);
        }
        return $this->availableOptions;
    }

    // }}}
    // {{{ supportsMethod()

    /**
     * Get the method parameters
     *
     * @param   string $methodName Method name.
     *
     * @return  array|bool array of the parameters a method expects or
     *                     FALSE if the method is not available.
     * @access  protected
     * @see     IMAGE_TOOLS_BASE_ERR_STATIC_FAILED
     */
    function supportsMethod($methodName)
    {
        if (!isset($this)) {
            return PEAR::raiseError('This method may only be called non statically',
                                    IMAGE_TOOLS_BASE_ERR_STATIC_FAILED);
        }
        if (isset($this->availableMethods[$methodName])) {
            return $this->availableMethods[$methodName];
        }
        return PEAR::raiseError('Unsupported method '.$methodName,
                                IMAGE_TOOLS_BASE_ERR_METHOD_UNSUPPORTED);
    }

    // }}}
    // {{{ availableMethods()

    /**
     * Get available methods
     *
     * returns an array where the keys are the method names available
     * and the value for each key is an array containing the
     * parameter the specific method expects.
     *
     * @return  array Available methods.
     * @access  protected
     * @see     IMAGE_TOOLS_BASE_ERR_STATIC_FAILED
     */
    function availableMethods()
    {
        if (!isset($this)) {
            return PEAR::raiseError('This method may only be called non statically',
                                    IMAGE_TOOLS_BASE_ERR_STATIC_FAILED);
        }
        return $this->availableMethods;
    }

    // }}}
    // {{{ getAPIVersion()

    /**
     * Get the API version of the common base
     *
     * This methods can be called statically using Image_Tools::getAPIVersion() or
     * from the subclass e.g Image_Tools_Border::getAPIVersion() or
     * $border->getAPIVersion()
     *
     * @return string Image_Tools base class api-version
     * @access protected
     */
    function getAPIVersion()
    {
        if (isset($this)) {
            return $this->apiVersion;
        } else {
            $obj = new Image_Tools;
            return $obj->getAPIVersion();
        }
    }

    // }}}
    // {{{ getVersion()

    /**
     * Get the subclass version.
     *
     * @returns string Version.
     * @access protected
     */
    function getVersion()
    {
        return $this->version;
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
