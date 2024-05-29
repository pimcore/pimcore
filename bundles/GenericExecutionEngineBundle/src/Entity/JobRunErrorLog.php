<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\GenericExecutionEngineBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Table;

/**
 * @internal
 */
#[Entity]
#[Table(name: 'generic_execution_engine_error_log')]
#[HasLifecycleCallbacks]
class JobRunErrorLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(type: 'integer')]
    private int $jobRunId;

    #[ORM\Column(type: 'integer')]
    private int $stepNumber;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $elementId;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage;

    public function __construct(
        int $jobRunId,
        int $stepNumber,
        ?int $elementId = null,
        ?string $errorMessage = null
    ) {
        $this->jobRunId = $jobRunId;
        $this->stepNumber = $stepNumber;
        $this->elementId = $elementId;
        $this->errorMessage = $errorMessage;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getJobRunId(): int
    {
        return $this->jobRunId;
    }

    public function getElementId(): ?int
    {
        return $this->elementId;
    }

    public function getStepNumber(): ?int
    {
        return $this->stepNumber;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
