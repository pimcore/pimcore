<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Hardlink;

use Pimcore\Model\Document;
use Pimcore\Tool\Serialize;

class Service
{
    /**
     * @param Document $doc
     *
     * @return Document\Hardlink\Wrapper\WrapperInterface|null
     *
     * @throws \Exception
     */
    public static function wrap(Document $doc)
    {
        if ($doc instanceof Document\Hardlink) {
            if ($sourceDoc = $doc->getSourceDocument()) {
                /** @var Document\Hardlink\Wrapper\Hardlink $destDoc */
                $destDoc = self::upperCastDocument($sourceDoc);
                $destDoc->setKey($doc->getKey());
                $destDoc->setPath($doc->getRealPath());
                $destDoc->initDao(get_class($sourceDoc), true);
                $destDoc->setHardLinkSource($doc);
                $destDoc->setSourceDocument($sourceDoc);

                return $destDoc;
            }
        } else {
            $destDoc = self::upperCastDocument($doc);
            $destDoc->initDao(get_class($doc), true);
            $destDoc->setSourceDocument($doc);

            return $destDoc;
        }

        return null;
    }

    /**
     * @static
     *
     * @param Document $doc
     *
     * @return Document\Hardlink\Wrapper\WrapperInterface
     */
    public static function upperCastDocument(Document $doc)
    {
        $to_class = 'Pimcore\\Model\\Document\\Hardlink\\Wrapper\\' . ucfirst($doc->getType());

        $old_serialized_prefix = 'O:'.strlen(get_class($doc));
        $old_serialized_prefix .= ':"'.get_class($doc).'":';

        // unset eventually existing children, because of performance reasons when serializing the document
        $doc->setChildren(null);

        $old_serialized_object = Serialize::serialize($doc);
        $new_serialized_object = 'O:'.strlen($to_class).':"'.$to_class . '":';
        $new_serialized_object .= substr($old_serialized_object, strlen($old_serialized_prefix));

        $document = Serialize::unserialize($new_serialized_object);

        return $document;
    }

    /**
     * this is used to get children below a hardlink by a path
     * for example: the requested path is /de/service/contact but /de/service is a hardlink to /en/service
     * then $hardlink would be /en/service and $path /de/service/contact and this function returns then /en/service/contact
     *
     * @param Document\Hardlink $hardlink
     * @param string $path
     *
     * @return Document\Hardlink\Wrapper\WrapperInterface|null
     */
    public static function getChildByPath(Document\Hardlink $hardlink, $path)
    {
        if ($hardlink->getChildrenFromSource() && $hardlink->getSourceDocument()) {
            $hardlinkRealPath = preg_replace('@^' . preg_quote($hardlink->getRealFullPath(), '@') . '@', $hardlink->getSourceDocument()->getRealFullPath(), $path);
            $hardLinkedDocument = Document::getByPath($hardlinkRealPath);
            if ($hardLinkedDocument instanceof Document) {
                $hardLinkedDocument = self::wrap($hardLinkedDocument);
                $hardLinkedDocument->setHardLinkSource($hardlink);

                $_path = $path != '/' ? $_path = dirname($path) : $path;
                $_path = str_replace('\\', '/', $_path); // windows patch
                $_path .= $_path != '/' ? '/' : '';

                $hardLinkedDocument->setPath($_path);

                return $hardLinkedDocument;
            }
        }

        return null;
    }

    /**
     * @param Document\Hardlink $hardlink
     * @param string $path
     *
     * @return Document\Hardlink\Wrapper\WrapperInterface|null
     */
    public static function getNearestChildByPath(Document\Hardlink $hardlink, $path)
    {
        if ($hardlink->getChildrenFromSource() && $hardlink->getSourceDocument()) {
            $hardlinkRealPath = preg_replace('@^' . preg_quote($hardlink->getRealFullPath(), '@') . '@', $hardlink->getSourceDocument()->getRealFullPath(), $path);
            $pathes = [];

            $pathes[] = '/';
            $pathParts = explode('/', $hardlinkRealPath);
            $tmpPathes = [];
            foreach ($pathParts as $pathPart) {
                $tmpPathes[] = $pathPart;
                $t = implode('/', $tmpPathes);
                if (!empty($t)) {
                    $pathes[] = $t;
                }
            }

            $pathes = array_reverse($pathes);

            foreach ($pathes as $p) {
                $hardLinkedDocument = Document::getByPath($p);
                if ($hardLinkedDocument instanceof Document) {
                    $hardLinkedDocument = self::wrap($hardLinkedDocument);
                    $hardLinkedDocument->setHardLinkSource($hardlink);

                    $_path = $path != '/' ? $_path = dirname($p) : $p;
                    $_path = str_replace('\\', '/', $_path); // windows patch
                    $_path .= $_path != '/' ? '/' : '';

                    $_path = preg_replace('@^' . preg_quote($hardlink->getSourceDocument()->getRealPath(), '@') . '@', $hardlink->getRealPath(), $_path);

                    $hardLinkedDocument->setPath($_path);

                    return $hardLinkedDocument;
                }
            }
        }

        return null;
    }
}
