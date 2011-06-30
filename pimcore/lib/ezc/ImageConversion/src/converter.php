<?php
/**
 * File containing the ezcImageConverter class.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Class to manage conversion and filtering of image files.
 * This class is highly recommended to be used with an external
 * singleton pattern to have just 1 converter in place over the whole
 * application.
 *
 * As back-ends 2 handler classes are available, of which at least 1 has to be
 * configured during the instantiation of the ezcImageConverter. Both handlers
 * utilize different image manipulation tools and are capable of different
 * sets of filters:
 *
 * <ul>
 * <li>ezcImageGdHandler
 *  <ul>
 *  <li>Uses PHP's GD extension for image manipulation.</li>
 *  <li>Implements the following filter interfaces
 *   <ul>
 *   <li>{@link ezcImageGeometryFilters}</li>
 *   <li>{@link ezcImageColorspaceFilters}</li>
 *   </ul>
 *  </li>
 *  </ul>
 * </li>
 * <li>ezcImageImagemagickHandler
 *  <ul>
 *  <li>Uses the external "convert" program, contained in ImageMagick</li>
 *  <li>Implements the following interfaces:
 *   <ul>
 *   <li>{@link ezcImageGeometryFilters}</li>
 *   <li>{@link ezcImageColorspaceFilters}</li>
 *   <li>{@link ezcImageEffectFilters}</li>
 *   </ul>
 *  </li>
 *  </ul>
 * </li>
 * </ul>
 *
 * A general example, how to use ezcImageConversion to convert images:
 * <code>
 * // Prepare settings for ezcImageConverter
 * // Defines the handlers to utilize and auto conversions.
 * $settings = new ezcImageConverterSettings(
 *     array(
 *         new ezcImageHandlerSettings( 'GD',          'ezcImageGdHandler' ),
 *         new ezcImageHandlerSettings( 'ImageMagick', 'ezcImageImagemagickHandler' ),
 *     ),
 *     array(
 *         'image/gif' => 'image/png',
 *         'image/bmp' => 'image/jpeg',
 *     )
 * );
 * 
 * // Create the converter itself.
 * $converter = new ezcImageConverter( $settings );
 * 
 * // Define a transformation
 * $filters = array(
 *     new ezcImageFilter(
 *         'scaleWidth',
 *         array(
 *             'width'     => 100,
 *             'direction' => ezcImageGeometryFilters::SCALE_BOTH,
 *         )
 *     ),
 *     new ezcImageFilter(
 *         'colorspace',
 *         array(
 *             'space' => ezcImageColorspaceFilters::COLORSPACE_GREY,
 *         )
 *     ),
 * );
 * 
 * // Which MIME types the conversion may output
 * $mimeTypes = array( 'image/jpeg', 'image/png' );
 * 
 * // Create the transformation inside the manager
 * $converter->createTransformation( 'thumbnail', $filters, $mimeTypes );
 * 
 * // Transform an image.
 * $converter->transform( 'thumbnail', dirname(__FILE__).'/jpeg.jpg', dirname(__FILE__).'/jpeg_thumb.jpg' );
 * </code>
 *
 * It's recommended to create only a single ezcImageConverter instance in your 
 * application to avoid creating multiple instances of it's internal objects. 
 * You can implement a singleton pattern for this, which might look similar to
 * the following example:
 * <code>
 * function getImageConverterInstance()
 * {
 *     if ( !isset( $GLOBALS['_ezcImageConverterInstance'] ) )
 *     {
 *         // Prepare settings for ezcImageConverter
 *         // Defines the handlers to utilize and auto conversions.
 *         $settings = new ezcImageConverterSettings(
 *             array(
 *                 new ezcImageHandlerSettings( 'GD',          'ezcImageGdHandler' ),
 *                 new ezcImageHandlerSettings( 'ImageMagick', 'ezcImageImagemagickHandler' ),
 *             ),
 *             array(
 *                 'image/gif' => 'image/png',
 *                 'image/bmp' => 'image/jpeg',
 *             )
 *         );
 * 
 * 
 *         // Create the converter itself.
 *         $converter = new ezcImageConverter( $settings );
 * 
 *         // Define a transformation
 *         $filters = array(
 *             new ezcImageFilter(
 *                 'scale',
 *                 array(
 *                     'width'     => 100,
 *                     'height'    => 300,
 *                     'direction' => ezcImageGeometryFilters::SCALE_BOTH,
 *                 )
 *             ),
 *             new ezcImageFilter(
 *                 'colorspace',
 *                 array(
 *                     'space' => ezcImageColorspaceFilters::COLORSPACE_SEPIA,
 *                 )
 *             ),
 *             new ezcImageFilter(
 *                 'border',
 *                 array(
 *                     'width' => 5,
 *                     'color' => array(255, 0, 0),
 *                 )
 *             ),
 *         );
 * 
 *         // Which MIME types the conversion may output
 *         $mimeTypes = array( 'image/jpeg', 'image/png' );
 * 
 *         // Create the transformation inside the manager
 *         $converter->createTransformation( 'funny', $filters, $mimeTypes );
 * 
 *         // Assign singleton instance
 *         $GLOBALS['_ezcImageConverterInstance'] = $converter;
 *     }
 * 
 *     // Return singleton instance
 *     return $GLOBALS['_ezcImageConverterInstance'];
 * }
 * 
 * // ...
 * // Somewhere else in the code...
 * // Transform an image.
 * getImageConverterInstance()->transform( 'funny', dirname(__FILE__).'/jpeg.jpg', dirname(__FILE__).'/jpeg_singleton.jpg' );
 * 
 * </code>
 *
 * @see ezcImageHandler
 * @see ezcImageTransformation
 *
 * @package ImageConversion
 * @version 1.3.8
 * @mainclass
 */
