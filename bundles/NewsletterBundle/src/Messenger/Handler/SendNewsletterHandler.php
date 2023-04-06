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

namespace Pimcore\Bundle\NewsletterBundle\Messenger\Handler;

use Pimcore\Bundle\NewsletterBundle\Document\Newsletter\AddressSourceAdapterFactoryInterface;
use Pimcore\Bundle\NewsletterBundle\Document\Newsletter\AddressSourceAdapterInterface;
use Pimcore\Bundle\NewsletterBundle\Messenger\SendNewsletterMessage;
use Pimcore\Bundle\NewsletterBundle\Model\Document\Newsletter;
use Pimcore\Bundle\NewsletterBundle\Tool\Newsletter as NewsletterTool;
use Pimcore\Logger;
use Pimcore\Model\Tool\TmpStore;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
class SendNewsletterHandler
{
    public function __construct(protected array $pimcoreConfig, protected ServiceProviderInterface $addressProvider)
    {
    }

    public function __invoke(SendNewsletterMessage $message): void
    {
        $sendingId = $message->getTmpStoreId();
        $hostUrl = $this->pimcoreConfig['default_url_prefix'] ?: $message->getHostUrl();

        $tmpStore = TmpStore::get($sendingId);

        if (null === $tmpStore) {
            Logger::alert(sprintf('No sending configuration for %s found. Cannot send newsletter.', $sendingId));

            return;
        }

        $data = $tmpStore->getData();

        if ($data['inProgress']) {
            Logger::alert('Cannot send newsletters because there\'s already one active sending process.');

            return;
        }

        $data['inProgress'] = 1;
        $tmpStore->setData($data);
        $tmpStore->update();

        /** @var Newsletter $document */
        $document = Newsletter::getById($data['documentId']);
        $addressSourceAdapterName = $data['addressSourceAdapterName'];
        $adapterParams = $data['adapterParams'];

        if (!$this->addressProvider->has($addressSourceAdapterName)) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot send newsletters because Address Source Adapter with identifier %s could not be found',
                    $addressSourceAdapterName
                )
            );
        }

        /** @var AddressSourceAdapterFactoryInterface $addressAdapterFactory */
        $addressAdapterFactory = $this->addressProvider->get($addressSourceAdapterName);
        $addressAdapter = $addressAdapterFactory->create($adapterParams);

        if ($document->getSendingMode() === NewsletterTool::SENDING_MODE_BATCH) {
            $this->doSendMailInBatchMode($document, $addressAdapter, $sendingId, $hostUrl);
        } else {
            $this->doSendMailInSingleMode($document, $addressAdapter, $sendingId, $hostUrl);
        }

        TmpStore::delete($sendingId);
    }

    /**
     * @throws \Exception
     */
    protected function doSendMailInBatchMode(
        Newsletter $document,
        AddressSourceAdapterInterface $addressAdapter,
        string $sendingId,
        string $hostUrl
    ): void {
        $sendingParamContainers = $addressAdapter->getMailAddressesForBatchSending();

        $currentCount = 0;
        $totalCount = $addressAdapter->getTotalRecordCount();

        // calculate page size based on total item count - with min page size 3 and max page size 10
        $fifth = $totalCount / 5;
        $minPageSize = $fifth < 3 ? 3 : (int) $fifth;
        $pageSize = $fifth > 10 ? 10 : $minPageSize;

        foreach ($sendingParamContainers as $sendingParamContainer) {
            $mail = NewsletterTool::prepareMail($document, $sendingParamContainer, $hostUrl);
            $tmpStore = TmpStore::get($sendingId);

            if (null === $tmpStore) {
                Logger::warn(
                    sprintf(
                        'Sending configuration for sending ID %s was deleted. Cancelling sending process.',
                        $sendingId
                    )
                );

                return;
            }

            if ($currentCount % $pageSize === 0) {
                Logger::info(
                    sprintf('Sending newsletter %d / %s [%s]', $currentCount, $totalCount, $document->getId())
                );
                $data = $tmpStore->getData();
                $data['progress'] = round($currentCount / $totalCount * 100, 2);
                $tmpStore->setData($data);
                $tmpStore->update();
                \Pimcore::collectGarbage();
            }

            try {
                NewsletterTool::sendNewsletterDocumentBasedMail($mail, $sendingParamContainer);
            } catch (\Exception $e) {
                Logger::err(sprintf('Exception while sending newsletter: %s', $e->getMessage()));
            }

            $currentCount++;
        }
    }

    protected function doSendMailInSingleMode(
        Newsletter $document,
        AddressSourceAdapterInterface $addressAdapter,
        string $sendingId,
        string $hostUrl
    ): void {
        $totalCount = $addressAdapter->getTotalRecordCount();

        //calculate page size based on total item count - with min page size 3 and max page size 10
        $fifth = $totalCount / 5;
        $minPageSize = $fifth < 3 ? 3 : (int) $fifth;
        $limit = $fifth > 10 ? 10 : $minPageSize;
        $offset = 0;
        $hasElements = true;
        $index = 1;

        while ($hasElements) {
            $tmpStore = TmpStore::get($sendingId);

            if (null === $tmpStore) {
                Logger::warn(
                    sprintf(
                        'Sending configuration for sending ID %s was deleted. Cancelling sending process.',
                        $sendingId
                    )
                );

                return;
            }

            $data = $tmpStore->getData();

            $data['progress'] = round($offset / $totalCount * 100, 2);
            $tmpStore->setData($data);
            $tmpStore->update();

            $sendingParamContainers = $addressAdapter->getParamsForSingleSending($limit, $offset);
            foreach ($sendingParamContainers as $sendingParamContainer) {
                // Please leave log-level warning, otherwise current status of sending process won't be logged in newsletter-sending-output.log
                Logger::warn(
                    sprintf('Sending newsletter %d / %s [%s]', $index, $totalCount, $document->getId())
                );

                try {
                    $mail = NewsletterTool::prepareMail($document, $sendingParamContainer, $hostUrl);
                    NewsletterTool::sendNewsletterDocumentBasedMail($mail, $sendingParamContainer);
                } catch (\Exception $e) {
                    Logger::err(sprintf('Exception while sending newsletter: %s', $e->getMessage()));
                }

                ++$index;
            }

            $offset += $limit;
            $hasElements = count($sendingParamContainers);

            \Pimcore::collectGarbage();
        }
    }
}
