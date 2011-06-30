<?php
/**
 * File containing the ezcImageTransformation class.
 *
 * @see ezcImageConverter
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Provides transformations on images using filters and MIME conversions.
 * Objects of this class group MIME type conversion and filtering of images
 * into transformations of images. Transformations can be chained by referencing
 * to another transformation so that multiple transformations will be produced
 * after each other.
 *
 * <code>
 * $filters = array(
 *   new ezcImageFilter( 'scaleDownByWidth',
 *                        array(
 *                            'width' => 100
 *                        )
 *       ),
 *   new ezcImageFilter( 'crop',
 *                        array(
 *                            'x' => 0,
 *                            'y' => 0,
 *                            'width'  => 100,
 *                            'height' => 100,
 *                        )
 *       ),
 * );
 * $mimeTypes = array( 'image/jpeg', 'image/png' );
 *
 * // ezcImageTransformation object returned for further manipulation
 * $thumbnail = $converter->createTransformation(
 *      'thumbnail',
 *      $filters,
 *      $mimeTypes
 * );
 *
 * $converter->transform( 'thumbnail', 'var/storage/myOriginal1.jpg',
 *                        'var/storage/myThumbnail1' ); // res: image/jpeg
 * $converter->transform( 'thumbnail', 'var/storage/myOriginal2.png',
 *                        'var/storage/myThumbnail2' ); // res: image/png
 * $converter->transform( 'thumbnail', 'var/storage/myOriginal3.gif',
 *                        'var/storage/myThumbnail3' ); // res: image/.png
 *
 * // Animated GIF, will simply be copied!
 * $converter->transform( 'thumbnail', 'var/storage/myOriginal4.gif',
 *                        'var/storage/myThumbnail4' ); // res: image/gif
 * </code>
 *
 * @see ezcImageConverter
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageTransformation
{
    /**
     * Array of MIME types allowed as output for this transformation.
     * Leave empty, for all MIME types to be allowed.
     *
     * @var array(string)
     */
    protected $mimeOut;

    /**
     * Stores the filters utilized by a transformation.
     *
     * @var array(ezcImageFilter)
     */
    protected $filters;

    /**
     * Stores the name of this transformation.
     *
     * @var string
     */
    protected $name;

    /**
     * The ezcImageConverter
     *
     * @var ezcImageConverter
     */
    protected $converter;

    /**
     * The handler last used for filtering.
     *
     * @var ezcImageHandler
     */
    protected $lastHandler;

    /**
     * Options for the final save step. 
     * 
     * @var ezcSaveOptions
     */
    protected $saveOptions;

    /**
     * Initialize transformation.
     *
     * @param ezcImageConverter $converter     The global converter.
     * @param string $name                     Name for the transformation.
     * @param array(ezcImageFilter) $filters   Filters to apply.
     * @param array(string) $mimeOut           Output MIME types.
     * @param ezcImageSaveOptions $saveOptions Options for saving images.
     *
     * @throws ezcImageFiltersException 
     *         On invalid filter or filter settings error.
     * @throws ezcImageMimeTypeUnsupportedException 
     *         If the output type is unsupported.
     */
    public function __construct( ezcImageConverter $converter, $name, array $filters = array(), array $mimeOut = array(), ezcImageSaveOptions $saveOptions = null )
    {
        $this->converter = $converter;
        $this->name = $name;
        $this->setFilters( $filters );
        $this->setMimeOut( $mimeOut );
        $this->setSaveOptions( $saveOptions !== null ? $saveOptions : new ezcImageSaveOptions() );
    }

    /**
     * Add a filter to the conversion.
     * Adds a filter with the specific settings. Filters can be added either
     * before an existing filter or at the end (leave out $before parameter).
     *
     * @param ezcImageFilter $filter  The filter definition.
     * @param int $before             Where to add the filter
     * @return void
     *
     * @throws ezcImageFilterNotAvailableException
     *         If the given filter is not available.
     */
    public function addFilter( ezcImageFilter $filter, $before = null )
    {
        if ( $this->converter->hasFilter( $filter->name ) === false )
        {
            throw new ezcImageFilterNotAvailableException( $filter->name );
        }
        if ( isset( $before ) && isset( $this->filters[$before] ) )
        {
            array_splice( $this->filters, $before, 0, array( $filter ) );
            return;
        }
        $this->filters[] = $filter;
    }

    /**
     * Determine output MIME type
     * Returns the MIME type that the transformation will output.
     *
     * @param string $fileIn File that should deal as input for the transformation.
     * @param string $mimeIn Specify the MIME type, so method does not need to.
     *
     * @return string MIME type the transformation will output.
     *
     * @throws ezcImageAnalyzerException If the input type is unsupported.
     */
    public function getOutMime( $fileIn, $mimeIn = null )
    {
        if ( !isset( $mimeIn ) )
        {
            $analyzer = new ezcImageAnalyzer( $fileIn );
            $mimeIn   = $analyzer->mime;
        }
        $mimeOut  = $this->converter->getMimeOut( $mimeIn );
        // Is output type allowed by this transformation? Else use first allowed one...
        return in_array( $mimeOut, $this->mimeOut ) ? $mimeOut : reset( $this->mimeOut );
    }

    /**
     * Apply the given filters for the transformation.
     * Applies the conversion as defined to the given file and saves it as 
     * defined.
     *
     * @param string $fileIn  The file to transform.
     * @param string $fileOut The file to save the transformed image to.
     * @return void
     *
     * @throws ezcImageTransformationException If an error occurs during the 
     *         transformation. The returned exception contains the exception
     *         the problem resulted from in it's public $parent attribute.
     * @throws ezcBaseFileNotFoundException If the file you are trying to 
     *         transform does not exists.
     * @throws ezcBaseFilePermissionException If the file you are trying to 
     *         transform is not readable.
     */
    public function transform( $fileIn, $fileOut )
    {
        // Sanity checks
        if ( !is_file( $fileIn ) )
        {
            throw new ezcBaseFileNotFoundException( $fileIn );
        }
        if ( !is_readable( $fileIn ) )
        {
            throw new ezcBaseFilePermissionException( $fileIn, ezcBaseFileException::READ );
        }
        
        // Start atomic file operation
        $fileTmp = tempnam( dirname( $fileOut ) . DIRECTORY_SEPARATOR, '.'. basename( $fileOut ) );
        copy( $fileIn, $fileTmp );

        try
        {
            // MIME types
            $analyzer = new ezcImageAnalyzer( $fileTmp );

            // Do not process animated GIFs
            if ( $analyzer->data->isAnimated )
            {
                copy( $fileTmp, $fileOut );
                unlink( $fileTmp );
                return;
            }

            $mimeIn = $analyzer->mime;
        }
        catch ( ezcImageAnalyzerException $e )
        {
            // Clean up
            unlink( $fileTmp );
            // Rethrow
            throw new ezcImageTransformationException( $e );
        }

        $outMime = $this->getOutMime( $fileTmp, $mimeIn );

        $ref = '';

        // Catch exceptions for cleanup
        try
        {
            // Apply the filters
            foreach ( $this->filters as $filter )
            {
                // Avoid reopening in same handler
                if ( isset( $this->lastHandler ) )
                {
                    if ( $this->lastHandler->hasFilter( $filter->name ) )
                    {
                        $this->lastHandler->applyFilter( $ref, $filter );
                        continue;
                    }
                    else
                    {
                        // Handler does not support filter, save file
                        $this->lastHandler->save( $ref );
                        $this->lastHandler->close( $ref );
                    }
                }
                // Get handler to perform filter correctly
                $this->lastHandler = $this->converter->getHandler( $filter->name, $mimeIn );
                $ref = $this->lastHandler->load( $fileTmp, $mimeIn );
                $this->lastHandler->applyFilter( $ref, $filter );
            }

            // When no filters are performed by a transformation, we might have no last handler here
            if ( !isset( $this->lastHandler ) )
            {
                $this->lastHandler = $this->converter->getHandler( null, $mimeIn, $outMime );
                $ref = $this->lastHandler->load( $fileTmp, $mimeIn );
            }

            // Perform conversion
            if ( $this->lastHandler->allowsOutput( ( $outMime ) ) )
            {
                $this->lastHandler->convert( $ref, $outMime );
            }
            else
            {
                // Close in last handler
                $this->lastHandler->save( $ref );
                $this->lastHandler->close( $ref );
                // Destroy invalid reference (has been closed)
                $ref = null;
                // Retreive correct handler
                $this->lastHandler = $this->converter->getHandler( null, $mimeIn, $outMime );
                // Load in new handler
                $ref = $this->lastHandler->load( $fileTmp );
                // Perform conversion
                $this->lastHandler->convert( $ref, $outMime );
            }
            // Everything done, save and close
            $this->lastHandler->save( $ref, null, null, $this->saveOptions );
            $this->lastHandler->close( $ref );
        }
        catch ( ezcImageException $e )
        {
            // Cleanup
            if ( $ref !== null )
            {
                $this->lastHandler->close( $ref );
            }
            if ( file_exists( $fileTmp ) )
            {
                unlink( $fileTmp );
            }
            $this->lastHandler = null;
            // Rethrow
            throw new ezcImageTransformationException( $e );
        }
        
        // Cleanup
        $this->lastHandler = null;

        // Finalize atomic file operation
        if ( ezcBaseFeatures::os() === 'Windows' && file_exists( $fileOut ) )
        {
            // Windows does not allows overwriting files using rename,
            // therefore the file is unlinked here first.
            if ( unlink( $fileOut ) === false )
            {
                // Cleanup
                unlink( $fileTmp );
                throw new ezcImageFileNotProcessableException( $fileOut, 'The file exists and could not be unlinked.' );
            }
        }
        if ( @rename( $fileTmp, $fileOut ) === false )
        {
            unlink( $fileTmp );
            throw new ezcImageFileNotProcessableException( $fileOut, "The temporary file {$fileTmp} could not be renamed to {$fileOut}." );
        }
    }

    /**
     * Set the filters for this transformation.
     * Checks if the filters defined are available and saves them to the created
     * transformation if everything is okay.
     *
     * @param array(ezcImageFilter) $filters Array of {@link ezcImageFilter filter objects}.
     * @return void
     *
     * @throws ezcImageFilterNotAvailableException 
     *         If a filter is not available.
     * @throws ezcBaseFileException
     *         If the filter array contains invalid object entries.
     */
    protected function setFilters( array $filters )
    {
        foreach ( $filters as $id => $filter )
        {
            if ( !$filter instanceof ezcImageFilter )
            {
                throw new ezcBaseSettingValueException( 'filters', 'array( int => ' . get_class( $filter ) . ' )', 'array( int => ezcImageFilter )' );
            }
            if ( !$this->converter->hasFilter( $filter->name ) )
            {
                throw new ezcImageFilterNotAvailableException( $filter->name );
            }
        }
        $this->filters = $filters;
    }

    /**
     * Sets the MIME types which are allowed for output.
     *
     * @param array $mime MIME types to allow output for.
     * @return void
     *
     * @throws ezcImageMimeTypeUnsupportedException 
     *         If the MIME types cannot be used as output of any of the 
     *         handlers in the converter.
     */
    protected function setMimeOut( array $mime )
    {
        foreach ( $mime as $mimeType )
        {
            if ( !$this->converter->allowsOutput( $mimeType ) )
            {
                throw new ezcImageMimeTypeUnsupportedException( $mimeType, 'output' );
            }
        }
        $this->mimeOut = $mime;
    }

    /**
     * Sets the save options.
     * Sets the save options, that are used for the final save step of the
     * transformation. 
     *
     * {@link ezcImageSaveOptions}
     * 
     * @param ezcImageSaveOptions $options Save options.
     * @return void
     */
    public function setSaveOptions( ezcImageSaveOptions $options )
    {
        $this->saveOptions = $options;
    }
}
?>
