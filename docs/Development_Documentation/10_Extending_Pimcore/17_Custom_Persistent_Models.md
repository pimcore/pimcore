# Custom Persistent Models

## When to use Custom Models

Pimcore objects are very flexible but shouldn't be used to store all types of data. For example, it doesn't make sense 
to implement a rating-, comments- or a complex blog system on top of the Pimcore objects. Sometimes people also 
try to implement quite interesting things just to get a unique object key or to build n to n relationships. This sometimes produces 
really ugly code with a lot of overhead which could be very slow, hard to refactor, and you will have a lot of pain if you have to merge multiple 
installations.

Pimcore provides 2 possible ways of working with custom entities namely Doctrine ORM and Pimcore Dao.

## Option 1: Use Doctrine ORM
Pimcore comes already with the Doctrine bundle, so you can easily create your own entities. 
Please check <http://symfony.com/doc/current/doctrine.html> for more details.  

## Option 2: Working with Pimcore Data Access Objects (Dao)

This example will show you how you can save a custom model in the database.

## Database
As a first step, create the database structure for the model, for this example I'll use a very easy model called vote. it just 
has an id, a username (just a string) and a score. If you want to write a model for a bundle you have to create the 
table(s) during the installation.

```php
CREATE TABLE `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `score` int(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8md4
```

Please keep in mind that this is just a generic example, you also could create other and more complex models.

## Model
The next step is to implement the model. To make it easy the model is stored into the `src/` library. You also could place 
it into a bundle library.

```php
# src/AppBundle/Model/Vote.php
<?php
 
namespace AppBundle\Model;
 
use Pimcore\Model\AbstractModel;
 
class Vote extends AbstractModel {
 
    /**
     * @var int
     */
    public $id;
 
    /**
     * @var string
     */
    public $username;
 
    /**
     * @var int
     */
    public $score;
 
    /**
     * get score by id
     *
     * @param $id
     * @return null|self
     */
    public static function getById($id) {
        try {
            $obj = new self;
            $obj->getDao()->getById($id);
            return $obj;
        }
        catch (\Exception $ex) {
            \Logger::warn("Vote with id $id not found");
        }
 
        return null;
    }
 
    /**
     * @param $score
     */
    public function setScore($score) {
        $this->score = $score;
    }
 
    /**
     * @return int
     */
    public function getScore() {
        return $this->score;
    }
 
    /**
     * @param $username
     */
    public function setUsername($username) {
        $this->username = $username;
    }
 
    /**
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }
 
    /**
     * @param $id
     */
    public function setId($id) {
        $this->id = $id;
    }
 
    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }
}
```

For every field in the database we need a corresponding property and a Setter/Getter. This is not really necessary, it 
just depends on your DAO, just read on and have a look at the save method in the DAO. 

The `save` and `getById` methods just call the corresponding DAO methods.

The `getDao` method looks for the nearest DAO. It just appends Dao to the class name, if the class exists you are ready 
to use the DAO. If the class doesn't exist, it just continue searching using the next namespace.

Small example: `AppBundle\Model\Vote` looks for `AppBundle\Model\Vote\Dao`, `AppBundle\Model\Dao`, `AppBundle\Dao`.
 

## DAO
Now we are ready to implement the Dao:

```php
#src/AppBundle/Model/Vote/Dao.php
<?php
namespace AppBundle\Model\Vote;
 
use Pimcore\Model\Dao\AbstractDao;
 
class Dao extends AbstractDao {
 
    protected $tableName = 'votes';
 
    /**
     * get vote by id
     *
     * @param null $id
     * @throws \Exception
     */
    public function getById($id = null) {
 
        if ($id != null)
            $this->model->setId($id);
 
        $data = $this->db->fetchRow('SELECT * FROM '.$this->tableName.' WHERE id = ?', $this->model->getId());
 
        if(!$data["id"])
            throw new \Exception("Object with the ID " . $this->model->getId() . " doesn't exists");
 
        $this->assignVariablesToModel($data);
    }
 
    /**
     * save vote
     *
     * @throws \Zend_Db_Adapter_Exception
     */
    public function save() {
        $vars = get_object_vars($this->model);
 
        $buffer = [];
 
        $validColumns = $this->getValidTableColumns($this->tableName);
 
        if(count($vars))
            foreach ($vars as $k => $v) {
 
                if(!in_array($k, $validColumns))
                    continue;
 
                $getter = "get" . ucfirst($k);
 
                if(!is_callable([$this->model, $getter]))
                    continue;
 
                $value = $this->model->$getter();
 
                if(is_bool($value))
                    $value = (int)$value;
 
                $buffer[$k] = $value;
            }
 
        if($this->model->getId() !== null) {
            $this->db->update($this->tableName, $buffer, ["id" => $this->model->getId()]);
            return;
        }
 
        $this->db->insert($this->tableName, $buffer);
        $this->model->setId($this->db->lastInsertId());
    }
 
    /**
     * delete vote
     */
    public function delete() {
        $this->db->delete($this->tableName, ["id" => $this->model->getId()]);
    }
 
}
```

Please keep in mind that this is just a very easy example DAO. Of course, you can do much more complex stuff like implementing joins, 
save dependencies or whatever you want.


## Using the Model

Now you can use your Model in your service-layer.

```php
$vote = new \AppBundle\Model\Vote();
$vote->setScore(3);
$vote->setUsername('foobar!'.mt_rand(1, 999));
$vote->save();
```


## Listing
If you need to query the data using a Pimcore entity list, you also need to implement a `Listing` and `Listing\Dao` class:

```php
#src/AppBundle/Model/Vote/Listing.php
  
