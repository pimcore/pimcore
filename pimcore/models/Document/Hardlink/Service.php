<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document\Hardlink;

use Pimcore\Model;
use Pimcore\Tool\Serialize;
use Pimcore\Model\Document;

class Service {

    /**
     * @param Document $doc
     * @return Document
     * @throws Model\Exception
     */
    public static function wrap(Document $doc) {

        if($doc instanceof Document\Hardlink) {
            if($sourceDoc = $doc->getSourceDocument()) {
                $destDoc = self::upperCastDocument($sourceDoc);
                $destDoc->setKey($doc->getKey());
                $destDoc->setPath($doc->getRealPath());
                $destDoc->initResource(get_class($sourceDoc));
                $destDoc->setHardLinkSource($doc);
                return $destDoc;
            }
        } else {
            $sourceClass = get_class($doc);
            $doc = self::upperCastDocument($doc);
            $doc->initResource($sourceClass);
            return $doc;
        }

        return;
    }

    /**
     * @static
     * @param Document $doc
     * @return Document
     */
    public static function upperCastDocument (Document $doc) {

        $to_class = "Pimcore\\Model\\Document\\Hardlink\\Wrapper\\" . ucfirst($doc->getType());

        $old_serialized_prefix  = "O:".strlen(get_class($doc));
        $old_serialized_prefix .= ":\"".get_class($doc)."\":";

        // unset eventually existing children, because of performance reasons when serializing the document
        $doc->setChilds(null);

        $old_serialized_object = Serialize::serialize($doc);
        $new_serialized_object = 'O:'.strlen($to_class).':"'.$to_class . '":';
        $new_serialized_object .= substr($old_serialized_object,strlen($old_serialized_prefix));

        $document = Serialize::unserialize($new_serialized_object);
        return $document;
    }

    /**
     * this is used to get childs below a hardlink by a path
     * for example: the requested path is /de/service/contact but /de/service is a hardlink to /en/service
     * then $hardlink would be /en/service and $path /de/service/contact and this function returns then /en/service/contact
     *
     * @param Document\Hardlink $hardlink
     * @param string $path
     * @return Document
     */
    public static function getChildByPath (Document\Hardlink $hardlink, $path) {
        if($hardlink->getChildsFromSource() && $hardlink->getSourceDocument()) {
            $hardlinkRealPath = preg_replace("@^" . preg_quote($hardlink->getRealFullPath()) . "@", $hardlink->getSourceDocument()->getRealFullPath(), $path);
            $hardLinkedDocument = Document::getByPath($hardlinkRealPath);
            if($hardLinkedDocument instanceof Document) {
                $hardLinkedDocument = self::wrap($hardLinkedDocument);
                $hardLinkedDocument->setHardLinkSource($hardlink);

                $_path = $path != "/" ? $_path = dirname($path) : $path;
                $_path = str_replace("\\", "/", $_path); // windows patch
                $_path .= $_path != "/" ? "/" : "";

                $hardLinkedDocument->setPath($_path);
                return $hardLinkedDocument;
            }
        }
        return null;
    }

    /**
     * @param Document\Hardlink $hardlink
     * @param $path
     * @return Document
     */
    public static function getNearestChildByPath(Document\Hardlink $hardlink, $path) {

        if($hardlink->getChildsFromSource() && $hardlink->getSourceDocument()) {
            $hardlinkRealPath = preg_replace("@^" . preg_quote($hardlink->getRealFullPath()) . "@", $hardlink->getSourceDocument()->getRealFullPath(), $path);
            $pathes = array();

            $pathes[] = "/";
            $pathParts = explode("/", $hardlinkRealPath);
            $tmpPathes = array();
            foreach ($pathParts as $pathPart) {
                $tmpPathes[] = $pathPart;
                $t = implode("/", $tmpPathes);
                if (!empty($t)) {
                    $pathes[] = $t;
                }
            }

            $pathes = array_reverse($pathes);

            foreach ($pathes as $p) {
                $hardLinkedDocument = Document::getByPath($p);
                if($hardLinkedDocument instanceof Document) {
                    $hardLinkedDocument = self::wrap($hardLinkedDocument);
                    $hardLinkedDocument->setHardLinkSource($hardlink);

                    $_path = $path != "/" ? $_path = dirname($p) : $p;
                    $_path = str_replace("\\", "/", $_path); // windows patch
                    $_path .= $_path != "/" ? "/" : "";

                    $_path = preg_replace("@^" . preg_quote($hardlink->getSourceDocument()->getRealFullPath()) . "@", $hardlink->getRealFullPath(), $_path);

                    $hardLinkedDocument->setPath($_path);
                    return $hardLinkedDocument;
                }
            }
        }
    }
}
