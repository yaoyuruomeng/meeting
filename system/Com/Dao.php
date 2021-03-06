<?php

/**
 * Data Access Object
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Dao.php 10650 2014-04-23 03:51:12Z jiangjian $
 */

class Com_Dao
{
    /**
     * 当前库名
     *
     * @var string
     */
    protected $_dbName;

    /**
     * 当前表名
     *
     * @var string
     */
    protected $_tableName;

    /**
     * 主键
     *
     * @var string
     */
    protected $_pk = 'id';

    /**
     * 可选属性，用于 pairs()
     *
     * @var string
     */
    protected $_nameField = 'name';

    protected $_options  = array();
    protected $_params   = array();
    protected $_isMaster = false;

    protected $_sqlBuilder;

    /**
     * Db 连接实例（们）
     *
     * @var array(Com_DB_PDO)
     */
    protected static $_dbs = array();

    /**
     * 缓存连接实例
     *
     * @var object
     */
    protected $_cache;

    /**
     * 当前查询是否需要缓存
     *
     * @var bool
     */
    protected $_isCached = false;

    /**
     * 本DAO是否需要缓存
     *
     * @var bool
     */
    protected $_isDaoNeedCache = false;

    /**
     * 缓存生存周期（单位：秒）
     * 默认0表示永不过期
     *
     * @var int
     */
    protected $_cacheTTL = 0;

    public function __construct()
    {
        // 创建缺省的缓存实例
        //$this->_cache = F('Memcache')->default;

        // 如果本DAO不需要缓存，则默认情况下本DAO的所有查询也都不需要缓存，除非调用 isCached(1) 单次手动开启
        if (! $this->_isDaoNeedCache) {
            $this->_isCached = false;
        }

        // 子类构函
        if (method_exists($this, '_init')) {
            $this->_init();
        }
    }

    /**
     * 获取 Db 连接实例
     *
     * @return Com_DB_PDO
     */
    public function db()
    {
    	if(! $this->_dbName){
    		$this->setDbName(DB_SHARE);
    	}
        if (! isset(self::$_dbs[$this->_dbName])) {
            if (! $this->_dbName) {
                throw new Core_Exception_Fatal(get_class($this) . ' 没有定义 $_dbName，无法使用 Com_Dao');
            }
            self::$_dbs[$this->_dbName] = Com_DB::get($this->_dbName);
        }

        return self::$_dbs[$this->_dbName];
    }

    public function getBuilder()
    {
        if ($this->_sqlBuilder === null) {
            $this->_sqlBuilder = new Com_DB_SqlBuilder();
        }

        return $this->_sqlBuilder;
    }

    /**
     * 设置当前库名
     *
     * @return $this
     */
    public function setDbName($dbName)
    {
        $this->_dbName = $dbName;

        return $this;
    }

    /**
     * 设置当前表名
     *
     * @return $this
     */
    public function setTableName($tableName)
    {
        $this->_tableName = $tableName;

        return $this;
    }

    public function getTableName()
    {
        if (isset($this->_options['table'])) {
            return $this->_options['table'];
        }

        // 缺省根据类名获取表名
        // 例如：Dao_User_Index => user_index
        if (null === $this->_tableName) {
            $this->_tableName = strtolower(str_replace('Dao_', '', get_called_class()));
        }

        return $this->_tableName;
    }

    /**
     * 设置哈希库名
     *
     * @return $this
     */
    public function hashDb($hashKey)
    {
        $this->_dbName = Com_DB_Hash::dbName($this->_dbName, $hashKey);

        return $this;
    }

    /**
     * 设置哈希表名
     *
     * @return $this
     */
    public function hashTable($hashKey)
    {
        $this->_tableName = Com_DB_Hash::tableName($this->getTableName(), $hashKey);

        return $this;
    }

    public function reset()
    {
        $this->_options  = array();
        $this->_params   = array();
        $this->_isMaster = false;
        $this->_isCached = $this->_isDaoNeedCache;

        return $this;
    }

    public function bindParams($params)
    {
        $this->_params = (array) $params;

        return $this;
    }

    public function isMaster($bool = true)
    {
        $this->_isMaster = (bool) $bool;

        return $this;
    }

    public function isCached($bool)
    {
        $this->_isCached = (bool) $bool;

        return $this;
    }