<?php
 
namespace AppBundle\Model\Vote;
 
use Pimcore\Model;
use Zend\Paginator\Adapter\AdapterInterface;
use Zend\Paginator\AdapterAggregateInterface;
 
class Listing extends Model\Listing\AbstractListing implements \Zend_Paginator_Adapter_Interface, \Zend_Paginator_AdapterAggregate, \Iterator, AdapterInterface, AdapterAggregateInterface
{
    /**
     * List of Votes.
     *
     * @var array
     */
    public $data = null;
 
    /**
     * @var string|\Zend_Locale
     */
    public $locale;
 
    /**
     * List of valid order keys.
     *
     * @var array
     */
    public $validOrderKeys = array(
        'id'
    );
 
    /**
     * Test if the passed key is valid.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isValidOrderKey($key)
    {
        return in_array($key, $this->validOrderKeys);
    }
 
    /**
     * @return array
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->load();
        }
 
        return $this->data;
    }
 
    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
 
    /**
     * Methods for \Zend_Paginator_Adapter_Interface.
     */
 
    /**
     * get total count.
     *
     * @return mixed
     */
    public function count()
    {
        return $this->getTotalCount();
    }
 
    /**
     * get all items.
     *
     * @param int $offset
     * @param int $itemCountPerPage
     *
     * @return mixed
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);
 
        return $this->load();
    }
 
    /**
     * Get Paginator Adapter.
     *
     * @return $this
     */
    public function getPaginatorAdapter()
    {
        return $this;
    }
 
    /**
     * Set Locale.
     *
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
 
    /**
     * Get Locale.
     *
     * @return string|\Zend_Locale
     */
    public function getLocale()
    {
        return $this->locale;
    }
     
    /**
     * Methods for Iterator.
     */
 
    /**
     * Rewind.
     */
    public function rewind()
    {
        $this->getData();
        reset($this->data);
    }
 
    /**
     * current.
     *
     * @return mixed
     */
    public function current()
    {
        $this->getData();
        $var = current($this->data);
 
        return $var;
    }
 
    /**
     * key.
     *
     * @return mixed
     */
    public function key()
    {
        $this->getData();
        $var = key($this->data);
 
        return $var;
    }
 
    /**
     * next.
     *
     * @return mixed
     */
    public function next()
    {
        $this->getData();
        $var = next($this->data);
 
        return $var;
    }
 
    /**
     * valid.
     *
     * @return bool
     */
    public function valid()
    {
        $this->getData();
        $var = $this->current() !== false;
 
        return $var;
    }
}
```


## Listing\Dao

```php
#src/AppBundle/Model/Vote/Listing/Dao.php
  
<?php
 
namespace AppBundle\Model\Vote\Listing;
 
use Pimcore\Model\Listing;
use AppBundle\Model;
use Pimcore\Tool;
 
class Dao extends Listing\Dao\AbstractDao
{
    /**
     * @var string
     */
    protected $tableName = 'votes';
 
    /**
     * Get tableName, either for localized or non-localized data.
     *
     * @return string
     *
     * @throws \Exception
     * @throws \Zend_Exception
     */
    protected function getTableName()
    {
        return $this->tableName;
    }
 
    /**
     * get select query.
     *
     * @return \Zend_Db_Select
     *
     * @throws \Exception
     */
    public function getQuery()
    {
 
        // init
        $select = $this->db->select();
 
        // create base
        $field = $this->getTableName().'.id';
        $select->from(
            [$this->getTableName()], [
                new \Zend_Db_Expr(sprintf('SQL_CALC_FOUND_ROWS %s as id', $field, 'o_type')),
            ]
        );
 
        // add condition
        $this->addConditions($select);
 
        // group by
        $this->addGroupBy($select);
 
        // order
        $this->addOrder($select);
 
        // limit
        $this->addLimit($select);
 
        return $select;
    }
 
    /**
     * Loads objects from the database.
     *
     * @return Model\Vote[]
     */
    public function load()
    {
        // load id's
        $list = $this->loadIdList();
 
        $objects = array();
        foreach ($list as $o_id) {
            if ($object = Model\Vote::getById($o_id)) {
                $objects[] = $object;
            }
        }
 
        $this->model->setData($objects);
 
        return $objects;
    }
 
    /**
     * Loads a list for the specicifies parameters, returns an array of ids.
     *
     * @return array
     * @throws \Exception
     */
    public function loadIdList()
    {
        try {
            $query = $this->getQuery();
            $objectIds = $this->db->fetchCol($query, $this->model->getConditionVariables());
            $this->totalCount = (int) $this->db->fetchOne('SELECT FOUND_ROWS()');
 
            return $objectIds;
        } catch (\Exception $e) {
            throw $e;
        }
    }
 
    /**
     * Get Count.
     *
     * @return int
     *
     * @throws \Exception
     */
    public function getCount()
    {
        $amount = (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM '.$this->getTableName().$this->getCondition().$this->getOffsetLimit(), $this->model->getConditionVariables());
 
        return $amount;
    }
 
    /**
     * Get Total Count.
     *
     * @return int
     *
     * @throws \Exception
     */
    public function getTotalCount()
    {
        $amount = (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM '.$this->getTableName().$this->getCondition(), $this->model->getConditionVariables());
 
        return $amount;
    }
}
```


## Using the Listing
Now you can use your Listing in your service-layer.

```php
$list = \AppBundle\Model\Vote::getList();
$list->setCondition("score > ?", array(1));
$votes = $list->load();
```
