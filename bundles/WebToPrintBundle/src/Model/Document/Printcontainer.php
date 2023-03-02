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

namespace Pimcore\Bundle\WebToPrintBundle\Model\Document;

use Pimcore\Model\Document;

/**
 * @method Printcontainer\Dao getDao()
 */
class Printcontainer extends PrintAbstract
{
    /**
     * {@inheritdoc}
     */
    protected string $type = 'printcontainer';

    /**
     * @internal
     *
     * @var string
     */
    protected string $action = 'container';

    private array $allChildren = [];

    /**
     * @return array
     *
     * @internal
     */
    public function getTreeNodeConfig(): array
    {
        $tmpDocument = [];
        $tmpDocument['leaf'] = false;
        $tmpDocument['expanded'] = !$this->hasChildren();
        $tmpDocument['iconCls'] = 'pimcore_icon_printcontainer';
        $tmpDocument['permissions'] = [
            'view' => $this->isAllowed('view'),
            'remove' => $this->isAllowed('delete'),
            'settings' => $this->isAllowed('settings'),
            'rename' => $this->isAllowed('rename'),
            'publish' => $this->isAllowed('publish'),
            'create' => $this->isAllowed('create'),
        ];

        return $tmpDocument;
    }

    public function getAllChildren(): array
    {
        $this->allChildren = [];
        $this->doGetChildren($this);

        return $this->allChildren;
    }

    private function doGetChildren(Document $document): void
    {
        $children = $document->getChildren();
        foreach ($children as $child) {
            if ($child instanceof Printpage) {
                $this->allChildren[] = $child;
            }

            if ($child instanceof Document\Folder || $child instanceof Printcontainer) {
                $this->doGetChildren($child);
            }

            if ($child instanceof Document\Hardlink) {
                if ($child->getSourceDocument() instanceof Printpage) {
                    $this->allChildren[] = $child;
                }

                $this->doGetChildren($child);
            }
        }
    }

    public function pdfIsDirty(): bool
    {
        $dirty = parent::pdfIsDirty();
        if (!$dirty) {
            $dirty = ($this->getLastGenerated() < $this->getDao()->getLastedChildModificationDate());
        }

        return $dirty;
    }
}
