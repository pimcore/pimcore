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

namespace Pimcore\Cache\Symfony;

use Pimcore\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CacheClearer
{
    public function clear(KernelInterface $kernel, array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'no-warmup'           => false,
            'no-optional-warmers' => false,
            'env'                 => $kernel->getEnvironment()
        ]);

        $this->runCommand($kernel, 'cache:clear', [], $resolver->resolve($options));
    }

    public function warmup(KernelInterface $kernel, array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'no-optional-warmers' => false,
            'env'                 => $kernel->getEnvironment()
        ]);

        $this->runCommand($kernel, 'cache:warmup', [], $resolver->resolve($options));
    }

    private function runCommand(KernelInterface $kernel, string $command, array $arguments = [], array $options = [])
    {
        $input = $this->createInput($command, $arguments, $options);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $output = new BufferedOutput();
        $result = $application->run($input, $output);

        if (0 !== $result) {
            throw new \RuntimeException(sprintf(
                'Command "%s" failed: %s',
                $command,
                $output->fetch()
            ));
        }
    }

    private function createInput(string $command, array $arguments = [], array $options = []): ArrayInput
    {
        $input = array_merge($arguments, [
            'command' => $command
        ]);

        foreach ($options as $optionKey => $option) {
            // do not set option if it is false
            if (is_bool($option) && !$option) {
                continue;
            }

            $input['--' . $optionKey] = $option;
        }

        return new ArrayInput($input);
    }
}
