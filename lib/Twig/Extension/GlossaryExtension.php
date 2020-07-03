<?php

declare(strict_types=1);

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

namespace Pimcore\Twig\Extension;

use Pimcore\Templating\Helper\Glossary;
use Pimcore\Twig\TokenParser\GlossaryTokenParser;
use Twig\Extension\AbstractExtension;

class GlossaryExtension extends AbstractExtension
{
    /**
     * @var Glossary
     */
    private $glossaryHelper;

    /**
     * @param Glossary $glossaryHelper
     */
    public function __construct(Glossary $glossaryHelper)
    {
        $this->glossaryHelper = $glossaryHelper;
    }

    /**
     * @return Glossary
     */
    public function getGlossaryHelper(): Glossary
    {
        return $this->glossaryHelper;
    }

    public function getTokenParsers(): array
    {
        return [
            new GlossaryTokenParser(),
        ];
    }
}