    public function __call($method, $args)
    {
        // 链式操作
        static $selectMethods = array(
            'table'    => 1,
            'field'    => 1,
            'where'    => 1,
            'order'    => 1,
            'limit'    => 1,
            'join'     => 1,
            'having'   => 1,
            'group'    => 1,
            'lock'     => 1,
            'distinct' => 1,
        );

        // 允许接收多个参数的方法
        static $selectMethodMultiArgs = array(
            'limit' => 1,
        );

        // 存储链式操作
        if (isset($selectMethods[$method])) {
            $this->_options[$method] = isset($selectMethodMultiArgs[$method]) ? $args : (isset($args[0]) ? $args[0] : null);
            return $this;
        }

        // 读操作
        static $fetchMethods = array(
            'fetchAll'   => 1,
            'fetchAssoc' => 1,
            'fetchOne'   => 1,
            'fetchRow'   => 1,
            'fetchCol'   => 1,
            'fetchPairs' => 1,
        );

        // 执行读操作
        if (isset($fetchMethods[$method])) {
            return $this->_read($method);
        }

        // 写操作
        static $writeMethods = array(
            'insert'         => 1,
            'replace'        => 1,
            'update'         => 1,
            'delete'         => 1,
            'batchInsert'    => 1,
            'batchReplace'   => 1,
            'increment'      => 1,
            'incrementMax'   => 1,
            'decrement'      => 1,
            'decrementMin'   => 1,
            'insertOnDupate' => 1,
        );

        // 执行写操作
        if (isset($writeMethods[$method])) {

            if ($method == 'delete' && $args) {
                throw new Core_Exception_Fatal('Com_Dao::delete() 的条件参数必须使用 where() 等方法来设置');
            } elseif ($method == 'update' && count($args) > 1) {
                throw new Core_Exception_Fatal('Com_Dao::update() 的条件参数必须使用 where() 等方法来设置');
            }

            return $this->_write($method, $args);
        }

        throw new Core_Exception_Fatal('Call to undefined method Com_Dao::' . $method);
    }

    protected function _read($fetchMethod)
    {
        $sql = $this->table($this->getTableName())
                    ->getBuilder()->setOptions($this->_options)->buildSelectSql();
        $result = $this->db()->$fetchMethod($sql, $this->_params, $this->_isMaster);

        // 重置存储的选项
        $this->reset();

        return $result;
    }

    protected function _write($method, $args = null)
    {
        $sqlBuilder = $this->table($this->getTableName())
                           ->getBuilder()->setOptions($this->_options);

        $buildMethod = 'build' . ucfirst($method) .'Sql';
        $sql = call_user_func_array(array($sqlBuilder, $buildMethod), $args);

        if (is_array($sql)) {
            $this->_params = array_merge($sql['params'], $this->_params);
            $sql           = $sql['sql'];
        }
        $result = $this->db()->query($sql, $this->_params, true);

        // 重置存储的选项
        $this->reset();

        return $result;
    }

    public function fetchCount()
    {
        return $this->field('COUNT(0)')->fetchOne() ?: 0;
    }

    public function fetchSum($field)
    {
        return $this->field('SUM(`' . $field . '`)')->fetchOne() ?: 0;
    }

    // 本方法不支持复合主键
    public function fetchPk()
    {
        return $this->field($this->_pk)->fetchOne();
    }

    // 本方法不支持复合主键
    public function fetchPks()
    {
        return $this->field($this->_pk)->fetchCol();
    }

    public function fetchNames()
    {
        return $this->field($this->_nameField)->fetchCol();
    }

    protected $_rowDatas = array();

    /**
     * 根据主键 fetchRow
     *
     * @param mixed $pk
     * @return array
     */
    public function get($pk)
    {
        // 禁用缓存时
        if (! $this->_isCached) {
            return $this->where($this->_getPkCondition($pk))->fetchRow();
        }

        $cacheKey = $this->_getRowCacheKey($pk);

        // 保证相同的静态记录只读取一遍
        if (isset($this->_rowDatas[$cacheKey])) {
            return $this->_rowDatas[$cacheKey];
        }

        if (! $row = $this->_cache->get($cacheKey)) {
            if ($row = $this->where($this->_getPkCondition($pk))->fetchRow()) {
                $this->_cache->set($cacheKey, $row, $this->_cacheTTL);
                $this->_rowDatas[$cacheKey] = $row;
            }
        }

        return $row;
    }

    // 批量获取记录
    // 注：本方法不支持复合主键
    public function getMulti(array $pks)
    {
        // 禁用缓存时
        if (! $this->_isCached) {
            return $this->where(array($this->_pk => array('IN', $pks)))
                        ->fetchAssoc();
        }

        $return = $cacheKeys = array();

        // 整理缓存key名称
        foreach ($pks as $pk) {
            $cacheKeys[$pk] = $this->_getRowCacheKey($pk);
        }

        // 批量读取
        $rows = $this->_cache->getMulti($cacheKeys);

        // 结果匹配和补充处理
        foreach ($pks as $pk) {
            $cacheKey = $cacheKeys[$pk];
            if (! isset($rows[$cacheKey]) || empty($rows[$cacheKey])) {
                $return[$pk] = $this->get($pk);
            }
            else {
                $return[$pk] = $rows[$cacheKey];
            }
        }

        return $return;
    }

    public function getField($pk, $field)
    {
        // 禁用缓存时
        if (! $this->_isCached) {
            return $this->field($field)
                        ->where($this->_getPkCondition($pk))
                        ->fetchOne();
        }

        $data = $this->get($pk);
        return isset($data[$field]) ? $data[$field] : null;
    }

    public function name($pk)
    {
        return $this->getField($pk, $this->_nameField);
    }

