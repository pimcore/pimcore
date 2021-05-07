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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Translation;
use Symfony\Component\EventDispatcher\Event;

class TranslationEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    /**
     * @var Translation
     */
    protected $translation;

    /**
     * AssetEvent constructor.
     *
     * @param Translation $translation
     * @param array $arguments additional parameters (e.g. "versionNote" for the version note)
     */
    public function __construct(Translation $translation, array $arguments = [])
    {
        $this->translation = $translation;
        $this->arguments = $arguments;
    }

    /**
     * @return Translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param Translation $translation
     */
    public function setTranslation(Translation $translation)
    {
        $this->translation = $translation;
    }

    /**
     * @deprecated use getTranslation() instead - will be removed in Pimcore 10
     *
     * @return Translation
     */
    public function getElement()
    {
        return $this->getTranslation();
    }
}
