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

namespace Pimcore\Model\Tool\CustomReport;

use Pimcore\Model;

/**
 * @internal
 *
 * @method bool isWriteable()
 * @method string getWriteTarget()
 * @method void delete()
 * @method void save()
 */
class Config extends Model\AbstractModel implements \JsonSerializable
{
    /**
     * @var string
     */
    protected string $name = '';

    /**
     * @var string
     */
    protected string $sql = '';

    /**
     * @var array
     */
    protected array $dataSourceConfig = [];

    /**
     * @var array
     */
    protected array $columnConfiguration = [];

    /**
     * @var string
     */
    protected string $niceName = '';

    /**
     * @var string
     */
    protected string $group = '';

    /**
     * @var string
     */
    protected string $groupIconClass = '';

    /**
     * @var string
     */
    protected string $iconClass = '';

    /**
     * @var bool
     */
    protected bool $menuShortcut = true;

    /**
     * @var string
     */
    protected string $reportClass = '';

    /**
     * @var string
     */
    protected string $chartType = '';

    /**
     * @var string
     */
    protected string $pieColumn = '';

    /**
     * @var string
     */
    protected string $pieLabelColumn = '';

    /**
     * @var string
     */
    protected string $xAxis = '';

    /**
     * @var string|array
     */
    protected string|array $yAxis = [];

    /**
     * @var int|null
     */
    protected ?int $modificationDate;

    /**
     * @var int|null
     */
    protected ?int $creationDate;

    /**
     * @var bool
     */
    protected bool $shareGlobally = true;

    /**
     * @var string[]
     */
    protected array $sharedUserNames = [];

    /**
     * @var string[]
     */
    protected array $sharedRoleNames = [];

