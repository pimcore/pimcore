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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\User;

class ResetPasswordCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('reset-password')
            ->setDescription("Reset a user's password")
            ->addOption(
                'user', 'u',
                InputOption::VALUE_REQUIRED,
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
        $user = $input->getOption("user");

        if (!$user) {
            $this->writeError("No username/ID given");
        }

        $method = is_numeric($user) ? 'getById' : 'getByName';
        /** @var $user User */
        $user = User::$method($user);

        if (!$user) {
            $this->writeError("User with name " . $user . " could not be found. Exiting");
            exit;
        }

        if ($input->getOption("password")) {
            $plainPassword = $input->getOption("password");
        } else {
            $plainPassword = false;
            while (empty($plainPassword)) {
                $plainPassword = $this->promtSilent();
            }
        }

        $password = \Pimcore\Tool\Authentication::getPasswordHash($user->getName(), $plainPassword);
        $user->setPassword($password);
        $user->save();

        $this->output->writeln("Password for user " . $user->getName() . " reset successfully.");
    }

    /**
     * @param string $prompt
     * @return string|void
     */
    protected function promtSilent($prompt = "Enter new password:")
    {
        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
            file_put_contents(
                $vbscript, 'wscript.echo(InputBox("'
                . addslashes($prompt)
                . '", "", "password here"))');
            $command = "cscript //nologo " . escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);

            return $password;
        } else {
            $command = "/usr/bin/env bash -c 'echo OK'";
            if (rtrim(shell_exec($command)) !== 'OK') {
                trigger_error("Can't invoke bash");

                return;
            }
            $command = "/usr/bin/env bash -c 'read -s -p \""
                . addslashes($prompt)
                . "\" mypassword && echo \$mypassword'";
            $password = rtrim(shell_exec($command));
            echo "\n";

            return $password;
        }
    }
}
