<?php
/**
 * This file contains the ezcImageGdHandler class.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * ezcImageHandler implementation for the GD2 extension of PHP, including filters.
 *
 * This ezcImageHandler is used when you want to manipulate images using ext/GD
 * in your application.
 *
 * Note: If you experience problems with loading some JPEG files that work in
 * your image viewer, please set the php.ini directive 'gd.jpeg_ignore_warning'
 * to true (possible via {@link ini_set()}).
 *
 * @see ezcImageConverter
 * @see ezcImageHandler
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageGdHandler extends ezcImageGdBaseHandler
                        implements ezcImageGeometryFilters,
                                   ezcImageColorspaceFilters,
                                   ezcImageWatermarkFilters,
                                   ezcImageThumbnailFilters
{
    /**
     * Scale filter.
     * General scale filter. Scales the image to fit into a given box size, 
     * determined by a given width and height value, measured in pixel. This 
     * method maintains the aspect ratio of the given image. Depending on the
     * given direction value, this method performs the following scales:
     *
     * - ezcImageGeometryFilters::SCALE_BOTH:
     *      The image will be scaled to fit exactly into the given box 
     *      dimensions, no matter if it was smaller or larger as the box
     *      before.
     * - ezcImageGeometryFilters::SCALE_DOWN:
     *      The image will be scaled to fit exactly into the given box 
     *      only if it was larger than the given box dimensions before. If it
     *      is smaller, the image will not be scaled at all.
     * - ezcImageGeometryFilters::SCALE_UP:
     *      The image will be scaled to fit exactly into the given box 
     *      only if it was smaller than the given box dimensions before. If it
     *      is larger, the image will not be scaled at all. ATTENTION:
     *      In this case, the image does not necessarily fit into the given box
     *      afterwards.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageGdHandler::applyFilter()} method, 
     * which enables you to specify the image a filter is applied to.
     *
     * @param int $width     Scale to width
     * @param int $height    Scale to height
     * @param int $direction Scale to which direction.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function scale( $width, $height, $direction = ezcImageGeometryFilters::SCALE_BOTH )
    {

        if ( !is_int( $width ) || $width < 1 )
        {
            throw new ezcBaseValueException( 'width', $width, 'int > 0' );
        }
        if ( !is_int( $height ) || $height < 1 )
        {
            throw new ezcBaseValueException( 'height', $height, 'int > 0' );
        }
        
        $resource = $this->getActiveResource();
        $oldDim = array( 'x' => imagesx( $resource ), 'y' => imagesy( $resource ) );

        $widthRatio = $width / $oldDim['x'];
        $heighRatio = $height / $oldDim['y'];
        
        $ratio = min( $widthRatio, $heighRatio );
        
        switch ( $direction )
        {
            case self::SCALE_DOWN:
                $ratio = $ratio < 1 ? $ratio : 1;
                break;
            case self::SCALE_UP:
                $ratio = $ratio > 1 ? $ratio : 1;
                break;
            case self::SCALE_BOTH:
                break;
            default:
                throw new ezcBaseValueException( 'direction', $direction, 'self::SCALE_BOTH, self::SCALE_UP, self::SCALE_DOWN' );
                break;
        }
        $this->performScale( round( $oldDim['x'] * $ratio ), round( $oldDim['y'] * $ratio ) );
    }

    /**
     * Scale after width filter.
     * Scales the image to a give width, measured in pixel. Scales the height
     * automatically while keeping the ratio. The direction dictates, if an
     * image may only be scaled {@link self::SCALE_UP}, {@link self::SCALE_DOWN}
     * or if the scale may work in {@link self::SCALE_BOTH} directions.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageGdHandler::applyFilter()} method, 
     * which enables you to specify the image a filter is applied to.
     *
     * @param int $width     Scale to width
     * @param int $direction Scale to which direction
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function scaleWidth( $width, $direction )
    {
        if ( !is_int( $width ) || $width < 1 )
        {
            throw new ezcBaseValueException( 'width', $width, 'int > 0' );
        }

        $resource = $this->getActiveResource();
        $oldDim = array(
            'x' => imagesx( $resource ),
            'y' => imagesy( $resource ),
        );
        switch ( $direction )
        {
            case self::SCALE_BOTH:
                $newDim = array(
                    'x' => $width,
                    'y' => $width / $oldDim['x'] * $oldDim['y']
                );
                break;
            case self::SCALE_UP:
                $newDim = array(
                    'x' => max( $width, $oldDim['x'] ),
                    'y' => $width > $oldDim['x'] ? round( $width / $oldDim['x'] * $oldDim['y'] ) : $oldDim['y'],
                );
                break;
            case self::SCALE_DOWN:
                $newDim = array(
                    'x' => min( $width, $oldDim['x'] ),
                    'y' => $width < $oldDim['x'] ? round( $width / $oldDim['x'] * $oldDim['y'] ) : $oldDim['y'],
                );
                break;
            default:
                throw new ezcBaseValueException( 'direction', $direction, 'self::SCALE_BOTH, self::SCALE_UP, self::SCALE_DOWN' );
                break;
        }
        $this->performScale( $newDim["x"], $newDim["y"] );
    }

    /**
     * Scale after height filter.
     * Scales the image to a give height, measured in pixel. Scales the width
     * automatically while keeping the ratio. The direction dictates, if an
     * image may only be scaled {@link self::SCALE_UP}, {@link self::SCALE_DOWN}
     * or if the scale may work in {@link self::SCALE_BOTH} directions.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageGdHandler::applyFilter()} method, 
     * which enables you to specify the image a filter is applied to.
     *
     * @param int $height    Scale to height
     * @param int $direction Scale to which direction
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function scaleHeight( $height, $direction )
    {
        if ( !is_int( $height ) || $height < 1 )
        {
            throw new ezcBaseValueException( 'height', $height, 'int > 0' );
        }

        $resource = $this->getActiveResource();
        $oldDim = array(
            'x' => imagesx( $resource ),
            'y' => imagesy( $resource ),
        );
        switch ( $direction )
        {
            case self::SCALE_BOTH:
                $newDim = array(
                    'x' => $height / $oldDim['y'] * $oldDim['x'],
                    'y' => $height,
                );
                break;
            case self::SCALE_UP:
                $newDim = array(
                    'x' => $height > $oldDim['y'] ? round( $height / $oldDim['y'] * $oldDim['x'] ) : $oldDim['x'],
                    'y' => max( $height, $oldDim['y'] ),
                );
                break;
            case self::SCALE_DOWN:
                $newDim = array(
                    'x' => $height < $oldDim['y'] ? round( $height / $oldDim['y'] * $oldDim['x'] ) : $oldDim['x'],
                    'y' => min( $height, $oldDim['y'] ),
                );
                break;
            default:
                throw new ezcBaseValueException( 'direction', $direction, 'self::SCALE_BOTH, self::SCALE_UP, self::SCALE_DOWN' );
                break;
        }
        $this->performScale( $newDim["x"], $newDim["y"] );
    }

    /**
     * Scale percent measures filter.
     * Scale an image to a given percentage value size.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageGdHandler::applyFilter()} method, 
     * which enables you to specify the image a filter is applied to.
     *
     * @param int $width  Scale to width
     * @param int $height Scale to height
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function scalePercent( $width, $height )
    {
        if ( !is_int( $height ) || $height < 1 )
        {
            throw new ezcBaseValueException( 'height', $height, 'int > 0' );
        }
        if ( !is_int( $width ) || $width < 1 )
        {
            throw new ezcBaseValueException( 'width', $width, 'int > 0' );
        }

        $resource = $this->getActiveResource();
        $this->performScale( round( imagesx( $resource ) * $width / 100 ), round( imagesy( $resource ) * $height / 100 ) );
    }

    /**
     * Scale exact filter.
     * Scale the image to a fixed given pixel size, no matter to which
     * direction.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageGdHandler::applyFilter()} method, 
     * which enables you to specify the image a filter is applied to.
     *
     * @param int $width  Scale to width
     * @param int $height Scale to height
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function scaleExact( $width, $height )
    {
        if ( !is_int( $height ) || $height < 1 )
        {
            throw new ezcBaseValueException( 'height', $height, 'int > 0' );
        }
        if ( !is_int( $width ) || $width < 1 )
        {
            throw new ezcBaseValueException( 'width', $width, 'int > 0' );
        }
        $this->performScale( $width, $height );
    }

    /**
     * Crop filter.
     * Crop an image to a given size. This takes cartesian coordinates of a
     * rect area to crop from the image. The cropped area will replace the old
     * image resource (not the input image immediately, if you use the
     * {@link ezcImageConverter}).  Coordinates are given as integer values and
     * are measured from the top left corner.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageGdHandler::applyFilter()} method, 
     * which enables you to specify the image a filter is applied to.
     *
     * @param int $x      X offset of the cropping area.
     * @param int $y      Y offset of the cropping area.
     * @param int $width  Width of cropping area.
     * @param int $height Height of cropping area.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function crop( $x, $y, $width, $height )
    {
        if ( !is_int( $x ) )
        {
            throw new ezcBaseValueException( 'x', $x, 'int' );
        }
        if ( !is_int( $y ) )
        {
            throw new ezcBaseValueException( 'y', $y, 'int' );
        }
        if ( !is_int( $height ) )
        {
            throw new ezcBaseValueException( 'height', $height, 'int' );
        }
        if ( !is_int( $width ) )
        {
            throw new ezcBaseValueException( 'width', $width, 'int' );
        }
        
        $oldResource = $this->getActiveResource();
        
        $sourceWidth = imagesx( $oldResource );
        $sourceHeight = imagesy( $oldResource );

        $x = ( $x >= 0 ) ? $x : $sourceWidth  + $x;
        $y = ( $y >= 0 ) ? $y : $sourceHeight + $y;
        
        $x = abs( min( $x, $x + $width ) );
        $y = abs( min( $y, $y + $height ) );
       
        $width = abs( $width );
        $height = abs( $height );

        if ( $x + $width > $sourceWidth )
        {
            $width = $sourceWidth - $x;
        }
        if ( $y + $height > $sourceHeight )
        {
            $height = $sourceHeight - $y;
        }
        
        $this->performCrop( $x, $y, $width, $height );

    }

    /**
     * Colorspace filter.
     * Transform the colorspace of the picture. The following colorspaces are
     * supported:
     *
     * - {@link self::COLORSPACE_GREY} - 255 grey colors
     * - {@link self::COLORSPACE_SEPIA} - Sepia colors
     * - {@link self::COLORSPACE_MONOCHROME} - 2 colors black and white
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageGdHandler::applyFilter()} method, 
     * which enables you to specify the image a filter is applied to.
     *
     * @param int $space Colorspace, one of self::COLORSPACE_* constants.
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcBaseValueException
     *         If the parameter submitted as the colorspace was not within the
     *         self::COLORSPACE_* constants.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     */
    public function colorspace( $space )
    {
        switch ( $space )
        {
            case self::COLORSPACE_GREY:
                $this->luminanceColorScale( array( 1.0, 1.0, 1.0 ) );
                break;
            case self::COLORSPACE_MONOCHROME:
                $this->thresholdColorScale(
                    array(
                        127 => array( 0, 0, 0 ),
                        255 => array( 255, 255, 255 ),
                    )
                );
                break;
            return;
            case self::COLORSPACE_SEPIA:
                $this->luminanceColorScale( array( 1.0, 0.89, 0.74 ) );
                break;
            default:
                throw new ezcBaseValueException( 'space', $space, 'self::COLORSPACE_GREY, self::COLORSPACE_SEPIA, self::COLORSPACE_MONOCHROME' );
                break;
        }

    }
    
    /**
     * Watermark filter.
     * Places a watermark on the image. The file to use as the watermark image
     * is given as $image. The $posX, $posY and $size values are given in
     * percent, related to the destination image. A $size value of 10 will make
     * the watermark appear in 10% of the destination image size.
     * $posX = $posY = 10 will make the watermark appear in the top left corner
     * of the destination image, 10% of its size away from its borders. If
     * $size is ommitted, the watermark image will not be resized.
     *
     * @param string $image  The image file to use as the watermark
     * @param int $posX      X position in the destination image in percent.
     * @param int $posY      Y position in the destination image in percent.
     * @param int|bool $size Percentage size of the watermark, false for none.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function watermarkPercent( $image, $posX, $posY, $size = false )
    {
        if ( !is_string( $image ) || !file_exists( $image ) || !is_readable( $image ) ) 
        {
            throw new ezcBaseValueException( 'image', $image, 'string, path to an image file' );
        }
        if ( !is_int( $posX ) || $posX < 0 || $posX > 100 )
        {
            throw new ezcBaseValueException( 'posX', $posX, 'int percentage value' );
        }
        if ( !is_int( $posY ) || $posY < 0 || $posY > 100 )
        {
            throw new ezcBaseValueException( 'posY', $posY, 'int percentage value' );
        }
        if ( !is_bool( $size ) && ( !is_int( $size ) || $size < 0 || $size > 100 ) )
        {
            throw new ezcBaseValueException( 'size', $size, 'int percentage value / bool' );
        }
        
        $imgWidth = imagesx( $this->getActiveResource() );
        $imgHeight = imagesy( $this->getActiveResource() );

        $watermarkWidth = false;
        $watermarkHeight = false;
        if ( $size !== false )
        {
            $watermarkWidth = (int) round( $imgWidth * $size / 100 );
            $watermarkHeight = (int) round( $imgHeight * $size / 100 );
        }

        $watermarkPosX = (int) round( $imgWidth * $posX / 100 );
        $watermarkPosY = (int) round( $imgHeight * $posY / 100 );

        $this->watermarkAbsolute( $image, $watermarkPosX, $watermarkPosY, $watermarkWidth, $watermarkHeight );
    }

    /**
     * Watermark filter.
     * Places a watermark on the image. The file to use as the watermark image
     * is given as $image. The $posX, $posY and $size values are given in
     * pixel. The watermark appear at $posX, $posY in the destination image
     * with a size of $size pixel. If $size is ommitted, the watermark image
     * will not be resized.
     *
     * @param string $image    The image file to use as the watermark
     * @param int $posX        X position in the destination image in pixel.
     * @param int $posY        Y position in the destination image in pixel.
     * @param int|bool $width  Pixel size of the watermark, false to keep size.
     * @param int|bool $height Pixel size of the watermark, false to keep size.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     * @throws ezcImageFileNotProcessableException
     *         If the given watermark image could not be loaded.
     */
    public function watermarkAbsolute( $image, $posX, $posY, $width = false, $height = false )
    {
        if ( !is_string( $image ) || !file_exists( $image ) || !is_readable( $image ) )
        {
            throw new ezcBaseValueException( 'image', $image, 'string, path to an image file' );
        }
        if ( !is_int( $posX ) )
        {
            throw new ezcBaseValueException( 'posX', $posX, 'int' );
        }
        if ( !is_int( $posY ) )
        {
            throw new ezcBaseValueException( 'posY', $posY, 'int' );
        }
        if ( !is_int( $width ) && !is_bool( $width ) )
        {
            throw new ezcBaseValueException( 'width', $width, 'int/bool' );
        }
        if ( !is_int( $height ) && !is_bool( $height ) )
        {
            throw new ezcBaseValueException( 'height', $height, 'int/bool' );
        }
        
        // Backup original image reference
        $originalRef = $this->getActiveReference();

        $originalWidth  = imagesx( $this->getActiveResource() );
        $originalHeight = imagesy( $this->getActiveResource() );

        $watermarkRef = $this->load( $image );
        if ( $width !== false && $height !== false && ( $originalWidth !== $width || $originalHeight !== $height ) )
        {
            $this->scale( $width, $height, ezcImageGeometryFilters::SCALE_BOTH );
        }

        // Negative offsets
        $posX = ( $posX >= 0 ) ? $posX : $originalWidth  + $posX;
        $posY = ( $posY >= 0 ) ? $posY : $originalHeight + $posY;

        imagecopy(
            $this->getReferenceData( $originalRef, "resource" ),                // resource $dst_im
            $this->getReferenceData( $watermarkRef, "resource" ),               // resource $src_im
            $posX,                                                              // int $dst_x
            $posY,                                                              // int $dst_y
            0,                                                                  // int $src_x
            0,                                                                  // int $src_y
            imagesx( $this->getReferenceData( $watermarkRef, "resource" ) ),    // int $src_w
            imagesy( $this->getReferenceData( $watermarkRef, "resource" ) )     // int $src_h
        );

        $this->close( $watermarkRef );
        
        // Restore original image reference
        $this->setActiveReference( $originalRef );
    }
    
    /**
     * Creates a thumbnail, and crops parts of the given image to fit the range best.
     * This filter creates a thumbnail of the given image. The image is scaled
     * down, keeping the original ratio and keeping the image larger as the
     * given range, if necessary. Overhead for the target range is cropped from
     * both sides equally.
     *
     * If you are looking for a filter that just resizes your image to
     * thumbnail size, you should consider the {@link
     * ezcImageGdHandler::scale()} filter.
     * 
     * @param int $width  Width of the thumbnail.
     * @param int $height Height of the thumbnail.
     */
    public function croppedThumbnail( $width, $height )
    {
        if ( !is_int( $width )  || $width < 1 )
        {
            throw new ezcBaseValueException( 'width', $width, 'int > 0' );
        }
        if ( !is_int( $height )  || $height < 1 )
        {
            throw new ezcBaseValueException( 'height', $height, 'int > 0' );
        }
        $resource = $this->getActiveResource();
        $data[0] = imagesx( $resource );
        $data[1] = imagesy( $resource );
        
        $scaleRatio  = max( $width / $data[0], $height / $data[1] );
        $scaleWidth  = round( $data[0] * $scaleRatio );
        $scaleHeight = round( $data[1] * $scaleRatio );
        
        $cropOffsetX = ( $scaleWidth > $width )   ? round( ( $scaleWidth - $width ) / 2 )   : 0;
        $cropOffsetY = ( $scaleHeight > $height ) ? round( ( $scaleHeight - $height ) / 2 ) : 0;

        $this->performScale( $scaleWidth, $scaleHeight );
        $this->performCrop( $cropOffsetX, $cropOffsetY, $width, $height );
    }

    /**
     * Creates a thumbnail, and fills up the image to fit the given range.
     * This filter creates a thumbnail of the given image. The image is scaled
     * down, keeping the original ratio and scaling the image smaller as the
     * given range, if necessary. Overhead for the target range is filled with the given
     * color on both sides equally.
     *
     * The color is defined by the following array format (integer values 0-255):
     *
     * <code>
     * array( 
     *      0 => <red value>,
     *      1 => <green value>,
     *      2 => <blue value>,
     * );
     * </code>
     *
     * If you are looking for a filter that just resizes your image to
     * thumbnail size, you should consider the {@link
     * ezcImageGdHandler::scale()} filter.
     * 
     * @param int $width  Width of the thumbnail.
     * @param int $height Height of the thumbnail.
     * @param array $color Fill color.
     *
     * @return void
     */
    public function filledThumbnail( $width, $height, $color = array() )
    {
        $i = 0;
        foreach ( $color as $id => $colorVal )
        {
            if ( $i++ > 2 )
            {
                break;
            }
            if ( !is_int( $colorVal )  || $colorVal < 0 || $colorVal > 255 )
            {
                throw new ezcBaseValueException( "color[$id]", $color[$id], 'int > 0 and < 256' );
            }
        }
        
        // Sanity checks for $width and $height performed by scale() method.
        $this->scale( $width, $height, ezcImageGeometryFilters::SCALE_BOTH );
        
        $oldResource = $this->getActiveResource();

        $realWidth   = imagesx( $oldResource );
        $realHeight  = imagesy( $oldResource );
        $xOffset     = ( $width > $realWidth )   ? round( ( $width - $realWidth ) / 2 )   : 0;
        $yOffset     = ( $height > $realHeight ) ? round( ( $height - $realHeight ) / 2 ) : 0;

        $newResource = imagecreatetruecolor( $width, $height );
        $bgColor     = $this->getColor( $newResource, $color[0], $color[1], $color[2] );
        if ( imagefill( $newResource, 0, 0, $bgColor ) === false )
        {
            throw new ezcImageFilterFailedException( "filledThumbnail", "Color fill failed." );
        }

        imagecopy(
            $newResource,
            $oldResource,
            $xOffset,
            $yOffset,
            0,
            0,
            $realWidth,
            $realHeight
        );

        $this->setActiveResource( $newResource );
        imagedestroy( $oldResource );
    }

    // private

    /**
     * Retrieve luminance value for a specific pixel.
     *
     * @param resource(GD) $resource Image resource
     * @param int $x                 Pixel x coordinate.
     * @param int $y                 Pixel y coordinate.
     * @return float Luminance value.
     */
    private function getLuminanceAt( $resource, $x, $y )
    {
            $currentColor = imagecolorat( $resource, $x, $y );
            $rgbValues = array(
                'r' => ( $currentColor >> 16 ) & 0xff,
                'g' => ( $currentColor >> 8 ) & 0xff,
                'b' => $currentColor & 0xff,
            );
            return $rgbValues['r'] * 0.299 + $rgbValues['g'] * 0.587 + $rgbValues['b'] * 0.114;
    }

    /**
     * Scale colors by threshold values.
     * Thresholds are defined by the following array structures:
     *
     * <code>
     * array(
     *  <int threshold value> => array(
     *      0 => <int red value>,
     *      1 => <int green value>,
     *      2 => <int blue value>,
     *  ),
     * )
     * </code>
     *
     * @param array $thresholds
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     *
     * @todo Optimization as described here: http://lists.ez.no/pipermail/components/2005-November/000566.html
     */
    protected function thresholdColorScale( $thresholds )
    {
        $resource = $this->getActiveResource();
        $dimensions = array( 'x' => imagesx( $resource ), 'y' => imagesy( $resource ) );

        // Check for GIFs and convert them to work properly here.
        if ( !imageistruecolor( $resource ) )
        {
            $resource = $this->paletteToTruecolor( $resource, $dimensions );
        }

        foreach ( $thresholds as $threshold => $colors )
        {
            $thresholds[$threshold] = array_merge(
                $colors,
                array( 'color' => $this->getColor( $resource, $colors[0], $colors[1], $colors[2] ) )
            );
        }
        // Default
        if ( !isset( $thresholds[255] ) )
        {
            $thresholds[255] = end( $thresholds );
            reset( $thresholds );
        }

        $colorCache = array();

        for ( $x = 0; $x < $dimensions['x']; $x++ )
        {
            for ( $y = 0; $y < $dimensions['y']; $y++ )
            {
                $luminance = $this->getLuminanceAt( $resource, $x, $y );
                $color = end( $thresholds );
                foreach ( $thresholds as $threshold => $colorValues )
                {
                    if ( $luminance <= $threshold )
                    {
                        $color = $colorValues;
                        break;
                    }
                }
                imagesetpixel( $resource, $x, $y, $color['color'] );
            }
        }

        $this->setActiveResource( $resource );
    }

    /**
     * Perform luminance color scale.
     *
     * @param array $scale Array of RGB values (numeric index).
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed
     *
     * @todo Optimization as described here: http://lists.ez.no/pipermail/components/2005-November/000566.html
     */
    protected function luminanceColorScale( $scale )
    {
        $resource = $this->getActiveResource();
        $dimensions = array( 'x' => imagesx( $resource ), 'y' => imagesy( $resource ) );

        // Check for GIFs and convert them to work properly here.
        if ( !imageistruecolor( $resource ) )
        {
            $resource = $this->paletteToTruecolor( $resource, $dimensions );
        }

        for ( $x = 0; $x < $dimensions['x']; $x++ )
        {
            for ( $y = 0; $y < $dimensions['y']; $y++ )
            {
                $luminance = $this->getLuminanceAt( $resource, $x, $y );
                $newRgbValues = array(
                    'r' => $luminance * $scale[0],
                    'g' => $luminance * $scale[1],
                    'b' => $luminance * $scale[2],
                );
                $color = $this->getColor( $resource, $newRgbValues['r'], $newRgbValues['g'], $newRgbValues['b'] );
                imagesetpixel( $resource, $x, $y, $color );
            }
        }
        $this->setActiveResource( $resource );
    }

    /**
     * Convert a palette based image resource to a true color one.
     * Takes a GD resource that does not represent a true color image and
     * converts it to a true color based resource. Do not forget, to replace
     * the actual resource in the handler, if you use this ,method!
     *
     * @param resource(GD) $resource         The image resource to convert
     * @param array(string=>int) $dimensions X and Y dimensions.
     * @return resource(GD) The converted resource.
     */
    protected function paletteToTruecolor( $resource, $dimensions )
    {
        $newResource = imagecreatetruecolor( $dimensions['x'], $dimensions['y'] );
        imagecopy( $newResource, $resource, 0, 0, 0, 0, $dimensions['x'], $dimensions['y'] );
        imagedestroy( $resource );
        return $newResource;
    }

    /**
     * Common color determination method.
     * Returns a color identifier for an RGB value. Avoids problems with palette images.
     *
     * @param reource(GD) $resource The image resource to get a color for.
     * @param int $r                Red value.
     * @param int $g                Green value.
     * @param int $b                Blue value.
     *
     * @return int The color identifier.
     *
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     */
    protected function getColor( $resource, $r, $g, $b )
    {
        if ( ( $res = imagecolorexact( $resource, $r, $g, $b ) ) !== -1 )
        {
            return $res;
        }
        if ( ( $res = imagecolorallocate( $resource, $r, $g, $b ) ) !== -1 )
        {
            return $res;
        }
        if ( ( $res = imagecolorclosest( $resource, $r, $g, $b ) ) !== -1 )
        {
            return $res;
        }
        throw new ezcImageFilterFailedException( 'allocatecolor', "Color allocation failed for color r: '{$r}', g: '{$g}', b: '{$b}'." );
    }

    /**
     * General scaling method to perform actual scale to new dimensions.
     *
     * @param int $width  Width.
     * @param int $height Height.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException.
     *         If the operation performed by the the filter failed.
     */
    protected function performScale( $width, $height )
    {
        $oldResource = $this->getActiveResource();
        if ( imageistruecolor( $oldResource ) )
        {
            $newResource = imagecreatetruecolor( $width, $height );
        }
        else
        {
            $newResource = imagecreate( $width, $height );
        }
   
        // Save transparency, if image has it
        $bgColor = imagecolorallocatealpha(  $newResource, 255, 255, 255, 127 );
        imagealphablending(  $newResource, true );
        imagesavealpha(  $newResource, true );
        imagefill(  $newResource, 1, 1, $bgColor );

        $res = imagecopyresampled(
            $newResource,
            $oldResource,
            0, 0, 0, 0,
            $width,
            $height,
            imagesx( $this->getActiveResource() ),
            imagesy( $this->getActiveResource() )
        );
        if ( $res === false )
        {
            throw new ezcImageFilterFailedException( 'scale', 'Resampling of image failed.' );
        }
        imagedestroy( $oldResource );
        $this->setActiveResource( $newResource );
    }

    /**
     * General method to perform a crop operation. 
     * 
     * @param int $x 
     * @param int $y 
     * @param int $width 
     * @param int $height 
     * @return void
     */
    private function performCrop( $x, $y, $width, $height )
    {
        $oldResource = $this->getActiveResource();
        if ( imageistruecolor( $oldResource ) )
        {
            $newResource = imagecreatetruecolor( $width, $height  );
        }
        else
        {
            $newResource = imagecreate( $width, $height );
        }
        
        // Save transparency, if image has it
        $bgColor = imagecolorallocatealpha(  $newResource, 255, 255, 255, 127 );
        imagealphablending(  $newResource, true );
        imagesavealpha(  $newResource, true );
        imagefill(  $newResource, 1, 1, $bgColor );
        
        $res = imagecopyresampled(
            $newResource,           // destination resource 
            $oldResource,           // source resource
            0,                      // destination x coord
            0,                      // destination y coord
            $x,                     // source x coord
            $y,                     // source y coord
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
        $this->setActiveResource( $newResource );
    }
}
?>
