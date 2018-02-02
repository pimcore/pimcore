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

namespace Pimcore\Templating\Helper;

use Pimcore\Tool\Glossary\Processor;
use Symfony\Component\Templating\Helper\Helper;

class Glossary extends Helper
{
    /**
     * @var \Pimcore\Tool\Glossary\Processor
     */
    private $glossaryProcessor;

    /**
     * @param \Pimcore\Tool\Glossary\Processor $glossaryProcessor
     */
    public function __construct(Processor $glossaryProcessor)
    {
        $this->glossaryProcessor = $glossaryProcessor;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'glossary';
    }

    public function start()
    {
        ob_start();
    }

    public function stop()
    {
        $contents = ob_get_clean();

        $result = '';
        if (empty($contents) || !is_string($contents)) {
            $result = $contents;
        } else {
            $result = $this->glossaryProcessor->process($contents);
        }

        echo $result;
    }
}
