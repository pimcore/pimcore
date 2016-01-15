<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Console\Command;

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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $input->getOption("user");

        if(!$user) {
            $this->writeError("No username/ID given");
        }

        $method = is_numeric($user) ? 'getById' : 'getByName';
        $user = User::$method($user);

        if(!$user) {
            $this->writeError("User with name " . $user . " could not be found. Exiting");
            exit;
        }

        $plainPassword = false;
        while (empty($plainPassword)) {
            $plainPassword = $this->promtSilent();
        }

        $password = \Pimcore\Tool\Authentication::getPasswordHash($user->getName(), $plainPassword);
        $user->setPassword($password);
        $user->save();

        $this->output->writeln("Password for user " . $user->getName() . " reset successfully.");

    }

    protected function promtSilent  ($prompt = "Enter new password:") {
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
