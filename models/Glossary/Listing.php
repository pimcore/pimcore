<?php

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

namespace Pimcore\Model\Glossary;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Glossary\Listing\Dao getDao()
 * @method Model\Glossary[] load()
 * @method Model\Glossary|false current()
 * @method int getTotalCount()
 * @method array getDataArray()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return Model\Glossary[]
     */
    public function getGlossary()
    {
        return $this->getData();
    }

    /**
     * @param Model\Glossary[]|null $glossary
     *
     * @return $this
     */
    public function setGlossary($glossary)
    {
        return $this->setData($glossary);
    }
}
