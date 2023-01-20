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

namespace Pimcore\Bundle\CustomReportsBundle\Tool\Adapter;

use Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\ReportAdapter;
use Pimcore\Bundle\CustomReportsBundle\Tool\Adapter\CustomReportAdapterFactoryInterface;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Pimcore\Document\Newsletter\AddressSourceAdapterFactoryInterface;
use Pimcore\Document\Newsletter\AddressSourceAdapterInterface;

/**
 * @internal
 */
final class ReportAddressSourceAdapterFactory implements AddressSourceAdapterFactoryInterface
{
    private ServiceLocator $reportAdapterServiceLocator;

    public function __construct(ServiceLocator $reportAdapterServiceLocator)
    {
        $this->reportAdapterServiceLocator = $reportAdapterServiceLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $params): ReportAdapter|AddressSourceAdapterInterface
    {
        $config = Config::getByName($params['reportId']);
        $configuration = $config->getDataSourceConfig();

        $reportAdapterType = $configuration->type;

        if (!$this->reportAdapterServiceLocator->has($reportAdapterType)) {
            throw new \RuntimeException(sprintf('Could not find Custom Report Adapter with type %s', $reportAdapterType));
        }

        /** @var CustomReportAdapterFactoryInterface $adapterFactory */
        $adapterFactory = $this->reportAdapterServiceLocator->get($reportAdapterType);
        $adapter = $adapterFactory->create($configuration, $config);

        return new ReportAdapter($params['emailFieldName'], $adapter);
    }
}
