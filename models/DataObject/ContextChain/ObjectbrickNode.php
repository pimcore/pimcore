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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ContextChain;

class ObjectbrickNode extends AbstractNode
{

    /** @var string */
    protected $brickKey;

    /** @var string */
    protected $fieldname;


    /**
     * @internal
     *
     * ObjectbrickNode constructor.
     * @param string $brickKey
     * @param string $fieldname
     */
    public function __construct(string $brickKey, string $fieldname)
    {
        $this->brickKey = $brickKey;
        $this->fieldname = $fieldname;
    }

    /**
     * @return string
     */
    public function getBrickKey(): string
    {
        return $this->brickKey;
    }

    /**
     * @return string
     */
    public function getFieldname(): string
    {
        return $this->fieldname;
    }


}
