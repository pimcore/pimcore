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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Hardlink_Wrapper {

    /**
     * @static
     * @param Document $hardlink
     * @return Document_PageSnippet
     */
    public static function wrap(Document $doc) {

        if($doc instanceof Document_Hardlink) {
            if($sourceDoc = $doc->getSourceDocument()) {
                $destDoc = self::upperCastDocument($sourceDoc);
                $destDoc->setKey($doc->getKey());
                return $destDoc;
            }
        } else {
            return self::upperCastDocument($doc);
        }

        return;
    }

    /**
     * @static
     * @param Document $doc
     * @return Document
     */
    public static function upperCastDocument (Document $doc) {

        $to_class = "Document_Hardlink_Wrapper_" . ucfirst($doc->getType());

        $old_serialized_prefix  = "O:".strlen(get_class($doc));
        $old_serialized_prefix .= ":\"".get_class($doc)."\":";

        $old_serialized_object = serialize($doc);
        $new_serialized_object = 'O:'.strlen($to_class).':"'.$to_class . '":';
        $new_serialized_object .= substr($old_serialized_object,strlen($old_serialized_prefix));

        $document = unserialize($new_serialized_object);
        return $document;
    }
}
