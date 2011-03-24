<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2009 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Christian Schmidt <schmidt@php.net>                         |
// +----------------------------------------------------------------------+
//
// $Id: Extension.php,v 1.1 2009/01/16 11:49:45 cweiske Exp $

require_once 'PEAR.php';

/**
 * Class for mapping file extensions to MIME types.
 *
 * @category MIME
 * @package  MIME_Type
 * @author   Christian Schmidt <schmidt@php.net>
 * @license  PHP License 3.0
 * @version  1.2.0
 * @link     http://pear.php.net/package/MIME_Type
 */
class MIME_Type_Extension
{
    /**
     * Mapping between file extension and MIME type.
     *
     * @internal The array is sorted alphabetically by value and with primary
     *  extension first. Be careful about not adding duplicate keys - PHP
     *  silently ignores duplicates. The following command can be used for
     *  checking for duplicates:
     *    grep "=> '" Extension.php | cut -d\' -f2 | sort | uniq -d
     *  application/octet-stream is generally used as fallback when no other
     *  MIME-type can be found, but the array does not contain a lot of such
     *  unknown extension. One entry exists, though, to allow detection of
     *  file extension for this MIME-type.
     *
     * @var array
     */
    var $extensionToType = array (
        'ez'        => 'application/andrew-inset',
        'atom'      => 'application/atom+xml',
        'jar'       => 'application/java-archive',
        'hqx'       => 'application/mac-binhex40',
        'cpt'       => 'application/mac-compactpro',
        'mathml'    => 'application/mathml+xml',
        'doc'       => 'application/msword',
        'dat'       => 'application/octet-stream',
        'oda'       => 'application/oda',
        'ogg'       => 'application/ogg',
        'pdf'       => 'application/pdf',
        'ai'        => 'application/postscript',
        'eps'       => 'application/postscript',
        'ps'        => 'application/postscript',
        'rdf'       => 'application/rdf+xml',
        'rss'       => 'application/rss+xml',
        'smi'       => 'application/smil',
        'smil'      => 'application/smil',
        'gram'      => 'application/srgs',
        'grxml'     => 'application/srgs+xml',
        'kml'       => 'application/vnd.google-earth.kml+xml',
        'kmz'       => 'application/vnd.google-earth.kmz',
        'mif'       => 'application/vnd.mif',
        'xul'       => 'application/vnd.mozilla.xul+xml',
        'xls'       => 'application/vnd.ms-excel',
        'xlb'       => 'application/vnd.ms-excel',
        'xlt'       => 'application/vnd.ms-excel',
        'xlam'      => 'application/vnd.ms-excel.addin.macroEnabled.12',
        'xlsb'      => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        'xlsm'      => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        'xltm'      => 'application/vnd.ms-excel.template.macroEnabled.12',
        'docm'      => 'application/vnd.ms-word.document.macroEnabled.12',
        'dotm'      => 'application/vnd.ms-word.template.macroEnabled.12',
        'ppam'      => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        'pptm'      => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        'ppsm'      => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        'potm'      => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
        'ppt'       => 'application/vnd.ms-powerpoint',
        'pps'       => 'application/vnd.ms-powerpoint',
        'odc'       => 'application/vnd.oasis.opendocument.chart',
        'odb'       => 'application/vnd.oasis.opendocument.database',
        'odf'       => 'application/vnd.oasis.opendocument.formula',
        'odg'       => 'application/vnd.oasis.opendocument.graphics',
        'otg'       => 'application/vnd.oasis.opendocument.graphics-template',
        'odi'       => 'application/vnd.oasis.opendocument.image',
        'odp'       => 'application/vnd.oasis.opendocument.presentation',
        'otp'       => 'application/vnd.oasis.opendocument.presentation-template',
        'ods'       => 'application/vnd.oasis.opendocument.spreadsheet',
        'ots'       => 'application/vnd.oasis.opendocument.spreadsheet-template',
        'odt'       => 'application/vnd.oasis.opendocument.text',
        'odm'       => 'application/vnd.oasis.opendocument.text-master',
        'ott'       => 'application/vnd.oasis.opendocument.text-template',
        'oth'       => 'application/vnd.oasis.opendocument.text-web',
        'potx'      => 'application/vnd.openxmlformats-officedocument.presentationml.template',
        'ppsx'      => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'pptx'      => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xlsx'      => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xltx'      => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'docx'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'dotx'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'vsd'       => 'application/vnd.visio',
        'wbxml'     => 'application/vnd.wap.wbxml',
        'wmlc'      => 'application/vnd.wap.wmlc',
        'wmlsc'     => 'application/vnd.wap.wmlscriptc',
        'vxml'      => 'application/voicexml+xml',
        'bcpio'     => 'application/x-bcpio',
        'vcd'       => 'application/x-cdlink',
        'pgn'       => 'application/x-chess-pgn',
        'cpio'      => 'application/x-cpio',
        'csh'       => 'application/x-csh',
        'dcr'       => 'application/x-director',
        'dir'       => 'application/x-director',
        'dxr'       => 'application/x-director',
        'dvi'       => 'application/x-dvi',
        'spl'       => 'application/x-futuresplash',
        'tgz'       => 'application/x-gtar',
        'gtar'      => 'application/x-gtar',
        'hdf'       => 'application/x-hdf',
        'js'        => 'application/x-javascript',
        'skp'       => 'application/x-koan',
        'skd'       => 'application/x-koan',
        'skt'       => 'application/x-koan',
        'skm'       => 'application/x-koan',
        'latex'     => 'application/x-latex',
        'nc'        => 'application/x-netcdf',
        'cdf'       => 'application/x-netcdf',
        'sh'        => 'application/x-sh',
        'shar'      => 'application/x-shar',
        'swf'       => 'application/x-shockwave-flash',
        'sit'       => 'application/x-stuffit',
        'sv4cpio'   => 'application/x-sv4cpio',
        'sv4crc'    => 'application/x-sv4crc',
        'tar'       => 'application/x-tar',
        'tcl'       => 'application/x-tcl',
        'tex'       => 'application/x-tex',
        'texinfo'   => 'application/x-texinfo',
        'texi'      => 'application/x-texinfo',
        't'         => 'application/x-troff',
        'tr'        => 'application/x-troff',
        'roff'      => 'application/x-troff',
        'man'       => 'application/x-troff-man',
        'me'        => 'application/x-troff-me',
        'ms'        => 'application/x-troff-ms',
        'ustar'     => 'application/x-ustar',
        'src'       => 'application/x-wais-source',
        'xhtml'     => 'application/xhtml+xml',
        'xht'       => 'application/xhtml+xml',
        'xslt'      => 'application/xslt+xml',
        'xml'       => 'application/xml',
        'xsl'       => 'application/xml',
        'dtd'       => 'application/xml-dtd',
        'zip'       => 'application/zip',
        'au'        => 'audio/basic',
        'snd'       => 'audio/basic',
        'mid'       => 'audio/midi',
        'midi'      => 'audio/midi',
        'kar'       => 'audio/midi',
        'mpga'      => 'audio/mpeg',
        'mp2'       => 'audio/mpeg',
        'mp3'       => 'audio/mpeg',
        'aif'       => 'audio/x-aiff',
        'aiff'      => 'audio/x-aiff',
        'aifc'      => 'audio/x-aiff',
        'm3u'       => 'audio/x-mpegurl',
        'wma'       => 'audio/x-ms-wma',
        'wax'       => 'audio/x-ms-wax',
        'ram'       => 'audio/x-pn-realaudio',
        'ra'        => 'audio/x-pn-realaudio',
        'rm'        => 'application/vnd.rn-realmedia',
        'wav'       => 'audio/x-wav',
        'pdb'       => 'chemical/x-pdb',
        'xyz'       => 'chemical/x-xyz',
        'bmp'       => 'image/bmp',
        'cgm'       => 'image/cgm',
        'gif'       => 'image/gif',
        'ief'       => 'image/ief',
        'jpeg'      => 'image/jpeg',
        'jpg'       => 'image/jpeg',
        'jpe'       => 'image/jpeg',
        'png'       => 'image/png',
        'svg'       => 'image/svg+xml',
        'tiff'      => 'image/tiff',
        'tif'       => 'image/tiff',
        'djvu'      => 'image/vnd.djvu',
        'djv'       => 'image/vnd.djvu',
        'wbmp'      => 'image/vnd.wap.wbmp',
        'ras'       => 'image/x-cmu-raster',
        'ico'       => 'image/x-icon',
        'pnm'       => 'image/x-portable-anymap',
        'pbm'       => 'image/x-portable-bitmap',
        'pgm'       => 'image/x-portable-graymap',
        'ppm'       => 'image/x-portable-pixmap',
        'rgb'       => 'image/x-rgb',
        'xbm'       => 'image/x-xbitmap',
        'psd'       => 'image/x-photoshop',
        'xpm'       => 'image/x-xpixmap',
        'xwd'       => 'image/x-xwindowdump',
        'eml'       => 'message/rfc822',
        'igs'       => 'model/iges',
        'iges'      => 'model/iges',
        'msh'       => 'model/mesh',
        'mesh'      => 'model/mesh',
        'silo'      => 'model/mesh',
        'wrl'       => 'model/vrml',
        'vrml'      => 'model/vrml',
        'ics'       => 'text/calendar',
        'ifb'       => 'text/calendar',
        'css'       => 'text/css',
        'csv'       => 'text/csv',
        'html'      => 'text/html',
        'htm'       => 'text/html',
        'txt'       => 'text/plain',
        'asc'       => 'text/plain',
        'rtx'       => 'text/richtext',
        'rtf'       => 'text/rtf',
        'sgml'      => 'text/sgml',
        'sgm'       => 'text/sgml',
        'tsv'       => 'text/tab-separated-values',
        'wml'       => 'text/vnd.wap.wml',
        'wmls'      => 'text/vnd.wap.wmlscript',
        'etx'       => 'text/x-setext',
        'mpeg'      => 'video/mpeg',
        'mpg'       => 'video/mpeg',
        'mpe'       => 'video/mpeg',
        'qt'        => 'video/quicktime',
        'mov'       => 'video/quicktime',
        'mxu'       => 'video/vnd.mpegurl',
        'm4u'       => 'video/vnd.mpegurl',
        'flv'       => 'video/x-flv',
        'f4v'       => 'video/mp4',
        'asf'       => 'video/x-ms-asf',
        'asx'       => 'video/x-ms-asf',
        'wmv'       => 'video/x-ms-wmv',
        'wm'        => 'video/x-ms-wm',
        'wmx'       => 'video/x-ms-wmx',
        'avi'       => 'video/x-msvideo',
        'ogv'       => 'video/ogg',
        'movie'     => 'video/x-sgi-movie',
        'ice'       => 'x-conference/x-cooltalk',
    );



    /**
     * Autodetect a file's MIME-type.
     *
     * @param string $file Path to the file to get the type of
     *
     * @return string $file's MIME-type on success, PEAR_Error otherwise
     */
    function getMIMEType($file)
    {
        $extension = substr(strrchr($file, '.'), 1);
        // mod for emotion
        $extension = strtolower($extension);
        
        if ($extension === false) {
            return PEAR::raiseError("File has no extension.");
        }

        if (!isset($this->extensionToType[$extension])) {
            return PEAR::raiseError("Sorry, couldn't determine file type.");
        }

        return $this->extensionToType[$extension];
    }



    /**
     * Return default MIME-type for the specified extension.
     *
     * @param string $type MIME-type
     *
     * @return string A file extension without leading period.
     */
    function getExtension($type)
    {
        require_once 'MIME/Type.php';
        // Strip parameters and comments.
        $type = MIME_Type::getMedia($type) . '/' . MIME_Type::getSubType($type);

        $extension = array_search($type, $this->extensionToType);
        if ($extension === false) {
            return PEAR::raiseError("Sorry, couldn't determine extension.");
        }
        return $extension;
    }

}

?>