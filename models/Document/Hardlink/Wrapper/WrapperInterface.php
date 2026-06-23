<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
