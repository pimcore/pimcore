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
