<?php
/**
 * This file contains the ezcImageMethodcallHandler interface.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 * @access private
 */

/**
 * Special image handler which handles filters using method calls and keeps
 * track of resources.
 * This is a special abstract class which are extended by the ImageMagick and
 * GD handlers and contains code which is common to both of them.
 * It performs filter operations by keeping a filter object available and
 * accesses the filter methods directly, this means the sub-classes only have
 * to create the correct filter object.
 *
 * Implements all abstract methods of ezcImageHandler except load(), save()
 * and close(). Instead it provides the {@link loadCommon()},
 * {@link saveCommon()} and {@link closeCommon()} methods to simplify the code
 * for sub-classes.
 *
 * @see ezcImageConverter
 * @see ezcImageGdHandler
 * @see ezcImageImagemagickHandler
 * @see ezcImageFilters
 *
 * @package ImageConversion
 * @version 1.3.8
 * @access private
 */
abstract class ezcImageMethodcallHandler extends ezcImageHandler
{
    /**
     * Array of MIME types usable for input
     *
     * @var array
     */
    protected $inputTypes;

    /**
     * Array of MIME types usable for output
     *
     * @var array
     */
    protected $outputTypes;

    /**
     * Array of filter names cached from getFilterNames().
     * 
     * @var array
     */
    protected $filterNameCache;

    /**
     * Image references created through load().
     * Format:
     * array(
     *  'id' => array(
     *      'file'      => <file name>,
     *      'mime'      => <MIME type>,
     *      'resource'  => <image resource>,
     *  )
     * )
     *
     * @var array
     */
    private $references = array();

    /**
     * Currently active image reference.
     * This is used to determine by the filter, which image should be
     * processed.
     *
     * @var string
     */
    private $activeReference;

    /**
     * Create a new image handler.
     * Creates an image handler. This should never be done directly,
     * but only through the manager for configuration reasons. One can
     * get a direct reference through manager afterwards. When overwriting
     * the constructor.
     *
     * The contents of the $settings parameter may change from handler to 
     * handler. For detailed information take a look at the specific handler
     * classes.
     *
     * @param ezcImageHandlerSettings $settings Settings for the handler.
     */
    public function __construct( ezcImageHandlerSettings $settings )
    {
        parent::__construct( $settings );
    }

    /**
     * Destroyes the handler and closes all open references correctly.
     * 
     * @return void
     */
    public function __destruct()
    {
        foreach ( $this->references as $id => $data )
        {
            $this->close( $id );
        }
    }

    /**
     * Check wether a specific MIME type is allowed as input for this handler.
     *
     * @param string $mime MIME type to check if it's allowed.
     * @return bool
     */
    public function allowsInput( $mime )
    {
        return ( in_array( strtolower( $mime ), $this->inputTypes ) );
    }

    /**
     * Checks wether a specific MIME type is allowed as output for this handler.
     *
     * @param string $mime MIME type to check if it's allowed.
     * @return bool
     */
    public function allowsOutput( $mime )
    {
        return ( in_array( strtolower( $mime ), $this->outputTypes ) );
    }

    /**
     * Checks if a given filter is available in this handler.
     *
     * @param string $name Name of the filter to check for.
     * @return bool
     *
     */
    public function hasFilter( $name )
    {
        return method_exists( $this, $name );
    }

    /**
     * Returns a list of filters this handler provides.
     * The list returned is in format:
     *
     * <code>
     * array(
     *  0 => <string filtername>,
     *  1 => <string filtername>,
     *  ...
     * )
     * </code>
     *
     * @return array(string)
     */
    public function getFilterNames()
    {
        if ( !isset( $this->filterNameCache ) || !is_array( $this->filterNameCache || sizeof( $this->filterNameCache ) === 0 ) )
        {
            $this->filterNameCache = array();
            $excludeMethods = array( 
                '__construct',
                '__destruct',
                '__get',
                '__set',
                '__call',
                'allowsInput',
                'allowsOutput',
                'hasFilter',
                'getFilterNames',
                'applyFilter',
                'convert',
                'load',
                'save',
                'close',
                'defaultSettings',
            );
            
            $refClass = new ReflectionClass( get_class( $this ) );
            foreach ( $refClass->getMethods() as $method )
            {
                if ( $method->isPublic() && !in_array( $method->getName(), $excludeMethods ) )
                {
                    $this->filterNameCache[] = $method->getName();
                }
            }
        }
        return $this->filterNameCache;
    }

