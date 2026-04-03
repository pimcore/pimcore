---
title: Custom Persistent Models
description: Store custom data using Doctrine ORM or the Pimcore DAO pattern.
---

# Custom Persistent Models

## When to Use Custom Models

Pimcore data objects are flexible but not the right fit for every data structure.
Ratings, comments, complex blog systems, unique key generation, or n-to-n
relationship tables are better served by dedicated database models. Custom models
avoid overhead, improve performance, and simplify merging across installations.

Pimcore supports two approaches: Doctrine ORM and the Pimcore DAO pattern.

## Option 1: Doctrine ORM

Pimcore ships with the Doctrine bundle, so you can create standard Doctrine
entities. See the
[Symfony Doctrine documentation](https://symfony.com/doc/current/doctrine.html)
for details.

:::warning
Pimcore uses the default Doctrine connection and the default entity manager.
You may only use the default entity manager for the default connection. Any
additional entity manager will throw an exception when you run the Doctrine
schema tool.

If you need a separate entity manager, you must also configure a separate
database connection pointing to a different database. Using the same database
with a different entity manager causes table drops.
:::

## Option 2: Pimcore Data Access Objects (DAO)

This section walks through building a custom model backed by the Pimcore DAO
layer.

### Database

Create the table for your model. For bundles, create the table during
bundle installation.

```sql
CREATE TABLE `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `score` int(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8mb4
```

### Model

Each database column needs a corresponding property with getter and setter
methods. The properties are public for simplicity, but the getters are required
because the DAO's `save()` method uses them to extract values.

The model extends `Pimcore\Model\AbstractModel`, which provides DAO
integration. `AbstractModel` uses a `__call()` magic method that delegates
unknown method calls to the DAO. This is how `save()` and `delete()` work:
calling `$vote->save()` triggers `AbstractModel::__call('save')`, which
forwards to `Dao::save()`.

The `getById()` method is an explicit static factory that instantiates the
model and loads data through the DAO.

```php
# src/Model/Vote.php
<?php

namespace App\Model;

use Pimcore\Model\AbstractModel;
use Pimcore\Model\Exception\NotFoundException;

class Vote extends AbstractModel
{
    public ?int $id = null;

    public ?string $username = null;

    public ?int $score = null;

    public static function getById(int $id): ?self
    {
        try {
            $obj = new self;
            $obj->getDao()->getById($id);
            return $obj;
        }
        catch (NotFoundException $ex) {
            \Pimcore\Logger::debug("Vote with id $id not found");
        }

        return null;
    }

    /**
     * Returns a new Listing instance for querying votes.
     */
    public static function getList(): Vote\Listing
    {
        return new Vote\Listing();
    }

    public function setScore(?int $score): void
    {
        $this->score = $score;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
```

The `getDao()` method resolves the DAO class by appending `\Dao` to the model's
namespace and walking up the hierarchy. For `App\Model\Vote`, it checks
`App\Model\Vote\Dao`, then `App\Model\Dao`, then `App\Dao`.

### DAO

The DAO handles all database operations:

```php
# src/Model/Vote/Dao.php
<?php

namespace App\Model\Vote;

use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\Exception\NotFoundException;

class Dao extends AbstractDao
{
    protected string $tableName = 'votes';

    /**
     * @throws NotFoundException
     */
    public function getById(?int $id = null): void
    {
        if ($id !== null) {
            $this->model->setId($id);
        }

        $data = $this->db->fetchAssociative(
            'SELECT * FROM ' . $this->tableName . ' WHERE id = ?',
            [$this->model->getId()]
        );

        if (!$data) {
            throw new NotFoundException(
                "Object with the ID " . $this->model->getId() . " doesn't exist"
            );
        }

        $this->assignVariablesToModel($data);
    }

    public function save(): void
    {
        $vars = get_object_vars($this->model);

        $buffer = [];

        $validColumns = $this->getValidTableColumns($this->tableName);

        if (count($vars)) {
            foreach ($vars as $k => $v) {
                if (!in_array($k, $validColumns)) {
                    continue;
                }

                $getter = "get" . ucfirst($k);

                if (!is_callable([$this->model, $getter])) {
                    continue;
                }

                $value = $this->model->$getter();

                if (is_bool($value)) {
                    $value = (int)$value;
                }

                $buffer[$k] = $value;
            }
        }

        if ($this->model->getId() !== null) {
            $this->db->update($this->tableName, $buffer, ["id" => $this->model->getId()]);
            return;
        }

        $this->db->insert($this->tableName, $buffer);
        $this->model->setId($this->db->lastInsertId());
    }

    public function delete(): void
    {
        $this->db->delete($this->tableName, ["id" => $this->model->getId()]);
    }
}
```

### Using the Model

```php
$vote = new \App\Model\Vote();
$vote->setScore(3);
$vote->setUsername('foobar!' . random_int(1, 999));
$vote->save();
```

### Listing

To query votes using a Pimcore listing, implement a `Listing` and
`Listing\Dao` class. `AbstractListing` already implements `Iterator` and
`Countable`, so your listing only needs to add pagination support and any
custom properties.

```php
# src/Model/Vote/Listing.php
<?php

namespace App\Model\Vote;

use Pimcore\Model;
use Pimcore\Model\Paginator\PaginateListingInterface;

class Listing extends Model\Listing\AbstractListing implements PaginateListingInterface
{
    /**
     * Optional: locale for localized queries.
     */
    public ?string $locale = null;

    public function count(): int
    {
        return $this->getTotalCount();
    }

    public function getItems(int $offset, int $itemCountPerPage): array
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);

        return $this->load();
    }

    /**
     * @return $this
     */
    public function getPaginatorAdapter(): static
    {
        return $this;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }
}
```

### Listing\Dao

```php
# src/Model/Vote/Listing/Dao.php
<?php

namespace App\Model\Vote\Listing;

use Pimcore\Model\Listing;
use App\Model;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Pimcore\Model\Listing\Dao\QueryBuilderHelperTrait;

class Dao extends Listing\Dao\AbstractDao
{
    use QueryBuilderHelperTrait;

    protected string $tableName = 'votes';

    protected function getTableName(): string
    {
        return $this->tableName;
    }

    public function getQueryBuilder(): DoctrineQueryBuilder
    {
        $queryBuilder = $this->db->createQueryBuilder();
        $field = $this->getTableName() . '.id';
        $queryBuilder->select($field . ' as id');
        $queryBuilder->from($this->getTableName());

        $this->applyListingParametersToQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * @return Model\Vote[]
     */
    public function load(): array
    {
        $list = $this->loadIdList();

        $objects = [];
        foreach ($list as $id) {
            if ($object = Model\Vote::getById($id)) {
                $objects[] = $object;
            }
        }

        $this->model->setData($objects);

        return $objects;
    }

    /**
     * @return int[]
     */
    public function loadIdList(): array
    {
        $query = $this->getQueryBuilder();
        $objectIds = $this->db->fetchFirstColumn(
            $query->getSQL(),
            $query->getParameters(),
            $query->getParameterTypes()
        );

        return array_map('intval', $objectIds);
    }

    public function getCount(): int
    {
        if ($this->model->isLoaded()) {
            return count($this->model->getData());
        } else {
            $idList = $this->loadIdList();

            return count($idList);
        }
    }

    public function getTotalCount(): int
    {
        $queryBuilder = $this->getQueryBuilder();
        $this->prepareQueryBuilderForTotalCount(
            $queryBuilder,
            $this->getTableName() . '.id'
        );

        if ($this->isQueryBuilderPartInUse($queryBuilder, 'groupBy')
            || $this->isQueryBuilderPartInUse($queryBuilder, 'having')
        ) {
            return (int)$this->db->fetchOne(
                'SELECT COUNT(*) FROM (' . $queryBuilder->getSQL() . ') as XYZ'
            );
        } else {
            return (int)$this->db->fetchOne(
                $queryBuilder->getSQL(),
                $queryBuilder->getParameters(),
                $queryBuilder->getParameterTypes()
            );
        }
    }
}
```

### Using the Listing

```php
$list = \App\Model\Vote::getList();
$list->setCondition("score > ?", [1]);
$votes = $list->load();
```
