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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Twig\Extension;

use Pimcore\Tool\Glossary\Processor;
use Pimcore\Twig\TokenParser\GlossaryTokenParser;
use Twig\Extension\AbstractExtension;

/**
 * @internal
 */
class GlossaryExtension extends AbstractExtension
{
    /**
     * @var \Pimcore\Tool\Glossary\Processor
     */
    private $glossaryProcessor;

    /**
     * @param \Pimcore\Tool\Glossary\Processor $glossaryProcessor
     *
     */
    public function __construct(Processor $glossaryProcessor)
    {
        $this->glossaryProcessor = $glossaryProcessor;
    }

    public function getTokenParsers(): array
    {
        return [
            new GlossaryTokenParser(),
        ];
    }

    public function start()
    {
        ob_start();
    }

    /**
     * @param array $options
     */
    public function stop(array $options = [])
    {
        $contents = ob_get_clean();

        if (empty($contents) || !is_string($contents)) {
            $result = $contents;
        } else {
            $result = $this->glossaryProcessor->process($contents, $options);
        }

        echo $result;
    }
}
