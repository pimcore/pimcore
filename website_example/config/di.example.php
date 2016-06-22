<?php

// Pimcore is using PHP-DI, see http://php-di.org/doc/

return [
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
];
