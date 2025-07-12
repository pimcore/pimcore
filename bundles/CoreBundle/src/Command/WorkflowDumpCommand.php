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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Workflow\Dumper\GraphvizDumper;
use Pimcore\Workflow\Dumper\StateMachineGraphvizDumper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\Marking;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:workflow:dump',
    description: 'Dump a workflow'

)]
class WorkflowDumpCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('name', InputArgument::REQUIRED, 'A workflow name'),
                new InputArgument('marking', InputArgument::IS_ARRAY, 'A marking (a list of places)'),
            ])
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command dumps the graphical representation of a
workflow in DOT format

    %command.full_name% <workflow name> | dot -Tpng > workflow.png

EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = $this->getApplication()->getKernel()->getContainer();
        $serviceId = $input->getArgument('name');
        if ($container->has('workflow.'.$serviceId)) {
            $workflow = $container->get('workflow.'.$serviceId);
            $dumper = $container->get(GraphvizDumper::class);
        } elseif ($container->has('state_machine.'.$serviceId)) {
            $workflow = $container->get('state_machine.'.$serviceId);
            $dumper = $container->get(StateMachineGraphvizDumper::class);
        } else {
            throw new InvalidArgumentException(sprintf('No service found for "workflow.%1$s" nor "state_machine.%1$s".', $serviceId));
        }

        $marking = new Marking();

        foreach ($input->getArgument('marking') as $place) {
            $marking->mark($place);
        }

        $output->writeln($dumper->dump($workflow->getDefinition(), $marking, ['workflowName' => $workflow->getName()]));

        return 0;
    }
}
