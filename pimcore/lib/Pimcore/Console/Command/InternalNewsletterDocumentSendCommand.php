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

namespace Pimcore\Console\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Document\Newsletter\AddressSourceAdapterInterface;
use Pimcore\Tool\Newsletter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model;
use Pimcore\Logger;

class InternalNewsletterDocumentSendCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('internal:newsletter-document-send')
            ->setDescription('For internal use only')
            ->addArgument("sendingId")->addArgument("hostUrl");
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sendingId = $input->getArgument("sendingId");
        $hostUrl = $input->getArgument("hostUrl");

        $tmpStore = Model\Tool\TmpStore::get($sendingId);

        if (empty($tmpStore)) {
            Logger::alert("No sending configuration for $sendingId found. Cannot send newsletter.");
            exit;
        }

        $data = $tmpStore->getData();

        if ($data['inProgress']) {
            Logger::alert("Cannot send newsletters because there's already one active sending process.");
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
         * @var $addressAdapter \Pimcore\Document\Newsletter\AddressSourceAdapterInterface
         */
        $addressAdapter = new $adapterClass($adapterParams);


        if ($document->getSendingMode() == Newsletter::SENDING_MODE_BATCH) {
            $this->doSendMailInBatchMode($document, $addressAdapter, $sendingId, $hostUrl);
        } else {
            $this->doSendMailInSingleMode($document, $addressAdapter, $sendingId, $hostUrl);
        }

        Model\Tool\TmpStore::delete($sendingId);
    }

    /**
     * @param Model\Document\Newsletter $document
     * @param AddressSourceAdapterInterface $addressAdapter
     * @param $sendingId
     * @param $hostUrl
     */
    protected function doSendMailInBatchMode(Model\Document\Newsletter $document, AddressSourceAdapterInterface $addressAdapter, $sendingId, $hostUrl)
    {
        $mail = \Pimcore\Tool\Newsletter::prepareMail($document, $hostUrl);
        $sendingParamContainers = $addressAdapter->getMailAddressesForBatchSending();

        $currentCount = 0;
        $totalCount = $addressAdapter->getTotalRecordCount();

        //calculate page size based on total item count - with min page size 3 and max page size 10
        $fifth = $totalCount / 5;
        $pageSize = $fifth > 10 ? 10 : ($fifth < 3 ? 3 : intval($fifth));

        foreach ($sendingParamContainers as $sendingParamContainer) {
            $tmpStore = Model\Tool\TmpStore::get($sendingId);

            if (empty($tmpStore)) {
                Logger::warn("Sending configuration for sending ID $sendingId was deleted. Cancelling sending process.");
                exit;
            }

            if ($currentCount % $pageSize == 0) {
                Logger::info("Sending newsletter " . $currentCount . " / " . $totalCount. " [" . $document->getId(). "]");
                $data = $tmpStore->getData();
                $data['progress'] = round($currentCount / $totalCount * 100, 2);
                $tmpStore->setData($data);
                $tmpStore->update();
                \Pimcore::collectGarbage();
            }

            try {
                \Pimcore\Tool\Newsletter::sendNewsletterDocumentBasedMail($mail, $sendingParamContainer);
            } catch (\Exception $e) {
                Logger::err('Exception while sending newsletter: '.$e->getMessage());
            }

            $currentCount++;
        }
    }

    /**
     * @param Model\Document\Newsletter $document
     * @param AddressSourceAdapterInterface $addressAdapter
     * @param $sendingId
     * @param $hostUrl
     */
    protected function doSendMailInSingleMode(Model\Document\Newsletter $document, AddressSourceAdapterInterface $addressAdapter, $sendingId, $hostUrl)
    {
        $totalCount = $addressAdapter->getTotalRecordCount();

        //calculate page size based on total item count - with min page size 3 and max page size 10
        $fifth = $totalCount / 5;
        $limit = $fifth > 10 ? 10 : ($fifth < 3 ? 3 : intval($fifth));
        $offset = 0;
        $hasElements = true;

        while ($hasElements) {
            $tmpStore = Model\Tool\TmpStore::get($sendingId);

            $data = $tmpStore->getData();

            Logger::info("Sending newsletter " . $hasElements . " / " . $totalCount. " [" . $document->getId(). "]");

            $data['progress'] = round($offset / $totalCount * 100, 2);
            $tmpStore->setData($data);
            $tmpStore->update();

            $sendingParamContainers = $addressAdapter->getParamsForSingleSending($limit, $offset);
            foreach ($sendingParamContainers as $sendingParamContainer) {
                try {
                    $mail = \Pimcore\Tool\Newsletter::prepareMail($document, $sendingParamContainer, $hostUrl);
                    \Pimcore\Tool\Newsletter::sendNewsletterDocumentBasedMail($mail, $sendingParamContainer);
                } catch (\Exception $e) {
                    Logger::err('Exception while sending newsletter: '.$e->getMessage());
                }


                if (empty($tmpStore)) {
                    Logger::warn("Sending configuration for sending ID $sendingId was deleted. Cancelling sending process.");
                    exit;
                }
            }

            $offset += $limit;
            $hasElements = count($sendingParamContainers);

            \Pimcore::collectGarbage();
        }
    }
}
