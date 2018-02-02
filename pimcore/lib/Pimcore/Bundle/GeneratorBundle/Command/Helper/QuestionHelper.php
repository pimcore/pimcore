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

namespace Pimcore\Bundle\GeneratorBundle\Command\Helper;

use Symfony\Component\Console\Output\OutputInterface;

class QuestionHelper extends \Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper
{
    public function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $text = str_replace('Symfony bundle generator', 'Pimcore bundle generator', $text);

        parent::writeSection($output, $text, $style);
    }
}
