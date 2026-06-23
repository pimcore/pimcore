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

namespace Pimcore\Bundle\GlossaryBundle\Model\Glossary;

use Pimcore\Bundle\GlossaryBundle\Model\Glossary;
use Pimcore\Model\Listing\AbstractListing;

/**
 * @method Listing\Dao getDao()
 * @method Glossary[] load()
 * @method Glossary|false current()
 * @method int getTotalCount()
 * @method list<array<string,mixed>> getDataArray()
 */
class Listing extends AbstractListing
{
    /**
     * @return Glossary[]
     */
    public function getGlossary(): array
    {
        return $this->getData();
    }

    /**
     * @param Glossary[]|null $glossary
     *
     * @return $this
     */
    public function setGlossary(?array $glossary): static
    {
        return $this->setData($glossary);
    }
}
