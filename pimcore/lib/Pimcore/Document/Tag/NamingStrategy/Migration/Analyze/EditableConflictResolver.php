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

namespace Pimcore\Document\Tag\NamingStrategy\Migration\Analyze;

use Pimcore\Bundle\CoreBundle\Command\Document\MigrateTagNamingStrategyCommand;
use Pimcore\Console\Style\PimcoreStyle;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\AbstractElement;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\Editable;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Exception\BuildEditableException;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Exception\LogicException;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\Model\Document;

final class EditableConflictResolver
{
    /**
     * @var MigrateTagNamingStrategyCommand
     */
    private $command;

    /**
     * @var PimcoreStyle
     */
    private $io;

    /**
     * @var NamingStrategyInterface
     */
    private $namingStrategy;

    public function __construct(MigrateTagNamingStrategyCommand $command, NamingStrategyInterface $namingStrategy)
    {
        $this->command        = $command;
        $this->io             = $command->getIo();
        $this->namingStrategy = $namingStrategy;
    }

    public function resolve(Document\PageSnippet $document, string $name, string $type, array $editables, array $errors): Editable
    {
        if (count($editables) < 2) {
            throw new LogicException(sprintf('Expected at least 2 editables, %d given', count($editables)));
        }

        $this->showWarning($document, $name, $type);

        /** @var Editable[] $editables */
        $editables = array_values($editables);
        $choices   = $this->buildChoices($editables);

        $leaveUnresolved = 'Leave unresolved';
        $choices[]       = $leaveUnresolved;

        $inverseChoices = array_flip($choices);
        $resultChoice   = $this->io->choice(
            'Please choose the resolution matching your template structure',
            $choices
        );

        $result = $inverseChoices[$resultChoice];

        if ($resultChoice === $leaveUnresolved) {
            $possibleNames = [];
            foreach ($editables as $editable) {
                $possibleNames[] = $editable->getNameForStrategy($this->namingStrategy);
            }

            $exception = new BuildEditableException(sprintf(
                'Ambiguous results left unresolved. Built %d editables for element "%s". Possible resolutions: %s',
                count($editables),
                $name,
                implode(', ', $possibleNames)
            ));

            $exception->setErrors($errors);

            throw $exception;
        } else {
            return $editables[$result];
        }
    }

    private function showWarning(Document\PageSnippet $document, string $name, string $type)
    {
        $this->io->newLine(2);
        $this->io->writeln(str_repeat('=', 80));
        $this->io->newLine(2);

        $message = <<<EOF
The element <comment>{$name}</comment> can't be automatically converted as there is more
than one possible hierarchy combination. This is probably because of nested elements with
the same name ("content" inside "content") or blocks with similar names andnumeric suffixes
("content" and "content1").
EOF;

        $this->io->writeln($message);
        $this->io->newLine();

        $tableRows = [
            [
                'Document',
                sprintf(
                    '<comment>%s</comment> (ID: <info>%d</info>)',
                    $document->getRealFullPath(),
                    $document->getId()
                )
            ],
            [
                'Element',
                sprintf(
                    '<comment>%s</comment> (type <comment>%s</comment>)',
                    $name, $type
                )
            ]
        ];

        if (!empty($document->getTemplate())) {
            $tableRows[] = [
                'Template',
                sprintf(
                    '<comment>%s</comment>',
                    $document->getTemplate()
                )
            ];
        }

        $this->io->table([], $tableRows);
        $this->io->newLine();
        $this->io->writeln('<comment>[WARNING]</comment> Selecting the wrong option here will rename your elements to a wrong target name!');
        $this->io->newLine();
    }

    /**
     * @param Editable[] $editables
     *
     * @return array
     */
    private function buildChoices(array $editables): array
    {
        $choices = [];

        foreach ($editables as $i => $editable) {
            $newName = $editable->getNameForStrategy($this->namingStrategy);

            $this->io->newLine();
            $this->io->title(sprintf(
                'Option <info>%d</info>: <comment>%s</comment>',
                $i,
                $newName
            ));

            $this->io->table([], [
                ['name', $editable->getName()],
                ['realName', $editable->getRealName()],
                ['new name', $newName],
                ['type', $editable->getType()],
                ['parents', json_encode($this->getParentNames($editable))],
                ['index', $editable->getIndex()],
                ['level', $editable->getLevel()],
                ['data', $editable->getData()],
            ]);

            $choices[] = $newName;
        }

        return $choices;
    }

    private function getParentNames(AbstractElement $element): array
    {
        $parents = [];
        foreach ($element->getParents() as $parent) {
            $parents[] = $parent->getRealName();
        }

        return $parents;
    }
}
