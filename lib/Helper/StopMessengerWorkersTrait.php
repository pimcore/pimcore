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

use Pimcore;
use Pimcore\Console\Application;
use Pimcore\Logger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @internal
 */
trait StopMessengerWorkersTrait
{
    /**
     * Signals the running messenger workers to stop, so that they get restarted with the current state.
     *
     * The signal comes on top of the operation of the caller (a configuration was saved, a class
     * definition was updated, ...). If it cannot be sent, the workers keep running until they are
     * restarted: that is logged, but does not fail the caller.
     */
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

            Logger::error(
                'Running messenger:stop-workers failed, the messenger workers keep running until they are ' .
                'restarted. Output was: ' . $content
            );
        }
    }
}
