<?php
/**
 * Backward compatibility for PHP < 4.3.0
 *
 * @author Philippe.Jausions @at@ 11abacus.com
 **/

include_once('Image/Transform/IMAGETYPE.php');

function image_type_to_mime_type($type) {
    switch ($type) {
        case IMAGETYPE_GIF:
            return 'image/gif';

        case IMAGETYPE_JPEG:
            return 'image/jpeg';

        case IMAGETYPE_PNG:
            return 'image/png';

        case IMAGETYPE_PSD:
            return 'image/psd';

        case IMAGETYPE_BMP:
            return 'image/bmp';

        case IMAGETYPE_TIFF_II:
        case IMAGETYPE_TIFF_MM:
            return 'image/tiff';

        case IMAGETYPE_JP2:
            return 'image/jp2';

        case IMAGETYPE_SWF:
        case IMAGETYPE_SWC:
            return 'application/x-shockwave-flash';

        case IMAGETYPE_IFF:
            return 'image/iff';

        case IMAGETYPE_WBMP:
            return 'image/vnd.wap.wbmp';

        case IMAGETYPE_XBM:
            return 'image/xbm';

        case IMAGETYPE_JPC:
        case IMAGETYPE_JPX:
        case IMAGETYPE_JB2:
        default:
            return 'application/octect-stream';

    }
}

?>