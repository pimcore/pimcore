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

namespace Pimcore\Model\Document\Hardlink\Wrapper;

use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;

interface WrapperInterface extends ElementInterface
{
    /**
     * @param Document $hardLinkSource
     */
    public function setHardLinkSource($hardLinkSource);

    /**
     * @return Document\Hardlink
     */
    public function getHardLinkSource();

    /**
     * @param Document $sourceDocument
     */
    public function setSourceDocument($sourceDocument);

    /**
     * @return Document
     */
    public function getSourceDocument();
}
