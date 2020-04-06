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

namespace Pimcore\Bundle\CoreBundle\Command;

use Exception;
use Pimcore;
use Pimcore\Console\AbstractCommand;
use Pimcore\Document\Newsletter\AddressSourceAdapterFactoryInterface;
use Pimcore\Document\Newsletter\AddressSourceAdapterInterface;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Tool\Newsletter;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InternalNewsletterDocumentSendCommand extends AbstractCommand
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    protected function configure(): void
    {
        $this
            ->setHidden(true)
            ->setName('internal:newsletter-document-send')
            ->setDescription('For internal use only')
            ->addArgument('sendingId')->addArgument('hostUrl');
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pimcoreSymfonyConfig = $this->container->getParameter('pimcore.config');
        $sendingId = $input->getArgument('sendingId');
        $hostUrl = $pimcoreSymfonyConfig['documents']['newsletter']['defaultUrlPrefix'] ?: $input->getArgument('hostUrl');

        $tmpStore = Model\Tool\TmpStore::get($sendingId);

        if (null === $tmpStore) {
            Logger::alert(sprintf('No sending configuration for %s found. Cannot send newsletter.', $sendingId));
            exit;
        }

        $data = $tmpStore->getData();

        if ($data['inProgress']) {
            Logger::alert('Cannot send newsletters because there\'s already one active sending process.');
            exit;
        }

        $data['inProgress'] = 1;
        $tmpStore->setData($data);
        $tmpStore->update();

        /** @var Model\Document\Newsletter $document */
        $document = Model\Document\Newsletter::getById($data['documentId']);
        $addressSourceAdapterName = $data['addressSourceAdapterName'];
        $adapterParams = $data['adapterParams'];

        $serviceLocator = $this->container->get('pimcore.newsletter.address_source_adapter.factories');

        if (!$serviceLocator->has($addressSourceAdapterName)) {
            throw new RuntimeException(
                sprintf(
                    'Cannot send newsletters because Address Source Adapter with identifier %s could not be found',
                    $addressSourceAdapterName
                )
            );
        }

        /** @var AddressSourceAdapterFactoryInterface $addressAdapterFactory */
        $addressAdapterFactory = $serviceLocator->get($addressSourceAdapterName);
        $addressAdapter = $addressAdapterFactory->create($adapterParams);

        if ($document->getSendingMode() === Newsletter::SENDING_MODE_BATCH) {
            $this->doSendMailInBatchMode($document, $addressAdapter, $sendingId, $hostUrl);
        } else {
            $this->doSendMailInSingleMode($document, $addressAdapter, $sendingId, $hostUrl);
        }

        Model\Tool\TmpStore::delete($sendingId);

        return 0;
    }

    /**
     * @param Model\Document\Newsletter $document
     * @param AddressSourceAdapterInterface $addressAdapter
     * @param string $sendingId
     * @param string $hostUrl
     *
     * @throws Exception
     */
    protected function doSendMailInBatchMode(
        Model\Document\Newsletter $document,
        AddressSourceAdapterInterface $addressAdapter,
        $sendingId,
        $hostUrl
    ): void {
        $sendingParamContainers = $addressAdapter->getMailAddressesForBatchSending();

        $currentCount = 0;
        $totalCount = $addressAdapter->getTotalRecordCount();

        // calculate page size based on total item count - with min page size 3 and max page size 10
        $fifth = $totalCount / 5;
        $minPageSize = $fifth < 3 ? 3 : (int) $fifth;
        $pageSize = $fifth > 10 ? 10 : $minPageSize;

        foreach ($sendingParamContainers as $sendingParamContainer) {
            $mail = Newsletter::prepareMail($document, $sendingParamContainer, $hostUrl);
            $tmpStore = Model\Tool\TmpStore::get($sendingId);

            if (null === $tmpStore) {
                Logger::warn(
                    sprintf(
                        'Sending configuration for sending ID %s was deleted. Cancelling sending process.',
                        $sendingId
                    )
                );
                exit;
            }

            if ($currentCount % $pageSize === 0) {
                Logger::info(
                    sprintf('Sending newsletter %d / %s [%s]', $currentCount, $totalCount, $document->getId())
                );
                $data = $tmpStore->getData();
                $data['progress'] = round($currentCount / $totalCount * 100, 2);
                $tmpStore->setData($data);
                $tmpStore->update();
                Pimcore::collectGarbage();
            }

            try {
                Newsletter::sendNewsletterDocumentBasedMail($mail, $sendingParamContainer);
            } catch (Exception $e) {
                Logger::err(sprintf('Exception while sending newsletter: %s', $e->getMessage()));
            }

            $currentCount++;
        }
    }

    /**
     * @param Model\Document\Newsletter $document
     * @param AddressSourceAdapterInterface $addressAdapter
     * @param string $sendingId
     * @param string $hostUrl
     */
    protected function doSendMailInSingleMode(
        Model\Document\Newsletter $document,
        AddressSourceAdapterInterface $addressAdapter,
        $sendingId,
        $hostUrl
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
            $tmpStore = Model\Tool\TmpStore::get($sendingId);

            if (null === $tmpStore) {
                Logger::warn(
                    sprintf(
                        'Sending configuration for sending ID %s was deleted. Cancelling sending process.',
                        $sendingId
                    )
                );
                exit;
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
                    $mail = Newsletter::prepareMail($document, $sendingParamContainer, $hostUrl);
                    Newsletter::sendNewsletterDocumentBasedMail($mail, $sendingParamContainer);
                } catch (Exception $e) {
                    Logger::err(sprintf('Exception while sending newsletter: %s', $e->getMessage()));
                }

                ++$index;
            }

            $offset += $limit;
            $hasElements = count($sendingParamContainers);

            Pimcore::collectGarbage();
        }
    }
}
