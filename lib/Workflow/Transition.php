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

namespace Pimcore\Workflow;

use Pimcore\Workflow\Notes\NotesAwareInterface;
use Pimcore\Workflow\Notes\NotesAwareTrait;
use Pimcore\Workflow\Notification\NotificationInterface;
use Pimcore\Workflow\Notification\NotificationTrait;

class Transition extends \Symfony\Component\Workflow\Transition implements NotesAwareInterface, NotificationInterface
{
    use NotesAwareTrait;
    use NotificationTrait;

    public const UNSAVED_CHANGES_BEHAVIOUR_SAVE = 'save';

    public const UNSAVED_CHANGES_BEHAVIOUR_IGNORE = 'ignore';

    public const UNSAVED_CHANGES_BEHAVIOUR_WARN = 'warn';

    /**
     * @var array
     */
    private $options;

    /**
     * Transition constructor.
     *
     * @param string|string[] $froms
     * @param string|string[] $tos
     */
    public function __construct(string $name, $froms, $tos, array $options = [])
    {
        parent::__construct($name, $froms, $tos);
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getLabel(): string
    {
        return $this->options['label'] ?? $this->getName();
    }

    public function getIconClass(): string
    {
        return $this->options['iconClass'] ?? 'pimcore_icon_workflow_action';
    }

    /**
     * @return string|int|false
     */
    public function getObjectLayout(): bool|int|string
    {
        return $this->options['objectLayout'] ?: false;
    }

    public function getChangePublishedState(): string
    {
        return (string) $this->options['changePublishedState'];
    }
}
