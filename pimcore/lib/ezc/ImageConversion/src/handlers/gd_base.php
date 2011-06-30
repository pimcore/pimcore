<?php
/**
 * This file contains the ezcImageGdBaseHandler class.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * ezcImageHandler implementation for the GD2 extension of PHP.
 * This class only implements the base funtionality of handling GD images. If
 * you want to manipulate images using ext/GD in your application, you should
 * use the {@link ezcImageGdHandler}.
 *
 * You can use this base class to implement your own filter set on basis of
 * ext/GD, but you can also use {@link ezcImageGdHandler} for this and profit
 * from its already implemented filters.
 *
 * @see ezcImageConverter
 * @see ezcImageHandler
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageGdBaseHandler extends ezcImageMethodcallHandler 
{
    /**
     * Create a new image handler.
     * Creates an image handler. This should never be done directly,
     * but only through the manager for configuration reasons. One can
     * get a direct reference through manager afterwards.
     *
     * @param ezcImageHandlerSettings $settings
     *        Settings for the handler.
     *
     * @throws ezcImageHandlerNotAvailableException
     *         If the precondition for the handler is not fulfilled.
     */
    public function __construct( ezcImageHandlerSettings $settings )
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'gd' ) )
        {
            throw new ezcImageHandlerNotAvailableException( "ezcImageGdHandler", "PHP extension 'GD' not available." );
        }
        $this->determineTypes();
        parent::__construct( $settings );
    }

    /**
     * Load an image file.
     * Loads an image file and returns a reference to it.
     *
     * @param string $file File to load.
     * @param string $mime The MIME type of the file.
     *
     * @return string Reference to the file in this handler.
     *
     * @see ezcImageAnalyzer
     *
     * @throws ezcBaseFileNotFoundException
     *         If the given file does not exist.
     * @throws ezcImageMimeTypeUnsupportedException
     *         If the type of the given file is not recognized
     * @throws ezcImageFileNotProcessableException
     *         If the given file is not processable using this handler.
     * @throws ezcImageFileNameInvalidException 
     *         If an invalid character (", ', $) is found in the file name.
     */
    public function load( $file, $mime = null )
    {
        $this->checkFileName( $file );
        $ref = $this->loadCommon( $file, isset( $mime ) ? $mime : null );
        $loadFunction = $this->getLoadFunction( $this->getReferenceData( $ref, 'mime' ) );
        if ( !ezcBaseFeatures::hasFunction( $loadFunction ) || ( $handle = @$loadFunction( $file ) ) === false )
        {
            throw new ezcImageFileNotProcessableException( $file, "File could not be opened using $loadFunction." );
        }
        $this->setReferenceData( $ref, $handle, 'resource' );
        return $ref;
    }

    /**
     * Save an image file.
     * Saves a given open file. Can optionally save to a new file name.
     *
     * @see ezcImageHandler::load()
     *
     * @param string $image                File reference created through load().
     * @param string $newFile              Filename to save the image to.
     * @param string $mime                 New MIME type, if differs from initial one.
     * @param ezcImageSaveOptions $options Save options.
     * @return void
     *
     * @throws ezcImageFileNotProcessableException
     *         If the given file could not be saved with the given MIME type.
     * @throws ezcBaseFilePermissionException
     *         If the desired file exists and is not writeable.
     * @throws ezcImageMimeTypeUnsupportedException
     *         If the desired MIME type is not recognized
     * @throws ezcImageFileNameInvalidException 
     *         If an invalid character (", ', $) is found in the file name.
     */
    public function save( $image, $newFile = null, $mime = null, ezcImageSaveOptions $options = null )
    {
        $options = ( $options === null ) ? new ezcImageSaveOptions() : $options;

        if ( $newFile !== null )
        {
            $this->checkFileName( $newFile );
        }
        
        // Check is transparency must be converted
        if  ( $this->needsTransparencyConversion( $this->getReferenceData( $image, 'mime' ), $mime ) && $options->transparencyReplacementColor !== null )
        {
            $this->replaceTransparency( $image, $options->transparencyReplacementColor );
        }

        $this->saveCommon( $image, isset( $newFile ) ? $newFile : null, isset( $mime ) ? $mime : null );
        $saveFunction = $this->getSaveFunction( $this->getReferenceData( $image, 'mime' ) );

        $saveParams = array(
            $this->getReferenceData( $image, 'resource' ),
            $this->getReferenceData( $image, 'file' ),
        );
        switch ( $saveFunction )
        {
            case "imagejpeg":
                if ( $options->quality !== null )
                {
                    $saveParams[] = $options->quality;
                }
                break;
            case "imagepng":
                if ( $options->compression !== null )
                {
                    $saveParams[] = $options->compression;
                }
                break;
        }

        if ( !ezcBaseFeatures::hasFunction( $saveFunction ) ||
            call_user_func_array( $saveFunction, $saveParams ) === false )
        {
            throw new ezcImageFileNotProcessableException( $file, "Unable to save file '{$file}' of type '{$mime}'." );
        }
    }

    /**
     * Replaces a transparent background with the given color.
     *
     * This method is used to replace the transparent background of an image
     * with an opaque color when converting from a transparency supporting MIME
     * type (e.g. image/png) to a MIME type that does not support transparency.
     *
     * The color 
     * 
     * @param mixed $image 
     * @param mixed $color 
     * @return void
     */
    protected function replaceTransparency( $image, array $color )
    {
        $oldResource = $this->getReferenceData( $image, 'resource' );
        $width  = imagesx( $oldResource );
        $height = imagesy( $oldResource );
        if ( imageistruecolor( $oldResource ) )
        {
            $newResource = imagecreatetruecolor( $width, $height  );
        }
        else
        {
            $newResource = imagecreate( $width, $height );
        }
        
        $bgColor = imagecolorallocate( $newResource, $color[0], $color[1], $color[2] );
        imagefill( $newResource, 0, 0, $bgColor );
        
        // $res = imagecopyresampled(
        $res = imagecopyresampled(
            $newResource,           // destination resource 
            $oldResource,           // source resource
            0,                      // destination x coord
            0,                      // destination y coord
            0,                      // source x coord
            0,                      // source y coord
            $width,                 // destination width
            $height,                // destination height
            $width,                 // source witdh
            $height                 // source height
        );
        if ( $res === false )
        {
            throw new ezcImageFilterFailedException( 'crop', 'Resampling of image failed.' );
        }
        imagedestroy( $oldResource );
        $this->setReferenceData( $image, $newResource, 'resource' );
    }

    /**
     * Close the file referenced by $image.
     * Frees the image reference. You should call close() before.
     *
     * @see ezcImageHandler::load()
     * @see ezcImageHandler::save()
     *
     * @param string $image The image reference.
     * @return void
     */
    public function close( $image )
    {
        $res = $this->getReferenceData( $image, 'resource' );
        if ( is_resource( $res ) )
        {
            imagedestroy( $res );
        }
        $this->closeCommon( $image );
    }

    /**
     * Determine, the image types the available GD extension is able to process.
     *
     * @return void
     */
    private function determineTypes()
    {
        $possibleTypes = array(
            IMG_GIF  => 'image/gif',
            IMG_JPG  => 'image/jpeg',
            IMG_PNG  => 'image/png',
            IMG_WBMP => 'image/wbmp',
            IMG_XPM  => 'image/xpm',
        );
        $imageTypes = imagetypes();
        foreach ( $possibleTypes as $bit => $mime )
        {
            if ( $imageTypes & $bit )
            {
                $this->inputTypes[] = $mime;
                $this->outputTypes[] = $mime;
            }
        }
    }

    /**
     * Generate imagecreatefrom* function out of a MIME type.
     *
     * @param string $mime MIME type in format "image/<type>".
     * @return string imagecreatefrom* function name.
     * 
     * @throws ezcImageMimeTypeUnsupportedException
     *         If the load function for a given MIME type does not exist.
     */
    private function getLoadFunction( $mime )
    {
        if ( !$this->allowsInput( $mime ) )
        {
            throw new ezcImageMimeTypeUnsupportedException( $mime, 'input' );
        }
        return 'imagecreatefrom' . substr( strstr( $mime, '/' ), 1 );
    }

    /**
     * Generate image* function out of a MIME type.
     *
     * @param string $mime MIME type in format "image/<type>".
     * @return string image* function name for saving.
     * 
     * @throws ezcImageImagemagickHandler
     *         If the save function for a given MIME type does not exist.
     */
    private function getSaveFunction( $mime )
    {
        if ( !$this->allowsOutput( $mime ) )
        {
            throw new ezcImageMimeTypeUnsupportedException( $mime, 'output' );
        }
        return 'image' . substr( strstr( $mime, '/' ), 1 );
    }

    /**
     * Creates default settings for the handler and returns it.
     * The reference name will be set to 'GD'.
     *
     * @return ezcImageHandlerSettings
     */
    static public function defaultSettings()
    {
        return new ezcImageHandlerSettings( 'GD', 'ezcImageGdHandler' );
    }

    
}

?>
