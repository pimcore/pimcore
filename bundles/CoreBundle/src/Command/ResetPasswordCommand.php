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

namespace Pimcore\Bundle\CoreBundle\Command;

use Exception;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\User;
use Pimcore\Tool\Authentication;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @internal
 */
#[AsCommand(
    name:'pimcore:user:reset-password',
    description: 'Reset a user\'s password',
    aliases: ['reset-password']
)]
class ResetPasswordCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'user',
                InputArgument::REQUIRED,
                'Username or ID of user'
            )
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Plaintext password - if not set, script will prompt for the new password (recommended)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userArgument = $input->getArgument('user');

        if (is_numeric($userArgument)) {
            $user = User::getById((int) $userArgument);
        } else {
            $user = User::getByName($userArgument);
        }

        if (!$user) {
            $this->writeError('User with name/ID ' . $userArgument . ' could not be found. Exiting');
            exit;
        }

        if ($input->getOption('password')) {
            $plainPassword = $input->getOption('password');
        } else {
            $plainPassword = $this->askForPassword($input, $output);
        }

        $password = Authentication::getPasswordHash($user->getName(), $plainPassword);
        $user->setPassword($password);
        $user->save();

        $this->output->writeln('Password for user ' . $user->getName() . ' reset successfully.');

        return 0;
    }

    protected function askForPassword(InputInterface $input, OutputInterface $output): mixed
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new Question('Please enter the new password: ');
        $question->setValidator(function ($value) {
            if (empty(trim($value))) {
                throw new Exception('The password cannot be empty');
            }

            return $value;
        });

        $question->setHidden(true);

        return $helper->ask($input, $output, $question);
    }
}
