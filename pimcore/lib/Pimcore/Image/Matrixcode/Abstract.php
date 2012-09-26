<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to version 1.0 of the Zend Framework
 * license, that is bundled with this package in the file LICENSE.txt, and
 * is available through the world-wide-web at the following URL:
 * http://framework.zend.com/license/new-bsd. If you did not receive
 * a copy of the Zend Framework license and are unable to obtain it
 * through the world-wide-web, please send a note to license@zend.com
 * so we can mail you a copy immediately.
 *
 * Renamed from Zend_Matrixcode to Pimcore_Image_Matrixcode for compatibility reasons
 * @copyright  Copyright (c) 2009-2011 Peter Minne <peter@inthepocket.mobi>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */



/**
 * Pimcore_Image_Matrixcode_Abstract
 *
 * Renamed from Zend_Matrixcode to Pimcore_Image_Matrixcode for compatibility reasons
 * @copyright  Copyright (c) 2009-2011 Peter Minne <peter@inthepocket.mobi>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Pimcore_Image_Matrixcode_Abstract
{

	/**
     * Namespace of the matrixcode for autoloading
     * @var string
     */
    protected $_matrixcodeNamespace = 'Pimcore_Image_Matrixcode';
    
    /**
     * Matrixcode type
     * @var string
     */
    protected $_type = null;
	
    /**
     * Size (in units) of a single matrixcode module (the 'black dots')
     * @var array array(width, height)
     */
    protected $_module_size = array(1,1);
    
    /**
     * Calculated width of the code
     * @var int
     */
    protected $_calculated_width;
    
    /**
     * Calculated height of the code
     * @var int
     */
    protected $_calculated_height;

    /**
     * Symbol color (fore color)
     * @var integer
     */
    protected $_fr_color = 0x000000;

    /**
     * Background color of the code (transparent if empty)
     * @var integer
     */
    protected $_bg_color;

    /**
     * Activate/deactivate border of the image
     * @var boolean
     */
    protected $_with_border = false;

    /**
     * Spacing between code and image borders. A value of '1' means a padding equal to the size of one module.
     * @var array array(top, right, bottom, left)
     */
    protected $_padding = array(1 , 1 , 1 , 1);

    /**
     * Text to encode
     * @var string
     */
    protected $_text = null;
    
    /**
     * Array containing the modules 
     * (2 dimensional array representing the 2 dimensional code)
     * @var array
     */
    protected $_matrix_table = null;

   
    
    
    /**
     * Constructor
     * @param array | Zend_Config $options 
     * @return void
     */
    public function __construct ($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif ($options instanceof Zend_Config) {
            $this->setConfig($options);
        }
        $this->_type = strtolower(substr(get_class($this), strlen($this->_matrixcodeNamespace) + 1));
    }

    
    /**
     * Set matrixcode state from options array
     * @param Zend_Config $config
     * @return Pimcore_Image_Matrixcode_Abstract
     */
    public function setOptions($options)
    {
    	foreach ($options as $key => $value) {
            $normalized = ucfirst($key);
            $method = 'set' . $normalized;
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }
    
   
	/**
     * Set matrixcode state from config object
     * @param Zend_Config $config
     * @return Pimcore_Image_Matrixcode_Abstract
     */
    public function setConfig(Zend_Config $config)
    {
        return $this->setOptions($config->toArray());
    }
    
    
	/**
     * Retrieve type of matrixcode
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }
    
    
    /**
     * Set module size
     * @param int | array $size
     */
    public function setModuleSize($value)
    {    	
    	if(is_array($value) && count($value) == 2) {
    		$this->_module_size = $value;
    	}else if(is_numeric($value)){
    		$this->_module_size = array($value,$value);
    	}else{
            throw new Pimcore_Image_Matrixcode_Exception(
                'Invalid module size'
            );
    	}	
    	return $this;
    }
    
    
    /**
     * Retrieve module size
     * @return array
     */
    public function getModuleSize()
    {
    	return $this->_module_size;
    }
    
    
	/**
     * Set color of the code
     * @param string $value
     * @return Pimcore_Image_Matrixcode_Abstract
     * @throw Pimcore_Image_Matrixcode_Exception
     */
    public function setForeColor($value)
    {
        if (preg_match('`\#[0-9A-Fa-f]{6}`', $value)) {
            $this->_fr_color = hexdec($value);
        } elseif (is_numeric($value) && $value >= 0 && $value <= 16777125) {
            $this->_fr_color = intval($value);
        } else {
            throw new Pimcore_Image_Matrixcode_Exception(
                'Fore color must be set as #[0-9A-Fa-f]{6}'
            );
        }
        return $this;
    }

    /**
     * Retrieve color of the code
     * @return string
     */
    public function getForeColor()
    {
        return $this->_fr_color;
    }

    /**
     * Set the color of the background
     * @param integer $value
     * @return Pimcore_Image_Matrixcode_Abstract
     * @throw Pimcore_Image_Matrixcode_Exception
     */
    public function setBackgroundColor($value)
    {
        if (preg_match('`\#[0-9A-F]{6}`', $value)) {
            $this->_bg_color = hexdec($value);
        } elseif (is_numeric($value) && $value >= 0 && $value <= 16777125) {
            $this->_bg_color = intval($value);
        } else {
            throw new Pimcore_Image_Matrixcode_Exception(
                'Background color must be set as #[0-9A-F]{6}'
            );
        }
        return $this;
    }

    /**
     * Retrieve background color
     * @return integer
     */
    public function getBackgroundColor()
    {
        return $this->_bg_color;
    }

    /**
     * Activate/deactivate drawing of a border
     * @param boolean $value
     * @return Pimcore_Image_Matrixcode_Abstract
     */
    public function setWithBorder($value)
    {
        $this->_withBorder = (bool) $value;
        return $this;
    }

    
    /**
     * Return if border needs to be drawn or not
     * @return boolean
     */
    public function getWithBorder()
    {
        return $this->_withBorder;
    }
    
    /**
     * Set the padding
     * @param float | array $value
     * @return Pimcore_Image_Matrixcode_Abstract
     */
    public function setPadding($value)
    {
    	if(is_array($value) && count($value) == 4) {
    		$this->_padding = $value;
    	}else if(is_int($value)){
    		$this->_padding = array($value,$value,$value,$value);
    	}else{
            throw new Pimcore_Image_Matrixcode_Exception(
                'Invalid padding value'
            );
    	}
    	return $this;
    }
    
    /**
     * Retrieve the padding
     * @return array
     */
    public function getPadding()
    {
    	return $this->_padding;
    }
    
    
	/**
     * Set text to encode
     * @param string $value
     * @return Pimcore_Image_Matrixcode_Abstract
     */
    public function setText($value)
    {
        $this->_text = trim($value);
        return $this;
    }

    /**
     * Retrieve text to encode
     * @return string
     */
    public function getText()
    {
        return $this->_text;
    }
    
    /**
     * Set the calculated width
     * @param float $width
     */
    protected function _setCalculatedWidth($value)
    {
    	$this->_calculated_width = $value;
    }
    
	/**
     * Set the calculated height
     * @param float $height
     */
    protected function _setCalculatedHeight($value)
    {
    	$this->_calculated_height = $value;
    }
    
    /**
     * Retrieve the calculated width of the code
     * @return float
     */
    public function getWidth()
    {
    	return $this->_calculated_width;
    }
    
    /**
     * Retrieve the calculated height of the code
     * @return float
     */
    public function getHeight()
    {
    	return $this->_calculated_height;
    }
    
    /**
     * Retrieve the matrix
     * @return array
     */
    public function getMatrix()
    {
    	return $this->_matrix_table;
    }
    

    /**
     * Complete drawing of the matrixcode
     * @return resource
     */
    public function draw ()
    {
        $this->_checkParams();
        $this->_matrix_table = $this->_prepareMatrixcode();
    }
    

    /**
     * Checking of parameters after all settings
     *
     * @return void
     */
    abstract protected function _checkParams();
    
    
    /**
     * Method that prepares the matrix
     * @return array
     */
    abstract protected function _prepareMatrixcode();

}