class ezcImageConverter
{
    /**
     * Manager settings
     * Settings basis for all image manipulations.
     *
     * @var ezcImageConverterSettings
     */
    protected $settings;

    /**
     * Keeps the handlers used by the converter.
     *
     * @var array(ezcImageHandler)
     */
    protected $handlers = array();

    /**
     * Stores transformation registered with this converter.
     *
     * @var array
     */
    protected $transformations = array();

    /**
     * Initialize converter with settings object.
     * The ezcImageConverter can be directly instantiated, but it's
     * highly recommended to use a manual singleton implementation
     * to have just 1 instance of a ezcImageConverter per Request.
     *
     * ATTENTION: The ezcImageConverter does not support animated
     * GIFs. Animated GIFs will simply be ignored by all filters and
     * conversions.
     *
     * @param ezcImageConverterSettings $settings Settings for the converter.
     *
     * @throws ezcImageHandlerSettingsInvalidException
     *         If handler settings are invalid.
     * @throws ezcImageMimeTypeUnsupportedException
     *         If a given MIME type is not supported.
     */
    public function __construct( ezcImageConverterSettings $settings )
    {
        // Initialize handlers
        foreach ( $settings->handlers as $i => $handlerSettings )
        {
            if ( !$handlerSettings instanceof ezcImageHandlerSettings )
            {
                throw new ezcImageHandlerSettingsInvalidException();
            }
            $handlerClass = $handlerSettings->className;
            if ( !ezcBaseFeatures::classExists( $handlerClass ) )
            {
                throw new ezcImageHandlerNotAvailableException( $handlerClass );
            }
            $handler = new $handlerClass( $handlerSettings );
            $this->handlers[$handlerSettings->referenceName] = $handler;
        }
        // Check implicit conversions
        foreach ( $settings->conversions as $mimeIn => $mimeOut )
        {
            if ( !$this->allowsInput( $mimeIn ) )
            {
                throw new ezcImageMimeTypeUnsupportedException( $mimeIn, 'input' );
            }
            if ( !$this->allowsOutput( $mimeOut ) )
            {
                throw new ezcImageMimeTypeUnsupportedException( $mimeOut, 'output' );
            }
        }
        $this->settings = $settings;
    }

