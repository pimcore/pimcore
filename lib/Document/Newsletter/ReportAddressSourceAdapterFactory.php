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

namespace Pimcore\Document\Newsletter;

use Pimcore\Document\Newsletter\AddressSourceAdapter\ReportAdapter;
use Pimcore\Model\Tool\CustomReport\Adapter\CustomReportAdapterFactoryInterface;
use Pimcore\Model\Tool\CustomReport\Config;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ReportAddressSourceAdapterFactory implements AddressSourceAdapterFactoryInterface
{
    /**
     * @var ServiceLocator
     */
    private $reportAdapterServiceLocator;

    /**
     * @param ServiceLocator $reportAdapterServiceLocator
     */
    public function __construct(ServiceLocator $reportAdapterServiceLocator)
    {
        $this->reportAdapterServiceLocator = $reportAdapterServiceLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function create($params)
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
