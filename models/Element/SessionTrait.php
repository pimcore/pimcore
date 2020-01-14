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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use DeepCopy\DeepCopy;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Tool\TmpStore;
use Pimcore\Tool\Serialize;
use Pimcore\Tool\Session;

trait SessionTrait
{

    /**
     * @param string type
     * @param int $elementId
     * @param null|string $postfix
     *
     * @return string
     */
    public static function getSessionKey($type, $elementId, $postfix = '') {
        $sessionId = Session::getSessionId();
        $tmpStoreKey = $type . '_session_' . $elementId . '_' . $sessionId . $postfix;
        return $tmpStoreKey;
    }

    /**
     * @param string $type
     * @param int $elementId
     * @param null|string $postfix
     *
     * @return AbstractObject|Document|null
     */
    public static function getElementFromSession($type, $elementId, $postfix = '')
    {
        $element = null;
        $tmpStoreKey = self::getSessionKey($type, $elementId, $postfix);

        $tmpStore = TmpStore::get($tmpStoreKey);
        if ($tmpStore) {
            $data = $tmpStore->getData();
            if ($data) {
                $element = Serialize::unserialize($data);
                return $element;
            }

            if (!$element) {
                $element = Service::getElementById($type, $elementId);
            }
        }

        return $element;
    }

    /**
     * @param ElementInterface $element
     * @param string $postfix
     * @param bool $clone save a copy
     */
    public static function saveElementToSession($element, $postfix = '', $clone = true)
    {
        if ($clone) {
            $copier = new DeepCopy();
            $element = $copier->copy($element);
        }

        $elementType = Service::getElementType($element);
        $tmpStoreKey = self::getSessionKey($elementType, $element->getId(), $postfix);
        $tag = $elementType . '-session' . $postfix;

        if ($element instanceof ElementDumpStateInterface) {
            self::loadAllFields($element);
            $element->setInDumpState(true);
        }
        $serializedData = Serialize::serialize($element);

        TmpStore::set($tmpStoreKey, $serializedData, $tag);
    }


    /**
     * @param $type
     * @param $elementId
     * @param string $postfix
     */
    public static function removeElementFromSession($type, $elementId, $postfix = '')
    {
        $tmpStoreKey = self::getSessionKey($type, $elementId, $postfix);
        TmpStore::delete($tmpStoreKey);
    }

}
