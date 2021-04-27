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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
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
     * @var bool
     */
    protected $skipAttribute = false;

    /**
     * PreprocessAttributeErrorEvent constructor.
     *
     * @param Attribute $attribute
     * @param \Throwable $exception
     * @param bool $skipAttribute
     * @param bool $throwException
     */
    public function __construct(Attribute $attribute, \Throwable $exception, bool $skipAttribute = false, bool $throwException = true)
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
