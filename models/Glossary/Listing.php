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
 * @package    Glossary
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Glossary;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Glossary\Listing\Dao getDao()
 * @method Model\Glossary[] load()
 * @method Model\Glossary current()
 * @method int getTotalCount()
 * @method array getDataArray()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @var Model\Glossary[]|null
     *
     * @deprecated use getter/setter methods or $this->data
     */
    protected $glossary = null;

    public function __construct()
    {
        $this->glossary = & $this->data;
    }

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
     * @return static
     */
    public function setGlossary($glossary)
    {
        return $this->setData($glossary);
    }
}