    /**
     * @param string $name
     *
     * @return null|Config
     *
     * @throws \Exception
     */
    public static function getByName(string $name): ?Config
    {
        try {
            $report = new self();

            /** @var Model\Tool\CustomReport\Config\Dao $dao */
            $dao = $report->getDao();
            $dao->getByName($name);

            return $report;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param Model\User|null $user
     *
     * @return array
     */
    public static function getReportsList(Model\User $user = null): array
    {
        $reports = [];

        $list = new Config\Listing();
        if ($user) {
            $items = $list->getDao()->loadForGivenUser($user);
        } else {
            $items = $list->getDao()->loadList();
        }

        foreach ($items as $item) {
            $reports[] = [
                'id' => $item->getName(),
                'text' => $item->getName(),
                'cls' => 'pimcore_treenode_disabled',
                'writeable' => $item->isWriteable(),
            ];
        }

        return $reports;
    }

    /**
     * @param \stdClass $configuration
     * @param Config|null $fullConfig
     *
     * @return Model\Tool\CustomReport\Adapter\CustomReportAdapterInterface
     *@deprecated Use ServiceLocator with id 'pimcore.custom_report.adapter.factories' to determine the factory for the adapter instead
     *
     */
    public static function getAdapter(\stdClass $configuration, Config $fullConfig = null): Adapter\CustomReportAdapterInterface
    {
        $type = $configuration->type ? $configuration->type : 'sql';
        $serviceLocator = \Pimcore::getContainer()->get('pimcore.custom_report.adapter.factories');

        if (!$serviceLocator->has($type)) {
            throw new \RuntimeException(sprintf('Could not find Custom Report Adapter with type %s', $type));
        }

        /** @var Model\Tool\CustomReport\Adapter\CustomReportAdapterFactoryInterface $factory */
        $factory = $serviceLocator->get($type);

        return $factory->create($configuration, $fullConfig);
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setSql(string $sql)
    {
        $this->sql = $sql;
    }

    /**
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    public function setColumnConfiguration(array $columnConfiguration)
    {
        $this->columnConfiguration = $columnConfiguration;
    }

    /**
     * @return array
     */
    public function getColumnConfiguration(): array
    {
        return $this->columnConfiguration;
    }

    public function setGroup(string $group)
    {
        $this->group = $group;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    public function setGroupIconClass(string $groupIconClass)
    {
        $this->groupIconClass = $groupIconClass;
    }

    /**
     * @return string
     */
    public function getGroupIconClass(): string
    {
        return $this->groupIconClass;
    }

    public function setIconClass(string $iconClass)
    {
        $this->iconClass = $iconClass;
    }

    /**
     * @return string
     */
    public function getIconClass(): string
    {
        return $this->iconClass;
    }

    public function setNiceName(string $niceName)
    {
        $this->niceName = $niceName;
    }

    /**
     * @return string
     */
    public function getNiceName(): string
    {
        return $this->niceName;
    }

    public function setMenuShortcut(bool $menuShortcut)
    {
        $this->menuShortcut = (bool) $menuShortcut;
    }

    /**
     * @return bool
     */
    public function getMenuShortcut(): bool
    {
        return $this->menuShortcut;
    }

    public function setDataSourceConfig(array $dataSourceConfig)
    {
        $this->dataSourceConfig = $dataSourceConfig;
    }

    /**
     * @return \stdClass|null
     */
    public function getDataSourceConfig(): ?\stdClass
    {
        if (is_array($this->dataSourceConfig) && isset($this->dataSourceConfig[0])) {
            $dataSourceConfig = new \stdClass();
            $dataSourceConfigArray = $this->dataSourceConfig[0];

            foreach ($dataSourceConfigArray as $key => $value) {
                $dataSourceConfig->$key = $value;
            }

            return $dataSourceConfig;
        }

        return null;
    }

    public function setChartType(string $chartType)
    {
        $this->chartType = $chartType;
    }

    /**
     * @return string
     */
    public function getChartType(): string
    {
        return $this->chartType;
    }

    public function setPieColumn(string $pieColumn)
    {
        $this->pieColumn = $pieColumn;
    }

    /**
     * @return string
     */
    public function getPieColumn(): string
    {
        return $this->pieColumn;
    }

    public function setXAxis(string $xAxis)
    {
        $this->xAxis = $xAxis;
    }

    /**
     * @return string
     */
    public function getXAxis(): string
    {
        return $this->xAxis;
    }

    public function setYAxis(array|string $yAxis)
    {
        $this->yAxis = $yAxis;
    }

    /**
     * @return array|string
     */
    public function getYAxis(): array|string
    {
        return $this->yAxis;
    }

    public function setPieLabelColumn(string $pieLabelColumn)
    {
        $this->pieLabelColumn = $pieLabelColumn;
    }

    /**
     * @return string
     */
    public function getPieLabelColumn(): string
    {
        return $this->pieLabelColumn;
    }

    /**
     * @return int|null
     */
    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setModificationDate(int $modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return int|null
     */
    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function setCreationDate(int $creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return string
     */
    public function getReportClass(): string
    {
        return $this->reportClass;
    }

    public function setReportClass(string $reportClass)
    {
        $this->reportClass = $reportClass;
    }

    /**
     * @return bool
     */
    public function getShareGlobally(): bool
    {
        return $this->shareGlobally;
    }

    public function setShareGlobally(bool $shareGlobally): void
    {
        $this->shareGlobally = $shareGlobally;
    }

    /**
     * @return int[]
     */
    public function getSharedUserIds(): array
    {
        $sharedUserIds = [];
        if ($this->sharedUserNames) {
            foreach ($this->sharedUserNames as $username) {
                $user = Model\User::getByName($username);
                if ($user) {
                    $sharedUserIds[] = $user->getId();
                }
            }
        }

        return $sharedUserIds;
    }

    /**
     * @return int[]
     */
    public function getSharedRoleIds(): array
    {
        $sharedRoleIds = [];
        if ($this->sharedRoleNames) {
            foreach ($this->sharedRoleNames as $name) {
                $role = Model\User\Role::getByName($name);
                if ($role) {
                    $sharedRoleIds[] = $role->getId();
                }
            }
        }

        return $sharedRoleIds;
    }

    /**
     * @return string[]
     */
    public function getSharedUserNames(): array
    {
        return $this->sharedUserNames;
    }

    /**
     * @param string[] $sharedUserNames
     */
    public function setSharedUserNames(array $sharedUserNames): void
    {
        $this->sharedUserNames = $sharedUserNames;
    }

    /**
     * @return string[]
     */
    public function getSharedRoleNames(): array
    {
        return $this->sharedRoleNames;
    }

    /**
     * @param string[] $sharedRoleNames
     */
    public function setSharedRoleNames(array $sharedRoleNames): void
    {
        $this->sharedRoleNames = $sharedRoleNames;
    }

    public function jsonSerialize(): array
    {
        $data = $this->getObjectVars();
        $data['sharedUserIds'] = $this->getSharedUserIds();
        $data['sharedRoleIds'] = $this->getSharedRoleIds();

        return $data;
    }

    public function __clone()
    {
        if ($this->dao) {
            $this->dao = clone $this->dao;
            $this->dao->setModel($this);
        }
    }
}
