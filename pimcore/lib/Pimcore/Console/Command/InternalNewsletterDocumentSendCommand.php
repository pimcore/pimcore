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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model;

class InternalNewsletterDocumentSendCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('internal:newsletter-document-send')
            ->setDescription('For internal use only')
            ->addArgument("sendingId")->addArgument("hostUrl");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sendingId = $input->getArgument("sendingId");
        $tmpStore = Model\Tool\TmpStore::get($sendingId);

        if(empty($tmpStore)) {
            \Logger::alert("No sending configuration for $sendingId found. Cannot send newsletter.");
            exit;
        }

        $data = $tmpStore->getData();

        if($data['inProgress']) {
            \Logger::alert("Cannot send newsletters because there's already one active sending process.");
            exit;
        }

        $data['inProgress'] = 1;
        $tmpStore->setData($data);
        $tmpStore->update();

        $document = Model\Document\Newsletter::getById($data['documentId']);
        $addressSourceAdapterName = $data['addressSourceAdapterName'];
        $adapterParams = $data['adapterParams'];

        $adapterClass = "\\Pimcore\\Document\\Newsletter\\AddressSourceAdapter\\" . ucfirst($addressSourceAdapterName);

        /**
         * @var $addressAdapter \Pimcore\Document\Newsletter\IAddressSourceAdapter
         */
        $addressAdapter = new $adapterClass($adapterParams);


        $totalCount = $addressAdapter->getTotalRecordCount();

        $limit = $totalCount / 5 > 20 ? 20 : intval($totalCount / 5);
        $offset = 0;
        $hasElements = true;

        while($hasElements) {

            $tmpStore = Model\Tool\TmpStore::get($sendingId);

            if(empty($tmpStore)) {
                \Logger::warn("Sending configuration for sending ID $sendingId was deleted. Cancelling sending process.");
                exit;
            }

            $data = $tmpStore->getData();


            \Logger::info("Sending newsletter " . $hasElements . " / " . $totalCount. " [" . $document->getId(). "]");

            $data['progress'] = round($offset / $totalCount * 100, 2);
            $tmpStore->setData($data);
            $tmpStore->update();

            $sendingParamContainer = $addressAdapter->getParamsForSingleSending($limit, $offset);
            \Pimcore\Tool\Newsletter::sendNewsletterDocumentBasedMail($document, $sendingParamContainer, \Pimcore\Tool\Newsletter::SENDING_MODE_SINGLE, $input->getArgument("hostUrl"));
            $offset += $limit;
            $hasElements = count($sendingParamContainer);

            \Pimcore::collectGarbage();

        }
        Model\Tool\TmpStore::delete($sendingId);
    }
}
