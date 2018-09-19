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

namespace Pimcore\Bundle\GeneratorBundle\Command;

use Pimcore\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Pimcore\Bundle\GeneratorBundle\Generator\BundleGenerator;
use Pimcore\Bundle\GeneratorBundle\Model\Bundle;
use Sensio\Bundle\GeneratorBundle\Command\GenerateBundleCommand as BaseGenerateBundleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class GenerateBundleCommand extends BaseGenerateBundleCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('pimcore:generate:bundle')
            ->setDescription('Generates a Pimcore bundle')
            ->setHelp(
                <<<EOT
The <info>%command.name%</info> command helps you generates new Pimcore bundles. If you need to create a normal Symfony
bundle, please use the generate:bundle command without pimcore: prefix.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--namespace</comment> is the only one needed if you follow the
conventions):

<info>php %command.full_name% --namespace=Acme/BlogBundle</info>

Note that you can use <comment>/</comment> instead of <comment>\\ </comment>for the namespace delimiter to avoid any
problems.

If you want to disable any user interaction, use <comment>--no-interaction</comment> but don't forget to pass all needed options:

<info>php %command.full_name% --namespace=Acme/BlogBundle --dir=src [--bundle-name=...] --no-interaction</info>

Note that the bundle namespace must end with "Bundle".
EOT
            );
    }

    protected function getSkeletonDirs(BundleInterface $bundle = null)
    {
        $dirs = parent::getSkeletonDirs($bundle);

        array_unshift($dirs, __DIR__ . '/../Resources/skeleton');

        return $dirs;
    }

    /**
     * @inheritDoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $input->setOption('format', 'annotation');

        parent::initialize($input, $output);
    }

    /**
     * @see Command
     *
     * @throws \InvalidArgumentException When namespace doesn't end with Bundle
     * @throws \RuntimeException         When bundle can't be executed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        $bundle = $this->createBundleObject($input);
        $bundle->setTestsDirectory($bundle->getTargetDirectory() . '/Tests');

        $questionHelper->writeSection($output, 'Bundle generation');

        /** @var BundleGenerator $generator */
        $generator = $this->getGenerator();

        $output->writeln(sprintf(
            '> Generating a sample bundle skeleton into <info>%s</info>',
            $this->makePathRelative($bundle->getTargetDirectory())
        ));

        $generator->generateBundle($bundle);

        $errors = [];
        $fs = $this->getContainer()->get('filesystem');

        try {
            // remove tests until we defined a standard setup for bundle tests
            $fs->remove($bundle->getTestsDirectory());

            // remove views (controller just returns a response)
            $fs->remove($bundle->getTargetDirectory() . '/Resources/views');
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        $questionHelper->writeGeneratorSummary($output, $errors);
    }

    protected function getQuestionHelper()
    {
        $question = $this->getHelperSet()->get('question');
        if (!$question || get_class($question) !== QuestionHelper::class) {
            $this->getHelperSet()->set($question = new QuestionHelper());
        }

        return $question;
    }

    protected function createBundleObject(InputInterface $input)
    {
        $bundle = parent::createBundleObject($input);

        return new Bundle($bundle);
    }

    protected function createGenerator()
    {
        return new BundleGenerator($this->getContainer()->get('filesystem'));
    }
}
