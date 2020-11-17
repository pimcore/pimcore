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

namespace Pimcore\Extension\Bundle\Installer;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractInstaller implements InstallerInterface
{
    /**
     * @deprecated
     * @var OutputWriterInterface
     */
    protected $outputWriter;

    /**
     * @param OutputWriterInterface $outputWriter
     */
    public function __construct(OutputWriterInterface $outputWriter = null)
    {
        if (null === $outputWriter) {
            $outputWriter = new OutputWriter();
        }

        $this->setOutputWriter($outputWriter);
    }

    public function setOutputWriter(OutputWriterInterface $outputWriter)
    {
        $this->outputWriter = $outputWriter;
    }

    public function getOutputWriter(): OutputWriterInterface
    {
        return $this->outputWriter;
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUninstalled()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall()
    {
        return false;
    }

    /**
     * @deprecated
     * @inheritDoc
     */
    public function canBeUpdated()
    {
        return false;
    }

    /**
     * @deprecated
     * @inheritDoc
     */
    public function update()
    {
    }

    public function getOutput(): OutputInterface
    {
        if($this->getOutputWriter() instanceof OutputWriter) {
            return $this->getOutputWriter()->getOutputInstance();
        }

        return new BufferedOutput(Output::VERBOSITY_NORMAL, true);
    }
}
