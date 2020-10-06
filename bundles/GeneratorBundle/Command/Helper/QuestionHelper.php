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

use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper as BaseQuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated
 */
class QuestionHelper extends BaseQuestionHelper
{
    public function writeGeneratorSummary(OutputInterface $output, $errors)
    {
        if (!$errors) {
            $this->writeSection($output, 'Everything is OK! Now get to work :).');
        } else {
            $this->writeSection($output, [
                'The command was not able to configure everything automatically.',
                'You\'ll need to make the following changes manually.',
            ], 'error');

            $output->writeln($errors);
        }
    }

    public function getRunner(OutputInterface $output, &$errors)
    {
        $runner = function ($err) use ($output, &$errors) {
            if ($err) {
                $output->writeln('<fg=red>FAILED</>');
                $errors = array_merge($errors, $err);
            } else {
                $output->writeln('<info>OK</info>');
            }
        };

        return $runner;
    }

    public function getQuestion($question, $default, $sep = ':')
    {
        return $default ? sprintf('<info>%s</info> [<comment>%s</comment>]%s ', $question, $default, $sep) : sprintf('<info>%s</info>%s ', $question, $sep);
    }

    public function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $text = str_replace('Symfony bundle generator', 'Pimcore bundle generator', $text);

        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');
        $output->writeln([
            '',
            $formatter->formatBlock($text, $style, true),
            '',
        ]);
    }
}
