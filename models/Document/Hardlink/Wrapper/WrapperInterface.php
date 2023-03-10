<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Document\Hardlink\Wrapper;

use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;

interface WrapperInterface extends ElementInterface
{
    /**
     * @return $this
     */
    public function setHardLinkSource(Document\Hardlink $hardLinkSource): static;

    public function getHardLinkSource(): Document\Hardlink;

    /**
     * @return $this
     */
    public function setSourceDocument(Document $sourceDocument): static;

    public function getSourceDocument(): ?Document;
}
