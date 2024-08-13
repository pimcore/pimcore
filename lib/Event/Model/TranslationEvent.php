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

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Translation;
use Symfony\Contracts\EventDispatcher\Event;

class TranslationEvent extends Event
{
    use ArgumentsAwareTrait;

    protected Translation $translation;

    /**
     * AssetEvent constructor.
     *
     * @param array $arguments additional parameters (e.g. "versionNote" for the version note)
     */
    public function __construct(Translation $translation, array $arguments = [])
    {
        $this->translation = $translation;
        $this->arguments = $arguments;
    }

    public function getTranslation(): Translation
    {
        return $this->translation;
    }

    public function setTranslation(Translation $translation): void
    {
        $this->translation = $translation;
    }
}
