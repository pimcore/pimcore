<?php

namespace Pimcore\Helper;

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
        $app = new Application(\Pimcore::getKernel());
        $app->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'messenger:stop-workers',
            '--no-ansi' => null,
            '--no-interaction' => null,
        ]);

        $output = new BufferedOutput();
        $return = $app->run($input, $output);

        if (0 !== $return) {
            // return the output, don't use if you used NullOutput()
            $content = $output->fetch();

            throw new \Exception('Running messenger:stop-workers failed, output was: ' . $content);
        }
    }
}
