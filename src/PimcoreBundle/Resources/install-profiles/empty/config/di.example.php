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
    "pimcore.executable.composer" => "php " . PIMCORE_DOCUMENT_ROOT . "/vendor/bin/composer.phar",
    "pimcore.executable.html2text" => "/usr/local/html2text/bin/html2text",
    "pimcore.executable.soffice" => "/opt/libreoffice/bin/soffice",
    "pimcore.executable.gs" => "/opt/ghostscript/bin/gs",
    "pimcore.executable.pdftotext" => "/opt/tools/pdftotext",
    "pimcore.executable.xvfb-run" => "/opt/tools/xvfb-run",
    "pimcore.executable.pngcrush" => "/opt/tools/pngcrush",
    "pimcore.executable.zopflipng" => "/opt/tools/zopflipng",
    "pimcore.executable.pngout" => "/opt/tools/pngout",
    "pimcore.executable.advpng" => "/opt/tools/advpng",
    "pimcore.executable.cjpeg" => "/opt/tools/cjpeg",
    "pimcore.executable.jpegoptim" => "/opt/tools/jpegoptim",
    "pimcore.executable.php" => "/usr/local/custom-php/bin/php",
    "pimcore.executable.nice" => "/opt/tools/nice",
    "pimcore.executable.nohup" => "/opt/tools/nohup",
    "pimcore.executable.ffmpeg" => "/opt/tools/ffmpeg",
    "pimcore.executable.exiftool" => "/opt/tools/exiftool",


    // Customize the image processing library
    \Pimcore\Image\Adapter::class => DI\object('Pimcore\Image\Adapter\ImageMagick')
        // can be used to customize the path to ImageMagick
        //->method('setConvertScriptPath', '/usr/bin/convert')
        //->method('setCompositeScriptPath', '/usr/bin/composite')
];
