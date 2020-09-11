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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Translation\AbstractTranslation;
use Symfony\Component\EventDispatcher\Event;

class TranslationEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    /**
     * @var AbstractTranslation
     */
    protected $translation;

    /**
     * AssetEvent constructor.
     *
     * @param AbstractTranslation $translation
     * @param array $arguments additional parameters (e.g. "versionNote" for the version note)
     */
    public function __construct(AbstractTranslation $translation, array $arguments = [])
    {
        $this->translation = $translation;
        $this->arguments = $arguments;
    }

    /**
     * @return AbstractTranslation
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param AbstractTranslation $translation
     */
    public function setTranslation(AbstractTranslation $translation)
    {
        $this->translation = $translation;
    }

    /**
     * @deprecated use getTranslation() instead - will be removed in Pimcore v7
     *
     * @return AbstractTranslation
     */
    public function getElement()
    {
        return $this->getTranslation();
    }
}