    /**
     * Applies a filter to a given image.
     *
     * @internal This method is the main one, which will dispatch the
     * filter action to the specific function of the backend.
     *
     * @see ezcImageMethodcallHandler::load()
     * @see ezcImageMethodcallHandler::save()
     *
     * @param string $image          Image reference to apply the filter on.
     * @param ezcImageFilter $filter Contains which filter operation to apply.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference.
     * @throws ezcImageFilterNotAvailableException
     *         If the desired filter does not exist.
     * @throws ezcImageFiltersMissingFilterParameterException
     *         If a parameter for the filter is missing.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     * @throws ezcBaseValueException
     *         If a parameter was not within the expected range.
     */
    public function applyFilter( $image, ezcImageFilter $filter )
    {
        if ( !$this->hasFilter( $filter->name ) )
        {
            throw new ezcImageFilterNotAvailableException( $filter->name );
        }
        $reflectClass = new ReflectionClass( get_class( $this ) );
        $reflectParameters = $reflectClass->getMethod( $filter->name )->getParameters();
        $parameters = array();
        foreach ( $reflectParameters as $id => $parameter )
        {
            $paramName = $parameter->getName();
            if ( isset( $filter->options[$paramName] ) )
            {
                $parameters[] = $filter->options[$paramName];
            }
            else if ( $parameter->isOptional() === false )
            {
                throw new ezcImageMissingFilterParameterException( $filter->name, $paramName );
            }
        }
        // Backup last active reference
        $oldRef = $this->getActiveReference();
        // Perform actual filtering on given image
        $this->setActiveReference( $image );
        call_user_func_array( array( $this, $filter->name ), $parameters );
        // Restore last active reference
        $this->setActiveReference( $oldRef );
    }

    /**
     * Converts an image to another MIME type.
     *
     * Use {@link ezcImageMethodcallHandler::allowsOutput()} to determine,
     * if the output MIME type is supported by this handler!
     *
     * @see ezcImageMethodcallHandler::load()
     * @see ezcImageMethodcallHandler::save()
     *
     * @param string $image Image reference to convert.
     * @param string $mime  MIME type to convert to.
     * @return void
     *
     * @throws ezcImageMimeTypeUnsupportedException
     *         If the given MIME type is not supported by the filter.
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     */
    public function convert( $image, $mime )
    {
        $oldMime = $this->getReferenceData( $image, 'mime' );
        if ( !$this->allowsOutput( $mime ) )
        {
            throw new ezcImageMimeTypeUnsupportedException( $mime, 'output' );
        }
        $this->setReferenceData( $image, $mime, 'mime' );
    }

    /**
     * Receive the resource of the active image reference.
     * This method is utilized by the ezcImageFilters* class to receive the
     * currently active resource for manipulations.
     *
     * @return resource The currently active resource.
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     */
    protected function getActiveResource()
    {
        $ref = $this->getActiveReference();
        if ( ( $resource = $this->getReferenceData( $ref, 'resource' ) ) === false )
        {
            throw new ezcImageInvalidReferenceException( "No resource found for the active reference '{$ref}'." );
        }
        return $resource;
    }

    /**
     * Replace the resource of an image reference with a new one.
     * After filtering the current image resource might have to be replaced
     * with a new version. This can be done using this method.
     *
     * @param resource(GD) $resource
     * @return void
     */
    protected function setActiveResource( $resource )
    {
        $this->setReferenceData(
            $this->getActiveReference(),
            $resource,
            'resource'
        );
    }

