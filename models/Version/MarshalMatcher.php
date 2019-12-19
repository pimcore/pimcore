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

namespace Pimcore\Model\Version;

use DeepCopy\TypeMatcher\TypeMatcher;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;

class MarshalMatcher extends TypeMatcher
{
    private $sourceType;

    private $sourceId;

    /**
     * MarshalMatcher constructor.
     *
     * @param $sourceType
     * @param $sourceId
     */
    public function __construct($sourceType, $sourceId)
    {
        $this->sourceType = $sourceType;
        $this->sourceId = $sourceId;
    }

    /**
     * @param mixed $element
     *
     * @return bool
     */
    public function matches($element)
    {
        if ($element instanceof ElementInterface) {
            $elementType = Service::getType($element);
            if ($elementType == $this->sourceType && $element->getId() == $this->sourceId) {
                return false;
            }

            return true;
        }

        return false;
    }
}
