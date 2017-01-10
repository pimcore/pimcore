<?php

// Pimcore is using PHP-DI, see http://php-di.org/doc/

return [

    // Custom class mappings for Pimcore models
    'Pimcore\Model\Object\News' => DI\object('Website\Model\News'),
    'Pimcore\Model\Object\News\List' => DI\object('Website\Model\News\Listing'),
    'Pimcore\Model\Object\Folder' => DI\object('Website\Model\Object\Folder'),
    'Pimcore\Model\Object\Listing' => DI\object('Website\Model\Object\Listing'),

    'Pimcore\Model\Asset\Folder' => DI\object('Website\Model\Asset\Folder'),
    'Pimcore\Model\Asset\Image' => DI\object('Website\Model\Asset\Image'),

    'Pimcore\Model\Document\Page' => DI\object('Website\Model\Document\Page'),
    'Pimcore\Model\Document\Snippet' => DI\object('Website\Model\Document\Snippet'),
    'Pimcore\Model\Document\Link' => DI\object('Website\Model\Document\Link'),
    'Pimcore\Model\Document\Listing' => DI\object('Website\Model\Document\Listing'),

    // customize the full path to external executables
    // normally they are auto-detected by `which program` or auto-discovered in the configured path in
    // System Settings -> General -> Additional $PATH variable
    // but in general it's a good idea to have your programs in your $PATH environment variable (system wide)
    "executable.composer" => "php " . PIMCORE_DOCUMENT_ROOT . "/vendor/bin/composer.phar",
    "executable.html2text" => "/usr/local/html2text/bin/html2text",
    "executable.soffice" => "/opt/libreoffice/bin/soffice",
    "executable.gs" => "/opt/ghostscript/bin/gs",
    "executable.pdftotext" => "/opt/tools/pdftotext",
    "executable.xvfb-run" => "/opt/tools/xvfb-run",
    "executable.pngcrush" => "/opt/tools/pngcrush",
    "executable.zopflipng" => "/opt/tools/zopflipng",
    "executable.pngout" => "/opt/tools/pngout",
    "executable.advpng" => "/opt/tools/advpng",
    "executable.cjpeg" => "/opt/tools/cjpeg",
    "executable.jpegoptim" => "/opt/tools/jpegoptim",
    "executable.php" => "/usr/local/custom-php/bin/php",
    "executable.nice" => "/opt/tools/nice",
    "executable.nohup" => "/opt/tools/nohup",
    "executable.ffmpeg" => "/opt/tools/ffmpeg",
    "executable.exiftool" => "/opt/tools/exiftool",


    // Customize the image processing library
    \Pimcore\Image\Adapter::class => DI\object('Pimcore\Image\Adapter\ImageMagick')
        // can be used to customize the path to ImageMagick
        //->method('setConvertScriptPath', '/usr/bin/convert')
        //->method('setCompositeScriptPath', '/usr/bin/composite')
];