    /**
     * Create a transformation in the manager.
     *
     * Creates a transformation and stores it in the manager. A reference to the
     * transformation is returned by this method for further manipulation and
     * to set options on it. The $name can later be used to remove a
     * transfromation using {@link removeTransformation()} or to execute it
     * using {@link transform()}. The $filters and $mimeOut parameters specify
     * the transformation actions as described with {@link
     * ezcImageTransformation::__construct()}. The $saveOptions are used when
     * the finally created image is saved and can configure compression and
     * quality options.
     *
     * @param string                $name        Name for the transformation.
     * @param array(ezcImageFilter) $filters     Filters.
     * @param array(string)         $mimeOut     Output MIME types.
     * @param ezcImageSaveOptions   $saveOptions Save options.
     *
     * @return ezcImageTransformation
     *
     * @throws ezcImageFiltersException 
     *         If a given filter does not exist.
     * @throws ezcImageTransformationAlreadyExists 
     *         If a transformation with the given name does already exist. 
     */
    public function createTransformation( $name, array $filters, array $mimeOut, ezcImageSaveOptions $saveOptions = null )
    {
        if ( isset( $this->transformations[$name] ) )
        {
            throw new ezcImageTransformationAlreadyExistsException( $name );
        }
        $this->transformations[$name] = new ezcImageTransformation( $this, $name, $filters, $mimeOut, $saveOptions );
        return $this->transformations[$name];
    }

    /**
     * Removes a transformation from the manager.
     *
     * @param string $name Name of the transformation to remove
     *
     * @return ezcImageTransformation The removed transformation
     *
     * @throws ezcImageTransformationNotAvailableExeption 
     *         If the requested transformation is unknown.
     */
    public function removeTransformation( $name )
    {
        if ( !isset( $this->transformations[$name] ) )
        {
            throw new ezcImageTransformationNotAvailableException( $name );
        }
        unset( $this->transformations[$name] );
    }

    /**
     * Apply transformation on a file.
     * This applies the given transformation to the given file.
     *
     * @param string $name    Name of the transformation to perform
     * @param string $inFile  The file to transform
     * @param string $outFile The file to save transformed version to
     *
     * @throws ezcImageTransformationNotAvailableExeption
     *         If the requested transformation is unknown.
     * @throws ezcImageTransformationException If an error occurs during the 
     *         transformation. The returned exception contains the exception
     *         the problem resulted from in it's public $parent attribute.
     * @throws ezcBaseFileNotFoundException If the file you are trying to 
     *         transform does not exists.
     * @throws ezcBaseFilePermissionException If the file you are trying to 
     *         transform is not readable.
     */
    public function transform( $name, $inFile, $outFile )
    {
        if ( !isset( $this->transformations[$name] ) )
        {
            throw new ezcImageTransformationNotAvailableException( $name );
        }
        $this->transformations[$name]->transform( $inFile, $outFile );
    }