    /**
     * Returns the currently active reference.
     * Returns the reference which is currently marked as active. This happens
     * either by loading a new file or by using the setActiveReference()
     * method.
     *
     * @see ezcImageMethodcallHandler::setActiveReference()
     * @see ezcImageMethodcallHandler::load()
     * @see ezcImageMethodcallHandler::$references
     *
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference.
     * 
     * @return string The active reference.
     */
    protected function getActiveReference()
    {
        if ( !isset( $this->activeReference ) )
        {
            throw new ezcImageInvalidReferenceException( 'No reference is defined as active. Either no file is loeaded, yet or an internal error destroyed the reference.' );
        }
        return $this->activeReference;
    }

    /**
     * Mark the submitted image reference as active.
     * The image reference submitted is marked as active. All following
     * filter operations are performed on this reference.
     *
     * @param string $image The image reference.
     * @return void
     * 
     * @throws ezcImageInvalidReferenceException
     *         If the given reference is invalid.
     */
    protected function setActiveReference( $image )
    {
        if ( !isset( $this->references[$image] ) )
        {
            throw new ezcImageInvalidReferenceException( 'Could not mark invalid reference as active.' );
        }
        $this->activeReference = $image;
    }

    /**
     * Returns data about a reference.
     * This gives you access to the data stored about a loaded image. You can
     * either retrieve a certain detail (defined in the references array), with
     * specifying it through the second parameter (the method then simply
     * returns that detail) or retrieve all available details with leaving that
     * parameter out.
     *
     * By default the following details are available:
     * <code>
     * 'file'       => The file name of the image loaded.
     * 'mime'       => The mime type of the image loaded.
     * 'resource'   => A resource referencing it.
     * </code>
     *
     * Of what type the resource is, may differ from handler to handler (e.g. a
     * GD resource for the GD handler or a file path for the ImageMagick handler).
     * You can simply store your own details be setting them and retreive them
     * through this method.
     *
     * @param string $reference Reference string assigned.
     * @param mixed $detail     To receive a single detail, set to detail name.
     * @return array Array of details if you specify $detail, else depending on
     *               the detail. If detail is not available, returns false.
     *
     * @throws ezcImageInvalidReferenceException
     *         If the given reference is invalid.
     *
     * @see ezcImageMethodcallHandler::setReferenceData()
     */
    protected function getReferenceData( $reference, $detail = null )
    {
        if ( !isset( $this->references[$reference] ) )
        {
            throw new ezcImageInvalidReferenceException( "Inavlid image reference given: '{$reference}'." );
        }
        if ( isset( $detail ) )
        {
            return isset( $this->references[$reference][$detail] ) ?  $this->references[$reference][$detail] : false;
        }
        return $this->references[$reference];
    }

    /**
     * Set data for an image reference.
     * This method allows you to set all data that can be retrieved through
     * ezcImageMethodcallHandler::getReferenceData(). You can either set a single detail
     * by providing the optional $detail parameter or submit an array containing
     * all details at once as the value to set all details.
     *
     * @param string $reference Reference string of the image data.
     * @param mixed $value      The value to set.
     * @param string $detail    The name of the detail to set.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If the given reference is invalid.
     * @throws ezcBaseValueException
     *         If the given detail is invalid.
     */
    protected function setReferenceData( $reference, $value, $detail = null )
    {
        if ( !isset( $this->references[$reference] ) )
        {
            throw new ezcImage( "Invalid image reference given: '{$reference}'." );
        }
        if ( isset( $detail ) )
        {
            $this->references[$reference][$detail] = $value;
        }
        else
        {
            if ( !is_array( $value ) )
            {
                throw new ezcBaseValueException( 'value', $value, 'array' );
            }
            if ( !isset( $value['file'] ) ) 
            {
                throw new ezcBaseValueException( 'file', null, 'string' );
            }
            if ( !isset( $value['mime'] ) ) 
            {
                throw new ezcBaseValueException( 'mime', null, 'string' );
            }
            if ( !isset( $value['resource'] ) ) 
            {
                throw new ezcBaseValueException( 'resource', null, 'string' );
            }
            $this->references[$reference] = $value;
        }
    }

