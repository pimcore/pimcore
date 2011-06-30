<?php
/**
 * This file contains the ezcImageGdHandler class.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 * @access public
 */

/**
 * Options class for ezcImageHandler->save() methods.
 * 
 * @property int $compression
 *           The compression level to use, if compression is supported by the
 *           target format (e.g. TIFF). A value between 0 and 9 (incl.) is
 *           expected.
 * @property int $quality A quality indicator used to determine the quality of
 *           the target image, if supported by the target format (e.g. JPEG). A
 *           value between 0 and 100 (incl.) is expected.
 * @property array(int) $transparencyReplacementColor
 *           Only certain image formats support transparent backgrounds (e.g.
 *           GIF and PNG). If such images are converted to a format that does
 *           not support transparency, this color will be used as the new
 *           background. The color value is given as an array of integers, each
 *           representing a color value in RGB between 0 and 255.
 *           <code>array( 255, 0, 0 )</code> for example would be pure red.
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageSaveOptions extends ezcBaseOptions
{
    /**
     * Properties.
     * 
     * @var array(string=>mixed)
     */
    protected $properties = array(
        "compression"                  => null,
        "quality"                      => null,
        "transparencyReplacementColor" => null,
    );

    /**
     * Property set access.
     * 
     * @param string $propertyName 
     * @param string $propertyValue 
     * @ignore
     * @return void
     */
    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case "compression":
                if ( ( !is_int( $propertyValue ) || $propertyValue < 0 || $propertyValue > 9 ) && $propertyValue !== null )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, "int > 0 and < 10" );
                }
                break;
            case "quality":
                if ( ( !is_int( $propertyValue ) || $propertyValue < 0 || $propertyValue > 100 ) && $propertyValue !== null )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, "int > 0 and <= 100" );
                }
                break;
            case "transparencyReplacementColor":
                if ( ( !is_array( $propertyValue ) || count( $propertyValue ) < 3 || !isset( $propertyValue[0] ) || !isset( $propertyValue[1] ) || !isset( $propertyValue[2] ) ) && $propertyValue !== null )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, "array(int)" );
                }
                break;
            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
        }
        $this->properties[$propertyName] = $propertyValue;
    }
}

?>
