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

use Pimcore\Console\Style\PimcoreStyle;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\AbstractBlock;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\AbstractElement;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element\Editable;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Exception\BuildEditableException;
use Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Exception\LogicException;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\Model\Document;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class ConflictResolver implements ConflictResolverInterface
{
    /**
     * @var PimcoreStyle
     */
    private $io;

    /**
     * @var NamingStrategyInterface
     */
    private $namingStrategy;

    public function __construct(PimcoreStyle $io, NamingStrategyInterface $namingStrategy)
    {
        $this->io             = $io;
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * @inheritdoc
     */
    public function resolveBuildFailed(Document\PageSnippet $document, BuildEditableException $exception): BuildEditableException
    {
        if (!$this->io->getInput()->isInteractive()) {
            return $exception;
        }

        $message = [
            sprintf('<fg=red>[ERROR]</> %s', $exception->getMessage()),
            '        You can try to open and save the document in the admin interface to clean up orphaned elements.'
        ];

        $this->showErrorInfo(
            $document,
            $exception,
            $message
        );

        $choices = [
            'Leave unresolved',
            'Ignore editable (<fg=red>data will be lost!</>)'
        ];

        $result = $this->io->choice(
            sprintf('Please select how to proceed with editable "<comment>%s</comment>"', $exception->getName()),
            $choices
        );

        if ($result === $choices[1]) {
            $exception->setIgnoreElement(true);
        }

        return $exception;
    }

    /**
     * @inheritdoc
     */
    public function resolveBlockConflict(Document\PageSnippet $document, BuildEditableException $exception, array $blocks): AbstractBlock
    {
        /** @var AbstractBlock $block */
        $block = $this->resolveConflict('block', $document, $exception, $blocks);

        return $block;
    }

    /**
     * @inheritdoc
     */
    public function resolveEditableConflict(Document\PageSnippet $document, BuildEditableException $exception, array $editables): Editable
    {
        /** @var Editable $editable */
        $editable = $this->resolveConflict('element', $document, $exception, $editables);

        return $editable;
    }

    /**
     * @param string $type
     * @param Document\PageSnippet $document
     * @param BuildEditableException $exception
     * @param AbstractElement[] $elements
     *
     * @return AbstractElement
     */
    private function resolveConflict(string $type, Document\PageSnippet $document, BuildEditableException $exception, array $elements): AbstractElement
    {
        if (count($elements) < 2) {
            throw new LogicException(sprintf('Expected at least 2 editables, %d given', count($elements)));
        }

        $message = <<<EOF
The {$type} <comment>{$exception->getName()}</comment> can't be automatically converted as there is more
than one possible hierarchy combination. This is probably because of nested elements with
the same name ("content" inside "content") or blocks with similar names andnumeric suffixes
("content" and "content1").
EOF;

        $this->showErrorInfo($document, $exception, $message);

        $this->io->newLine();
        $this->io->writeln('<comment>[WARNING]</comment> Selecting the wrong option here will rename your elements to a wrong target name!');
        $this->io->newLine();

        /** @var AbstractElement[] $elements */
        usort(array_values($elements), function (AbstractElement $a, AbstractElement $b) {
            return strcasecmp($a->getName(), $b->getName());
        });

        $leaveUnresolved = 'Leave unresolved';
        $resultChoice    = $leaveUnresolved;
        $result          = null;

        if ($this->io->getInput()->isInteractive()) {
            $choices   = $this->buildChoices($elements);
            $choices[] = $leaveUnresolved;

            $inverseChoices = array_flip($choices);

            $resultChoice = $this->io->choice(
                'Please choose the resolution matching your template structure',
                $choices
            );

            $result = $inverseChoices[$resultChoice];
        }

        if ($resultChoice === $leaveUnresolved) {
            $possibleNames = [];
            foreach ($elements as $editable) {
                $possibleNames[] = $editable->getNameForStrategy($this->namingStrategy);
            }

            throw BuildEditableException::fromPrevious(
                $exception,
                sprintf(
                    'Ambiguous results left unresolved. Built %d %ss for element "%s". Possible resolutions: %s',
                    count($elements),
                    $type,
                    $exception->getName(),
                    implode(', ', $possibleNames)
                )
            );
        } else {
            return $elements[$result];
        }
    }

    private function showErrorInfo(Document\PageSnippet $document, BuildEditableException $exception, $message = null)
    {
        $this->io->newLine(2);
        $this->io->writeln(str_repeat('=', 80));
        $this->io->newLine(2);

        if ($message) {
            $this->io->writeln($message);
            $this->io->newLine();
        }

        if (count($exception->getErrors()) > 0) {
            foreach ($exception->getErrors() as $error) {
                $this->io->writeln('  <fg=red>*</> ' . $error->getMessage());
            }

            $this->io->newLine();
        }

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
                    $exception->getName(), $exception->getType()
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

        $tableRows[] = [
            'Data',
            $this->dumpData($exception->getElementData())
        ];

        $this->io->table([], $tableRows);
    }

    private function dumpData($data)
    {
        $data = null !== $data ? trim($data) : null;
        if (empty($data)) {
            $data = '<empty>';
        }

        $dumped = trim((new CliDumper())->dump((new VarCloner())->cloneVar($data), true));

        return $dumped;
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