    // 批量获取名称
    // 注：本方法不支持复合主键
    public function names(array $pks)
    {
        // 禁用缓存时
        if (! $this->_isCached) {
            return $this->field(array($this->_pk, $this->_nameField))
                        ->where(array($this->_pk => array('IN', $pks)))
                        ->fetchPairs();
        }

        if (! $list = $this->getMulti($pks)) {
            return $list;
        }

        return Helper_Array::fetchPairs($list, $this->_pk, $this->_nameField);
    }

    public function incrByPk($pk, $field, $step = 1)
    {
        if (! $result = $this->where($this->_getPkCondition($pk))->increment($field, $step)) {
            return $result;
        }

        // 清理缓存
        if ($this->_isCached) {
            $this->_deleteRowCache($pk);
        }

        return $result;
    }

    public function decrByPk($pk, $field, $step)
    {
        return $this->incrByPk($pk, $field, -$step);
    }

    public function replaceByPk(array $setArr, $pk)
    {
        if (! $setArr) {
            return false;
        }

        if (! $result = $this->replace($setArr)) {
            return $result;
        }

        // 清理缓存
        if ($this->_isCached) {
            $this->_deleteRowCache($pk);
        }

        return $result;
    }

    /**
     * 更新（根据主键）
     *
     * @param array $setArr
     * @param mixed $pk
     * @param array $extraWhere 格外的WHERE条件
     * @return bool/int
     */
    public function updateByPk(array $setArr, $pk, array $extraWhere = array())
    {
        if (! $setArr) {
            return false;
        }

        $where = $this->_getPkCondition($pk);

        if ($extraWhere) {
            $where = array_merge($where, $extraWhere);
        }

        if (! $result = $this->where($where)->update($setArr)) {
            return $result;
        }

        // 清理缓存
        if ($this->_isCached) {
            $this->_deleteRowCache($pk);
        }

        return $result;
    }

    /**
     * 批量更新（根据主键）
     * 注：本方法不支持复合主键
     *
     * @param array $setArr
     * @param array $pks
     * @param array $extraWhere 格外的WHERE条件
     * @return bool/int
     */
    public function updateByPks(array $setArr, array $pks, array $extraWhere = array())
    {
        $where = array($this->_pk => array('IN', $pks));

        if ($extraWhere) {
            $where = array_merge($where, $extraWhere);
        }

        if (! $result = $this->where($where)->update($setArr)) {
            return $result;
        }

        // 清理缓存
        if ($this->_isCached) {
            foreach ($pks as $pk) {
                $this->_deleteRowCache($pk);
            }
        }

        return $result;
    }

    /**
     * 删除（根据主键）
     *
     * @param mixed $pk
     * @param array $extraWhere 格外的WHERE条件
     * @return bool
     */
    public function deleteByPk($pk, array $extraWhere = array())
    {
        $where = $this->_getPkCondition($pk);

        if ($extraWhere) {
            $where = array_merge($where, $extraWhere);
        }

        if (! $result = $this->where($where)->delete()) {
            return $result;
        }

        // 清理缓存
        if ($this->_isCached) {
            $this->_deleteRowCache($pk);
        }

        return $result;
    }

    /**
     * 批量删除（根据主键）
     * 注：本方法不支持复合主键
     *
     * @param array $pks
     * @param array $extraWhere 格外的WHERE条件
     * @return bool
     */
    public function deleteByPks(array $pks, array $extraWhere = array())
    {
        $where = array($this->_pk => array('IN', $pks));

        if ($extraWhere) {
            $where = array_merge($where, $extraWhere);
        }

        if (! $result = $this->where($where)->delete()) {
            return $result;
        }

        // 清理缓存
        if ($this->_isCached) {
            foreach ($pks as $pk) {
                $this->_deleteRowCache($pk);
            }
        }

        return $result;
    }

    public function touch($pk, $return = true)
    {
        // 需要返回值
        if ($return) {

            // 先查找
            if (! $row = $this->get($pk)) {
                // 找不到则新插入一条
                $this->insert($this->_getPkCondition($pk), false, true);
                // 重新读取一遍
                $row = $this->get($pk);
            }

            return $row;
        }

        // 不需要返回值
        else {

            // 先查找
            if (! $row = $this->get($pk)) {
                // 找不到则新插入一条
                $this->insert($this->_getPkCondition($pk), false, true);
            }
        }
    }

    // 获取单条记录缓存key
    protected function _getRowCacheKey($pk)
    {
        return self::buildRowCacheKey($this->_dbName, $this->_tableName, $pk);
    }

    protected function _deleteRowCache($pk)
    {
        $cacheKey = $this->_getRowCacheKey($pk);

        unset($this->_rowDatas[$cacheKey]);
        $this->_cache->delete($cacheKey);
    }

    protected function _getPkCondition($pk)
    {
        // 复合主键
        if (is_array($this->_pk)) {
            return array_combine($this->_pk, $pk);
        }
        // 单一主键
        else {
            return array($this->_pk => $pk);
        }
    }

    // 构造单条记录缓存key
    public static function buildRowCacheKey($dbName, $tableName, $pk)
    {
        if (is_array($pk)) {
            $pkString = implode(':', $pk);
        }
        else {
            $pkString = $pk;
        }

        return md5($dbName . ':' . $tableName . ':get:' . $pkString);
    }
}