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

namespace Pimcore\Bundle\PimcoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Tool\Authentication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\User;
use Symfony\Component\Console\Question\Question;

class ResetPasswordCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('pimcore:user:reset-password')
            ->setAliases(['reset-password'])
            ->setDescription("Reset a user's password")
            ->addArgument(
                'user',
                InputArgument::REQUIRED,
                "Username or ID of user"
            )
            ->addOption(
                "password", "p",
                InputOption::VALUE_OPTIONAL,
                "Plaintext password - if not set, script will prompt for the new password (recommended)"
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userArgument = $input->getArgument("user");

        $method = is_numeric($userArgument) ? 'getById' : 'getByName';

        /** @var $user User */
        $user = User::$method($userArgument);

        if (!$user) {
            $this->writeError("User with name/ID " . $userArgument . " could not be found. Exiting");
            exit;
        }

        if ($input->getOption("password")) {
            $plainPassword = $input->getOption("password");
        } else {
            $plainPassword = $this->askForPassword($input, $output);
        }

        $password = Authentication::getPasswordHash($user->getName(), $plainPassword);
        $user->setPassword($password);
        $user->save();

        $this->output->writeln("Password for user " . $user->getName() . " reset successfully.");
    }

    protected function askForPassword(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $question = new Question('Please enter the new password: ');
        $question->setValidator(function ($value) {
            if (empty(trim($value))) {
                throw new \Exception('The password cannot be empty');
            }

            return $value;
        });

        $question->setHidden(true);

        $password = $helper->ask($input, $output, $question);

        return $password;
    }
}
