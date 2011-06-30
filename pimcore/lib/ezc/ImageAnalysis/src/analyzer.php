<?php
/**
 * File containing the ezcImageAnalyzer class.
 *
 * @package ImageAnalysis
 * @version 1.1.3
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Class to retreive information about a given image file.
 * This class scans the specified image file and leaves the information
 * available through the public property {@link ezcImageAnalyzer::$data}.
 * The information available depends on the handlers used by the 
 * ezcImageAnalyzer and the type of image you select. In case the 
 * ezcImageAnalyzer does not find a suitable handler to analyze an image,
 * it will throw a {@link ezcImageAnalyzerFileNotProcessableException}.
 *
 * In this package the following handlers are available (in their priority
 * order):
 * - {@link ezcImageAnalyzerImagemagickHandler}, which uses the ImageMagick 
 *   binary "identify" to collect information about an image.
 * - {@link ezcImageAnalyzerPhpHandler}, which relies on the GD extension and 
 *   is capable of using the exif extension to determine additional data.
 * 
 * For detailed information on the data provided by these handlers, 
 * their capabilities on analyzing images and their speciallties, please 
 * take a look at their documentation. For general information on handlers,
 * look at {@link ezcImageAnalyzerHandler}, 
 * {@link ezcImageAnalyzer::getHandlerClasses()} and
 * {@link ezcImageAnalyzer::setHandlerClasses()}.
 * 
 * A simple example.
 * <code>
 * // Analyzation of the MIME type is done during creation.
 * $image = new ezcImageAnalyzer( dirname( __FILE__ ).'/toby.jpg' );
 * 
 * if ( $image->mime == 'image/tiff' || $image->mime == 'image/jpeg' )
 * {
 *      // Analyzation of further image data is done during access of the data
 *      echo 'Photo taken on '.date( 'Y/m/d, H:i', $image->data->date ).".\n";
 * }
 * elseif ( $mime !== false )
 * {
 *      echo "Format was detected as {$mime}.\n";
 * }
 * else
 * {
 *      echo "Unknown photo format.\n";
 * }
 * </code>
 *
 * If you want to manipulate the handlers used by ezcImageAnalyzer, you can do 
 * this globally like this:
 * <code>
 * // Retreive the predefined handler classes
 * $originalHandlers = ezcImageAnalyzer::getHandlerClasses();
 * foreach ( $handlerClasses as $id => $handlerClass )
 * {
 *      // Unset the ezcImageAnalyzerPhpHandler (do not use that anymore!)
 *      if ( $handlerClass === 'ezcImageAnalyzerPhpHandler' )
 *      {
 *          unset( $handlerClasses[$id] );
 *      }
 * }
 * // Set the new collection of handler classes.
 * ezcImageAnalyzer::setHandlerClasses( $handlerClasses );
 *
 * // Somewhere else in the code... This now tries to use your handler in the 
 * // first place
 * $image = new ezcImageAnalyzer( '/var/cache/images/toby.jpg' );
 * </code>
 *
 * Or you can define your own handler classes to be used (beware, those must 
 * either be already loaded or load automatically on access).
 * <code>
 * // Define your onw handler class to be used in the first place and fall back on 
 * // ImageMagick, if necessary.
 * $handlerClasses = array( 'MyOwnHandlerClass', 'ezcImageAnalyzerImagemagickHandler' );
 * ezcImageAnalyzer::setHandlerClasses( $handlerClasses );
 *
 * // Somewehre else in the code... This now tries to use your handler in the 
 * // first place
 * $image = new ezcImageAnalyzer( '/var/cache/images/toby.jpg' );
 * </code>
 *
 * @property-read string $mime
 *                The MIME type of the image.
 * @property-read ezcImageAnalyzerData $data
 *                Extended data about the image.
 *
 * @package ImageAnalysis
 * @version 1.1.3
 */
class ezcImageAnalyzer
{
    /**
     * The path of the file to analyze.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Determines whether the image file has been analyzed or not.
     * This is used internally.
     *
     * @var bool
     */
    protected $isAnalyzed;

    /**
     * Container to hold the properties
     *
     * @var array(string=>mixed)
     */
    protected $properties;

    /**
     * Collection of known handler classes. Classes are ordered by priority.
     *
     * @var array(string=>mixed)
     */
    protected static $knownHandlers = array(
        'ezcImageAnalyzerPhpHandler' => array(),
        'ezcImageAnalyzerImagemagickHandler' => array(),
    );

    /**
     * Available handler classes and their options.
     *
     * @var array
     */
    protected static $availableHandlers;

    /**
     * Create an image analyzer for the specified file.
     *
     * @throws ezcBaseFilePermissionException
     *         If image file is not readable.
     * @throws ezcBaseFileNotFoundException
     *         If image file does not exist.
     * @throws ezcImageAnalyzerFileNotProcessableException
     *         If the file could not be processed.
     * @param string $file The file to analyze.
     */
    public function __construct( $file )
    {
        if ( !file_exists( $file ) || !is_file( $file ) )
        {
            throw new ezcBaseFileNotFoundException( $file );
        }
        if ( !is_readable( $file ) )
        {
            throw new ezcBaseFilePermissionException( $file, ezcBaseFileException::READ );
        }
        $this->filePath = $file;
        $this->isAnalyzed = false;

        $this->checkHandlers();
        
        $this->analyzeType();
    }

