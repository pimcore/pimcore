<?php
/**
 * File containing the ezcImageWatermarkFilters interface.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * This interface has to implemented by ezcImageFilters classes to
 * support watermark filters.
 *
 * @see ezcImageHandler
 * @see ezcImageTransformation
 * @see ezcImageFiltersInterface
 *
 * @package ImageConversion
 * @version 1.3.8
 */
interface ezcImageWatermarkFilters
{
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
    public function watermarkPercent( $image, $posX, $posY, $size = false );

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
     */
    public function watermarkAbsolute( $image, $posX, $posY, $width = false, $height = false );
}
?>