    /**
     * Create a reference entry for this file.
     * Performs common operations on a specific file, like checking if the file
     * exists, if it is loadable, if it's already loaded. Beside of that, it
     * creates the reference internally, so you don't need to handle this
     * stuff manually with the internal data structure of
     * ezcImageMethodcallHandler::$references. It also cares for determining the MIME-
     * type of the image and sets the newly created reference to be active.
     *
     * @param string $file The file to load.
     * @param string $mime The MIME type of the file.
     * @return string reference The reference string for this file.
     *
     * @throws ezcBaseFileNotFoundException
     *         If the desired file does not exist.
     * @throws ezcBaseFilePermissionException
     *         If the desired file is not readable.
     * @throws ezcBaseValueException
     *         If the given detail is invalid.
     * @throws ezcImageMimeTypeUnsupportedException
     *         If the desired file has a not recognized type.
     */
    protected function loadCommon( $file, $mime = null )
    {
        if ( !is_file( $file ) )
        {
            throw new ezcBaseFileNotFoundException( $file );
        }
        if ( !is_readable( $file ) )
        {
            throw new ezcBaseFilePermissionException( $file, ezcBaseFileException::READ );
        }

        $file = realpath( $file );
        $ref = md5( $file );

        if ( !isset( $mime ) )
        {
            $mime = '';
            try
            {
                $analyzer = new ezcImageAnalyzer( $file );
                $mime = $analyzer->mime;
            }
            catch ( ezcImageAnalyzerException $e )
            {
                throw new ezcImageMimeTypeUnsupportedException( 'unknown/unknown', 'input' );
            }
        }

        $this->references[$ref] = array();
        $this->setReferenceData(
            $ref,
            array(
                'file'      => $file,
                'mime'      => $mime,
                'resource'  => false,
            )
        );
        $this->setActiveReference( $ref );

        return $ref;
    }

    /**
     * Performs common operations before saving a file.
     * This method should/can be used while implementing the save method of an
     * ezcImageMethodcallHandler. It performs several tasks, like setting the new file name,
     * if it has been submitted, and the new MIME type. Beside that, it checks
     * if one can write to the new file and if the handler is able to process
     * the new MIME type.
     *
     * @param string $reference   The image reference.
     * @param string $newFile The new filename.
     * @param string $mime    The new MIME type.
     * @return void
     *
     * @throws ezcBaseFilePermissionException 
     *         If the desired file is not writeable.
     * @throws ezcImageMimeTypeUnsupportedException
     *         If the desired MIME type is not recognized.
     */
    protected function saveCommon( $reference, $newFile = null, $mime = null )
    {
        if ( isset( $newFile ) )
        {
            $this->setReferenceData( $reference, $newFile, 'file' );
        }
        $file = $this->getReferenceData( $reference, 'file' );
        if ( file_exists( $file ) && !is_writeable( $file ) )
        {
            throw new ezcBaseFilePermissionException( $file, ezcBaseFileException::WRITE );
        }

        if ( isset( $mime ) )
        {
            $this->setReferenceData( $reference, $mime, 'mime' );
        }
        $mime = $this->getReferenceData( $reference, 'mime' );
        if ( $this->allowsOutput( $mime ) === false )
        {
            throw new ezcImageMimeTypeUnsupportedException( $mime, 'output' );
        }
    }

    /**
     * Unsets the reference data for the given reference.
     * This method _must_ be called in the implementation of the close() method
     * in every ezcImageMethodcallHandler to finally remove the reference.
     *
     * @param string $reference The reference to free.
     * @return void
     */
    protected function closeCommon( $reference )
    {
        $data = $this->getReferenceData( $reference );
        unset( $this->references[$reference] );
    }
}
?>
