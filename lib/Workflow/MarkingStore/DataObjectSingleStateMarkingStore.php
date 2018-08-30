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

namespace Pimcore\Workflow\MarkingStore;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;

class DataObjectSingleStateMarkingStore extends SingleStateMarkingStore implements PimcoreElementPersistentMarkingStoreInterface
{
    /**
     * @inheritdoc
     * @throws LogicException
     */
    public function getMarking($subject)
    {
        $this->checkIfSubjectIsValid($subject);

        return parent::getMarking($subject);
    }

    /**
     * @inheritdoc
     * @throws LogicException
     * @throws \Exception
     */
    public function setMarking($subject, Marking $marking)
    {
        $subject = $this->checkIfSubjectIsValid($subject);

        parent::setMarking($subject, $marking);

        $subject->save();
    }

    /**
     * @param $subject
     * @return Concrete
     */
    private function checkIfSubjectIsValid($subject): Concrete
    {
        if(!$subject instanceof Concrete) {
            throw new LogicException('data_object_single_state marking store works for pimcore data objects only.');
        }

        return $subject;
    }

}