    /**
     * Check all known handlers for availability.
     *
     * This method checks all registered handler classes for if the they are
     * available (using {@link ezcImageAnalyzerHandler::isAvailable()}).
     * 
     * @throws ezcImageAnalyzerInvalidHandlerException
     *         If a registered handler class does not exist
     *         or does not inherit from {@link ezcImageAnalyzerHandler}.
     */
    protected function checkHandlers()
    {
        if ( isset( ezcImageAnalyzer::$availableHandlers ) && is_array( ezcImageAnalyzer::$availableHandlers ) )
        {
            return;
        }
        ezcImageAnalyzer::$availableHandlers = array();
        foreach ( ezcImageAnalyzer::$knownHandlers as $handlerClass => $options )
        {
            if ( !ezcBaseFeatures::classExists( $handlerClass ) || !is_subclass_of( $handlerClass, 'ezcImageAnalyzerHandler' ) )
            {
                throw new ezcImageAnalyzerInvalidHandlerException( $handlerClass );
            }
            $handler = new $handlerClass( $options );
            if ( $handler->isAvailable() ) 
            {
                ezcImageAnalyzer::$availableHandlers[] = clone( $handler );
            }
        }
    }

    /**
     * Returns an array of known handler classes.
     *
     * This method returns an array of available handler classes. The array is
     * indexed by the handler names, which are assigned to an array of options
     * set for this handler.
     *
     * @return array(string=>array(string=>string)) Handlers and options.
     */
    public static function getHandlerClasses()
    {
        return ezcImageAnalyzer::$knownHandlers;
    }

    /**
     * Set the array of known handlers.
     *
     * Sets the available handlers. The array submitted must be indexed by
     * the handler classes names (attention: handler classes must extend
     * ezcImageAnalyzerHandler), assigned to an array of options for this
     * handler. Most handlers don't have any options. Which options a handler
     * may accept depends on the handler implementation.
     *
     * @param array(string=>array(string=>string)) $handlerClasses Handlers
     *                                                             and options.
     */
    public static function setHandlerClasses( array $handlerClasses )
    {
        ezcImageAnalyzer::$knownHandlers = $handlerClasses;
        ezcImageAnalyzer::$availableHandlers = null;
    }

    /**
     * Sets the property $name to $value.
     *
     * @throws ezcBasePropertyNotFoundException
     *         If the property does not exist.
     * @throws ezcBasePropertyPermissionException
     *         If the property cannot be modified.
     * @param string $name
     * @param mixed $value
     * @ignore
     */
    public function __set( $name, $value )
    {
        switch ( $name )
        {
            case 'mime':
            case 'data':
                throw new ezcBasePropertyPermissionException( $name, ezcBasePropertyPermissionException::READ );
            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }

    /**
     * Returns the property $name.
     *
     * @throws ezcBasePropertyNotFoundException
     *         If the property does not exist.
     * @param string $name Name of the property to access.
     * @return mixed Value of the desired property.
     * @ignore
     */
    public function __get( $name )
    {
        switch ( $name )
        {
            case 'mime':
                return $this->properties['mime'];
            case 'data':
                if ( !$this->isAnalyzed )
                {
                    $this->analyzeImage();
                }
                return $this->properties[$name];
            default:
                throw new ezcBasePropertyNotFoundException( $name );
        }
    }

    /**
     * Checks if the property $name exist and returns the result.
     *
     * @param string $name
     * @return bool
     * @ignore
     */
    public function __isset( $name )
    {
        switch ( $name )
        {
            case 'mime':
            case 'data':
                return true;
            default:
                return false;
        }
    }

    /**
     * Analyze the image file's MIME type.
     * This method triggers a handler to analyze the MIME type of the given image file.
     *
     * @throws ezcImageAnalyzerFileNotProcessableException
     *         If the no handler is capable to analyze the given image file.
     */
    public function analyzeType()
    {
        if ( !is_array( ezcImageAnalyzer::$availableHandlers ) )
        {
            $this->checkHandlers();
        }
        foreach ( ezcImageAnalyzer::$availableHandlers as $handler )
        {
            if ( ( $mime = $handler->analyzeType( $this->filePath ) ) !== false )
            {
                $this->properties['mime'] = $mime;
                return;
            }
        }
        throw new ezcImageAnalyzerFileNotProcessableException( $this->filePath, "Could not determine MIME type of file." );
    }

    /**
     * Analyze the image file.
     *
     * This method triggers a handler to analyze the given image file for more data.
     * 
     * @throws ezcImageAnalyzerFileNotProcessableException
     *         If the no handler is capable to analyze the given image file.
     * @throws ezcBaseFileIoException
     *         If an error occurs while the file is read.
     */
    public function analyzeImage()
    {
        if ( !is_array( ezcImageAnalyzer::$availableHandlers ) )
        {
            $this->checkHandlers();
        }
        if ( !isset( $this->properties['mime'] ) )
        {
            $this->analyzeType();
        }
        foreach ( ezcImageAnalyzer::$availableHandlers as $handler )
        {
            if ( $handler->canAnalyze( $this->properties['mime'] ) )
            {
                $this->properties['data'] = $handler->analyzeImage( $this->filePath );
                $this->isAnalyzed = true;
                return;
            }
        }
        throw new ezcImageAnalyzerFileNotProcessableException( $this->filePath, "No handler found to analyze MIME type '{$this->mime}'." );
    }
}
?>
