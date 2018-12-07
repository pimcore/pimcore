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
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * Contains the results of the list. They are all an instance of Glossary
     *
     * @var array
     */
    public $glossary = [];

    /**
     * @return array
     */
    public function getGlossary()
    {
        return $this->glossary;
    }

    /**
     * @param $glossary
     *
     * @return $this
     */
    public function setGlossary($glossary)
    {
        $this->glossary = $glossary;

        return $this;
    }
}
