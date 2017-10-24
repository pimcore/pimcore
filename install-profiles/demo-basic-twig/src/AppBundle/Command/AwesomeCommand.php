<?php

namespace AppBundle\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AwesomeCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('app:awesome')
            ->setDescription('Awesome command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output->writeln('<info>Hello, world!</info>');
    }
}
