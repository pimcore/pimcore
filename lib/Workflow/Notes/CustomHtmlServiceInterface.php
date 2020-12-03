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

namespace Pimcore\Workflow\Notes;

use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Workflow;
use Pimcore\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

interface CustomHtmlServiceInterface
{
    public function renderHtml(WorkflowInterface $workflow, Transition $transition, ElementInterface $element) : string;

   // public function renderHtmlTop(): string;

   // public function renderHtmlCenter(): string;

  //  public function renderHtmlBottom(): string;

}
