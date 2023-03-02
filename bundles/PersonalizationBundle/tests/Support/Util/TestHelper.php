<?php

namespace Pimcore\Bundle\PersonalizationBundle\Tests\Util;

use Pimcore\Bundle\PersonalizationBundle\Model\Document\Page;

class TestHelper
{
    public static function createEmptyPage(?string $keyPrefix = '', bool $save = true, bool $publish = true): Page
    {
        if (null === $keyPrefix) {
            $keyPrefix = '';
        }

        $document = new Page();
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->setKey($keyPrefix . uniqid() . rand(10, 99));

        if ($publish) {
            $document->setPublished(true);
        }

        if ($save) {
            $document->save();
        }

        return $document;
    }
}
