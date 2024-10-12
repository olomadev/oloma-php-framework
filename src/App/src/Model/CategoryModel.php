<?php
declare(strict_types=1);

namespace App\Model;

use Exception;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\Db\TableGateway\TableGatewayInterface;

use function isUid;

class CategoryModel
{
    private $conn;
    private $depth = 0;
    private $cache;
    private $adapter;
    private $categories;

    public function __construct(
        TableGatewayInterface $categories,
        StorageInterface $cache,
    )
    {
        $this->cache = $cache;
        $this->categories = $categories;
        $this->adapter = $categories->getAdapter();
        $this->conn = $this->adapter->getDriver()->getConnection();
    }

    public function findAll()
    {
        $key = CACHE_ROOT_KEY.Self::class.':'.__FUNCTION__;
        if ($this->cache->hasItem($key)) {
            return $this->cache->getItem($key);
        } 
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            'id' => 'categoryId',
            'name',
            'parentId',
            'lft',
            'rgt',
        ]);
        $select->from(['c' => 'categories']);
        $nest = $select->where->nest();
            $nest->and->between('c.lft', new Expression('c.lft'), new Expression('c.rgt'));
        $nest->unnest();
        $select->group(['c.name']);
        $select->order(['c.lft']);

        $statement = $sql->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();
        $options = iterator_to_array($resultSet);
        
        if (! empty($options))  {
            $this->cache->setItem($key, $options);
        }
        return $options;
    }

    public function findAllByPaging()
    {
        $key = CACHE_ROOT_KEY.Self::class.':'.__FUNCTION__;
        if ($this->cache->hasItem($key)) {
            return $this->cache->getItem($key);
        }        
        /**
         * Find all category tree
         * 
         * SELECT c.name, c.slug, c.parentId, c.rgt, c.lft
            FROM
            categories c
            WHERE
            c.lft BETWEEN c.lft AND c.rgt 
            GROUP BY
            c.name 
            ORDER BY
            c.lft;
        */
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            'categoryId',
            'parentId',
            'name',
            'slug',
            'lft',
            'rgt',
        ]);
        $select->from(['c' => 'categories']);
        $nest = $select->where->nest();
            $nest->and->between('c.lft', new Expression('c.lft'), new Expression('c.rgt'));
        $nest->unnest();
        $select->group(['c.name']);
        $select->order(['c.lft']);

        $statement = $sql->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();
        $items = iterator_to_array($resultSet);
        $itemsArray = $this->buildTree($items, 0);

        if (! empty($itemsArray))  {
            $this->cache->setItem($key, $itemsArray);    
        }
        return $itemsArray;
    }

    public function create(array $data)
    {
        $categoryId = (string)$data['id'];
        $name = (string)$data['categories']['name'];
        $parentId = (string)$data['categories']['parentId'];
        $rgtValue = (int)$data['categories']['rgt'];

        // UPDATE categories SET rgt = rgt + 2 WHERE rgt > $rgtValue;
        // UPDATE categories SET lft = lft + 2 WHERE lft > $rgtValue;

        // INSERT INTO categories(categoryId, name, parentId, lft, rgt) VALUES('id', $parentId, 'GAME CONSOLES', $rgtValue + 1, $rgtValue + 2);
        $this->deleteCache();
        try {
            $this->conn->beginTransaction();

            $statement = $this->adapter->createStatement('UPDATE `categories` SET rgt = rgt + 2 WHERE `rgt` > ?');
            $statement->execute([$rgtValue]);
            $statement = $this->adapter->createStatement('UPDATE `categories` SET lft = lft + 2 WHERE `lft` > ?');
            $statement->execute([$rgtValue]);
            
            $data = array();
            $data['categoryId'] = $categoryId;
            $data['name'] = $name;
            $data['slug'] = "";
            $data['parentId'] = $parentId;
            $data['lft'] = intval($rgtValue + 1);
            $data['rgt'] = intval($rgtValue + 2);

            $this->categories->insert($data);
            $this->conn->commit();
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function delete(string $categoryId)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            'lft',
            'rgt',
        ]);
        $select->from(['c' => 'categories']);
        $select->where(['categoryId' => $categoryId]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $resultSet = $statement->execute();
        $row = $resultSet->current();
        $statement->getResource()->closeCursor();

        if (!$row) {
            return;
        }
        $rgtValue = (int)$row['rgt'];
        $lftValue = (int)$row['lft'];
        $width = $rgtValue - $lftValue + 1;

        $this->deleteCache();
        try {
            $this->conn->beginTransaction();
            
            // SELECT @myLeft := lft, @myRight := rgt, @myWidth := rgt - lft + 1 FROM categories WHERE name = 'GAME CONSOLES';

            // DELETE FROM categories WHERE lft BETWEEN @myLeft AND @myRight;
            // 
            // UPDATE categories SET rgt = rgt - @myWidth WHERE rgt > @myRight;
            // UPDATE categories SET lft = lft - @myWidth WHERE lft > @myRight;

            $statement = $this->adapter->createStatement('DELETE FROM `categories` WHERE lft BETWEEN ? AND ?');
            $statement->execute([$lftValue, $rgtValue]);

            $statement = $this->adapter->createStatement('UPDATE `categories` SET rgt = rgt - ? WHERE `rgt` > ?');
            $statement->execute([$width, $rgtValue]);
            $statement = $this->adapter->createStatement('UPDATE `categories` SET lft = lft - ? WHERE `lft` > ?');
            $statement->execute([$width, $rgtValue]);

            $this->conn->commit();
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    protected function buildTree($items, $parentId = 0)
    {
        $branch = [];
        foreach ($items as $item) {
            if ($item['parentId'] == $parentId) {
                $children = $this->buildTree($items, $item['categoryId']);
                $node = [
                    'id' => $item['categoryId'],
                    'title' => $item['name'],
                    'parentId' => $item['parentId'],
                    'rgt' => $item['rgt'],
                    'lft' => $item['lft'],
                ];
                if (!empty($children)) {
                    $node['children'] = $children;
                }
                $branch[] = $node;
            }
        }
        return $branch;
    }

    public function update(array $data, bool $move = false)
    {
        $categoryId = (string)$data['id'];
        $name = (string)$data['categories']['name'];
        $parentId = (string)$data['categories']['parentId'];
        $rgtValue = (int)$data['categories']['rgt'];

        if (isUid($parentId)) {
            $sql = new Sql($this->adapter);
            $select = $sql->select();
            $select->columns([
                'categoryId',
                'lft',
                'rgt',
            ]);
            $select->from(['c' => 'categories']);
            $select->where(['categoryId' => $parentId]);
            $statement = $sql->prepareStatementForSqlObject($select);
            $resultSet = $statement->execute();
            $row = $resultSet->current();
            $statement->getResource()->closeCursor();
            if ($row) {
                $data['targetNodeId'] = $row['categoryId'];
                $data['parentRgt'] = $row['rgt'];
                $data['parentLft'] = $row['lft'];
            }
        }
        try {
            $this->conn->beginTransaction();
            if (isUid($parentId) && $move) {
                $this->move($data);
            }
            $update = array();
            $update['name'] = $name;
            $update['slug'] = "";
            $this->categories->update($update, ['categoryId' => $data['id']]);
            
            $this->deleteCache();
            $this->conn->commit();
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
 
    /**
     * How to move nested categories ? 
     * 
     * https://stackoverflow.com/questions/889527/move-node-in-nested-set
     * 
     * @param  array  $data
     * @return void
     */
    protected function move(array $data)
    {
        //
        // source category
        // 
        $nodeId = (string)$data['id'];
        $nodePosRight = (int)$data['categories']['rgt'];
        $nodePosLeft = (int)$data['categories']['lft'];
        //
        // target category
        // 
        $targetNodeId = (string)$data['targetNodeId'];
        $targetPosLeft = (string)$data['parentLft'];
        $targetPosRight = (string)$data['parentRgt'];
        $nodeSize = $nodePosRight - $nodePosLeft + 1;
        //
        // -- step 1: temporary "remove" moving node
        // 
        $sql = "UPDATE `categories` SET `lft` = 0-(`lft`), `rgt` = 0-(`rgt`) WHERE `lft` >= $nodePosLeft AND `rgt` <= $nodePosRight";
        $this->adapter->query($sql, $this->adapter::QUERY_MODE_EXECUTE);
        //
        // -- step 2: decrease left and/or right position values of currently 'lower' items (and parents)
        //
        $sql = "UPDATE `categories` SET `lft` = `lft` - $nodeSize WHERE `lft` > $nodePosRight";
        $this->adapter->query($sql, $this->adapter::QUERY_MODE_EXECUTE);
        
        $sql = "UPDATE `categories` SET `rgt` = `rgt` - $nodeSize WHERE `rgt` > $nodePosRight";
        $this->adapter->query($sql, $this->adapter::QUERY_MODE_EXECUTE);
        //
        // -- step 3: increase left and/or right position values of future 'lower' items (and parents)
        //
        $sql = "UPDATE `categories` SET `lft` = `lft` + $nodeSize WHERE `lft` >= IF($targetPosRight > $nodePosRight, $targetPosRight - $nodeSize, $targetPosRight)";
        $this->adapter->query($sql, $this->adapter::QUERY_MODE_EXECUTE);

        $sql = "UPDATE `categories` SET `rgt` = `rgt` + $nodeSize WHERE `rgt` >= IF($targetPosRight > $nodePosRight, $targetPosRight - $nodeSize, $targetPosRight)";
        $this->adapter->query($sql, $this->adapter::QUERY_MODE_EXECUTE);
        //
        // -- step 4: move node (ant it's subnodes) and update it's parent item id
        //
        $sql = "UPDATE `categories` SET ";
        $sql.= "`lft` = 0-(`lft`)+IF($targetPosRight > $nodePosRight, $targetPosRight - $nodePosRight - 1, $targetPosRight - $nodePosRight - 1 + $nodeSize),";
        $sql.= "`rgt` = 0-(`rgt`)+IF($targetPosRight > $nodePosRight, $targetPosRight - $nodePosRight - 1, $targetPosRight - $nodePosRight - 1 + $nodeSize) ";
        $sql.= "WHERE `lft` <= 0-$nodePosLeft AND `rgt` >= 0-$nodePosRight";
        $this->adapter->query($sql, $this->adapter::QUERY_MODE_EXECUTE);
        //
        // -- update parent id
        //
        $statement = $this->adapter->createStatement('UPDATE `categories` SET `parentId` = ? WHERE `categoryId` = ?');
        $statement->execute([$targetNodeId, $nodeId]);
    }

    private function deleteCache()
    {
        $this->cache->removeItem(CACHE_ROOT_KEY.Self::class.':findAll');
        $this->cache->removeItem(CACHE_ROOT_KEY.Self::class.':findAllByPaging');
    }

}