    /**
     * Returns if a handler is found, supporting the given MIME type for output.
     *
     * @param string $mime The MIME type to check for.
     * @return bool Whether the MIME type is supported.
     */
    public function allowsInput( $mime )
    {
        foreach ( $this->handlers as $handler )
        {
            if ( $handler->allowsInput( $mime ) )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns if a handler is found, supporting the given MIME type for output.
     *
     * @param string $mime The MIME type to check for.
     * @return bool Whether the MIME type is supported.
     */
    public function allowsOutput( $mime )
    {
        foreach ( $this->handlers as $handler )
        {
            if ( $handler->allowsOutput( $mime ) )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the MIME type that will be outputted for a given input type.
     * Checks whether the given input type can be processed. If not, an
     * exception is thrown. Checks then, if an implicit conversion for that
     * MIME type is defined. If so, outputs the given output MIME type. In
     * every other case, just outputs the MIME type given, because no
     * conversion is implicitly required.
     *
     * @param string $mimeIn Input MIME type.
     * @return string Output MIME type.
     *
     * @throws ezcImageMimeTypeUnsupportedException
     *         If the input MIME type is not supported.
     */
    public function getMimeOut( $mimeIn )
    {
        if ( $this->allowsInput( $mimeIn ) === false )
        {
            throw new ezcImageMimeTypeUnsupportedException( $mimeIn, 'input' );
        }
        if ( isset( $this->settings->conversions[$mimeIn] ) )
        {
            return $this->settings->conversions[$mimeIn];
        }
        return $mimeIn;
    }

    /**
     * Returns if a given filter is available.
     * Returns either an array of handler names this filter
     * is available in or false if the filter is not enabled.
     *
     * @param string $name Name of the filter to query existance for
     *
     * @return mixed Array of handlers on success, otherwise false.
     */
    public function hasFilter( $name )
    {
        foreach ( $this->handlers as $handler )
        {
            if ( $handler->hasFilter( $name ) )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a list of enabled filters.
     * Gives you an overview on filters enabled in the manager.
     * Format is:
     * <code>
     * array(
     *  '<filterName>',
     * );
     * </code>
     *
     * @return array(string)
     */
    public function getFilterNames()
    {
        $filters = array();
        foreach ( $this->handlers as $handler )
        {
            $filters = array_merge( $filters, $handler->getFilterNames() );
        }
        return array_unique( $filters );
    }

    /**
     * Apply a single filter to an image.
     * Applies just a single filter to an image. Optionally you can select
     * a handler yourself, which is not recommended, but possible. If the
     * specific handler does not have that filter, ImageConverter will try
     * to fall back on another handler.
     *
     * @param ezcImageFilter $filter      Filter object to apply.
     * @param string         $inFile      Name of the input file.
     * @param string         $outFile     Name of the output file.
     * @param string         $handlerName
     *        To choose a specific handler, this is the reference named passed
     *        to {@link ezcImageHandlerSettings}.
     * @return void
     *
     *
     * @throws ezcImageHandlerNotAvailableException
     *         If fitting handler is not available.
     * @throws ezcImageFilterNotAvailableException 
     *         If filter is not available.
     * @throws ezcImageFileNameInvalidException 
     *         If an invalid character (", ', $) is found in the file name.
     */
    public function applyFilter( ezcImageFilter $filter, $inFile, $outFile, $handlerName = null )
    {
        $handlerObj = false;
        // Do we have an explicit handler given?
        if ( $handlerName !== null )
        {
            if ( !isset( $this->handlers[$handlerName] ) )
            {
                throw new ezcImageHandlerNotAvailableException( $handlerName );
            }
            if ( $this->handlers[$handlerName]->hasFilter( $filter->name ) === true )
            {
                $handlerObj = $this->handlers[$handlerName];
            }
        }
        // Either no handler explicitly given or try to fall back.
        if ( $handlerObj === false )
        {
            foreach ( $this->handlers as $regHandler )
            {
                if ( $regHandler->hasFilter( $filter->name ) )
                {
                    $handlerObj = $regHandler;
                    break;
                }
            }
        }
        // No handler found to apply filter with.
        if ( $handlerObj === false )
        {
            throw new ezcImageFilterNotAvailableException( $filter->name );
        }
        $imgRef = $handlerObj->load( $inFile );
        $handlerObj->applyFilter( $imgRef, $filter );
        $handlerObj->save( $imgRef, $outFile );
    }

    /**
     * Returns a handler object for direct use.
     * Returns the handler with the highest priority, that supports the given
     * filter, MIME input type and MIME output type. All parameters are
     * optional, if none is specified, the highest prioritized handler is
     * returned.
     *
     * If no handler is found, that supports the criteria named, an exception
     * of type {@link ezcImageHandlerNotAvailableException} will be thrown.
     *
     * @param string $filterName  Name of the filter to search for.
     * @param string $mimeIn      Input MIME type.
     * @param string $mimeOut     Output MIME type.
     *
     * @return ezcImageHandler
     *
     * @throws ezcImageHandlerNotAvailableException
     *         If a handler for the given specification could not be found.
     */
    public function getHandler( $filterName = null, $mimeIn = null, $mimeOut = null )
    {
        foreach ( $this->handlers as $handler )
        {
            if ( ( !isset( $filterName ) || $handler->hasFilter( $filterName ) )
              && ( !isset( $mimeIn )     || $handler->allowsInput( $mimeIn ) )
              && ( !isset( $mimeOut )    || $handler->allowsOutput( $mimeOut ) )
               )
            {
                return $handler;
            }
        }
        throw new ezcImageHandlerNotAvailableException( 'unknown' );
    }
}
?>
