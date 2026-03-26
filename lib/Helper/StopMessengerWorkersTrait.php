<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Helper;

use Exception;
use Pimcore;
use Pimcore\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @internal
 */
trait StopMessengerWorkersTrait
{
    protected function stopMessengerWorkers(): void
    {
        $app = new Application(Pimcore::getKernel());
        $app->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'messenger:stop-workers',
            '--no-ansi' => null,
            '--no-interaction' => null,
            '--ignore-maintenance-mode' => null,
        ]);

        $output = new BufferedOutput();
        $return = $app->run($input, $output);

        if (0 !== $return) {
            // return the output, don't use if you used NullOutput()
            $content = $output->fetch();

            throw new Exception('Running messenger:stop-workers failed, output was: ' . $content);
        }
    }
}
