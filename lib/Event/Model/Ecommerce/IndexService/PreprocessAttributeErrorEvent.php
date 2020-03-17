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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event\Model\Ecommerce\IndexService;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\Definition\Attribute;

class PreprocessAttributeErrorEvent extends PreprocessErrorEvent
{
    /**
     * @var Attribute
     */
    protected $attribute;

    /**
     * skip attribute is currently the default behavior.
     *
     * @var bool
     */
    protected $skipAttribute = true;

    /**
     * PreprocessAttributeErrorEvent constructor.
     *
     * @param Attribute $attribute
     * @param bool $skipAttribute
     */
    public function __construct(Attribute $attribute, \Throwable $exception, bool $skipAttribute = true, bool $throwException = false)
    {
        parent::__construct($exception, $throwException);
        $this->attribute = $attribute;
        $this->skipAttribute = $skipAttribute;
    }

    /**
     * @return Attribute
     */
    public function getAttribute(): Attribute
    {
        return $this->attribute;
    }

    /**
     * @return bool
     */
    public function doSkipAttribute(): bool
    {
        return $this->skipAttribute;
    }

    /**
     * @param bool $skipAttribute
     *
     * @return PreprocessErrorEvent
     */
    public function setSkipAttribute(bool $skipAttribute): PreprocessErrorEvent
    {
        $this->skipAttribute = $skipAttribute;

        return $this;
    }
}
