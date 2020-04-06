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

namespace Pimcore\Workflow;

use Pimcore\Workflow\Notes\NotesAwareInterface;
use Pimcore\Workflow\Notes\NotesAwareTrait;
use Pimcore\Workflow\Notification\NotificationInterface;
use Pimcore\Workflow\Notification\NotificationTrait;

class Transition extends \Symfony\Component\Workflow\Transition implements NotesAwareInterface, NotificationInterface
{
    use NotesAwareTrait;
    use NotificationTrait;

    private $options;

    /**
     * Transition constructor.
     *
     * @param string $name
     * @param string|string[] $froms
     * @param string|string[] $tos
     * @param array $options
     */
    public function __construct($name, $froms, $tos, $options = [])
    {
        parent::__construct($name, $froms, $tos);
        $this->options = $options;
    }

    public function getOptions()
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
    public function getObjectLayout()
    {
        return $this->options['objectLayout'] ?: false;
    }

    public function getChangePublishedState(): string
    {
        return (string) $this->options['changePublishedState'];
    }
}
