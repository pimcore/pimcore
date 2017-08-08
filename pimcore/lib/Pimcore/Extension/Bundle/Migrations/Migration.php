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

namespace Pimcore\Extension\Bundle\Migrations;

use Doctrine\DBAL\Migrations\OutputWriter;
use Pimcore\Extension\Bundle\Migrations\Configuration\Configuration;

class Migration extends \Doctrine\DBAL\Migrations\Migration
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * The OutputWriter object instance used for outputting information
     *
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * @inheritDoc
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->outputWriter = $configuration->getOutputWriter();

        parent::__construct($configuration);
    }

    /**
     * @inheritDoc
     */
    public function writeSqlFile($path, $to = null)
    {
        $sql = $this->getSql($to);

        $from = $this->configuration->getCurrentVersion();
        if ($to === null) {
            $to = $this->configuration->getLatestVersion();
        }

        $direction = $from > $to ? Version::DIRECTION_DOWN : Version::DIRECTION_UP;

        $this->outputWriter->write(sprintf("-- Migrating from %s to %s\n", $from, $to));

        $sqlWriter = new SqlFileWriter(
            $this->configuration,
            $path,
            $this->outputWriter
        );

        return $sqlWriter->write($sql, $direction);
    }
}
