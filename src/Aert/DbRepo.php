<?php namespace Aert;
/**
 * Db 简化类
 * 
 * @author 449211678@qq.com
 */
class DbRepo
{
	
	private static $_ds_instances = array();

    /**
     * 为特定存储域选择匹配的存储服务实例
     *
     * @param string $domain
     *
     * @return Db_Query
     */
    static function queryNode($domain='')
    {
    	$name = empty($domain) ? 'default' : trim($domain);
        if (!isset(self::$_ds_instances[$name]))
        {
            $dbo = empty($domain) ? DB::connection() : DB::connection($domain);
            self::$_ds_instances[$name] = new Db_Query($dbo);
        }
        return self::$_ds_instances[$name];
    }

	/**
     * 返回数据库可以接受的日期格式
     *
     * @param int $timestamp
     * @return string
     */
    static function dbTimeStamp($timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }
    
	/**
	 * 打印输出结果
	 * 
	 * @param mixed $vars
	 * @param string $label
	 * @param bool $return
	 * 
	 * @return String
	 */
	static function dump($vars, $label = '', $return = false)
	{    
	    $content = "<pre>\n";
	    if ($label != '') {
	        $content .= "<strong>{$label} :</strong>\n";
	    }
	    $content .= htmlspecialchars(print_r($vars, true),ENT_COMPAT | ENT_IGNORE);
	    $content .= "\n</pre>\n";
	    
	    if ($return) { return $content; }
	    echo $content;
	}
    
}

/**
 * 查询接口
 */
class Db_Query
{
	
	/**
	 * Db_Actor 对象
	 * 
	 * @var Db_Actor
	 */
	private $actor;
	
	function __construct($dbo)
	{
		$this->dbo = $dbo;
		$this->ds = new Db_DataSource( $dbo->getPdo() );
		$this->actor = new Db_Actor($this->ds);
	}
	
	/**
	 * 返回数据源对象
	 * 
	 * @return Db_DataSource
	 */
	function getDataSource()
	{
		return $this->ds;
	}

	/**
	 * 返回Db操作对象
	 * 
	 * @return Db_Actor
	 */
	function getDbActor()
	{
		return $this->actor;
	}
	
    /**
     * 从表中检索符合条件的一条记录
     *
     * @param string $table
     * @param mixed $cond
     * @param string $fields
     * @param string $sort
     * 
     * @return array
     */
    function selectRow($table ,$cond=null ,$fields='*', $sort=null)
	{
		$cond = Db_SqlHelper::parseCond($this->ds,$cond);
		if ($cond) $cond = "WHERE {$cond}";
		if ($sort) $sort = "ORDER BY {$sort}";
		
		$qfields = Db_SqlHelper::qfields($fields,$table);
		
		return $this->actor->read(Db_Actor::MODE_READ_GETROW, array(
				"SELECT {$qfields} FROM {$table} {$cond} {$sort}"
			));
	}
    
	/**
	 * 从表中检索符合条件的多条记录
	 *
	 * @param string $table
	 * @param mixed $cond
	 * @param string $fields
	 * @param string $sort
	 * @param int|array $limit 数组的话遵循格式 ( offset,length ) 
	 * @param bool $calc_total 计算总个数 
	 * 
	 * @return array
	 */
	function select($table, $cond=null, $fields='*', $sort=null, $limit=null, $calc_total=false)
	{		
		$cond = Db_SqlHelper::parseCond($this->ds,$cond);
		if ($cond) $cond = "WHERE {$cond}";
		if ($sort) $sort = "ORDER BY {$sort}";
		
		$qfields = Db_SqlHelper::qfields($fields,$table);
		$table = Db_SqlHelper::qtable($table);
		
		return $this->actor->read(Db_Actor::MODE_READ_GETALL, array(
				"SELECT {$qfields} FROM {$table} {$cond} {$sort}",
				empty($limit) ? false : $limit,
				$calc_total
			));
	}
	
    /**
     * 统计符合条件的记录的总数
     *
     * @param string $table
     * @param mixed $cond
     * @param string|array $fields
     * @param boolean $distinct
     *
     * @return int
     */
    function count($table, $cond=null, $fields='*', $distinct=false)
	{
    	if ($distinct) $distinct = 'DISTINCT ';
    		
    	$cond = Db_SqlHelper::parseCond($this->ds,$cond);
    	if ($cond) $cond = "WHERE {$cond}";
		
    	if (is_null($fields) || trim($fields) == '*') {
            $fields = '*';
        } 
        else {
            $fields = Db_SqlHelper::qfields($fields,$table);
        }
        
        $table = Db_SqlHelper::qtable($table);
        
        return (int) $this->actor->read(Db_Actor::MODE_READ_GETONE, array(
				"SELECT COUNT({$distinct}{$fields}) FROM {$table} {$cond}"
			));
    }

    /**
     * 插入一条记录
     *
     * @param string $table
     * @param array $row
     * @param bool $pkval 是否获取插入的主键值
     *
     * @return mixed
     */
    function insert($table, array $row, $pkval=false)
	{
		list($holders, $values) = Db_SqlHelper::placeholder($row);
        $holders = implode(',', $holders);
        
        $fields = Db_SqlHelper::qfields(array_keys($values));        
        $table = Db_SqlHelper::qtable($table);
		
        return $this->actor->write(Db_Actor::MODE_WRITE_INSERT, array(
				Db_SqlHelper::bind($this->ds, "INSERT INTO {$table} ({$fields}) VALUES ({$holders})", $row),
				$pkval
			));
	}

    /**
	 * 更新表中记录
	 *
	 * @param string $table
	 * @param array $row
	 * @param mixed $cond 条件
	 * 
	 * @return int
	 */
	function update($table, array $row, $cond=null)
	{
		if ( empty($row) ) return false;
					
        list($pairs, $values) = Db_SqlHelper::placeholderPair($row);
        $pairs = implode(',', $pairs);
        
        $table = Db_SqlHelper::qtable($table);
		
        $sql = Db_SqlHelper::bind($this->ds, "UPDATE {$table} SET {$pairs}", $row);
        
        $cond = Db_SqlHelper::parseCond($this->ds, $cond);
        if ($cond) $sql .= " WHERE {$cond}";
        
        return $this->actor->write(Db_Actor::MODE_WRITE_UPDATE, array(
			 $sql
		));		
	}

    /**
	 * 删除 表中记录
	 * 
	 * @param string $table
	 * @param mixed $cond
	 * 
	 * @return int
	 */
	function del($table, $cond=null)
	{
		$cond = Db_SqlHelper::parseCond($this->ds, $cond);
		$table = Db_SqlHelper::qtable($table);
		
		$sql = "DELETE FROM {$table} " . (empty($cond) ? '' : "WHERE {$cond}");
		
		return $this->actor->write(Db_Actor::MODE_WRITE_DELETE, array(
				$sql
			));
	}
		
	/**
	 * 向表中 某字段的值做 "加"运算
	 *
	 * @param string $table
	 * @param string $field
	 * @param int $incr
	 * @param mixed $cond
	 * 
	 * @return int
	 */
    function incrField($table, $field, $incr=1, $cond=null)
	{
		if ( empty($field) ) return false;
		
		$table = Db_SqlHelper::qtable($table);
		$field = Db_SqlHelper::qfield($field);
		
		$sql = "UPDATE {$table} SET {$field}={$field}+{$incr}";
		
		$cond = Db_SqlHelper::parseCond($this->ds, $cond);
        if ($cond) $sql .= " WHERE {$cond}";
        
        return $this->actor->write(Db_Actor::MODE_WRITE_UPDATE, array(
			 $sql
		));	
	}
	
}

class Db_Actor
{
		
	/**
	 * 读 记录集
	 */
	const MODE_READ_GETALL = 1;
	
	/**
	 * 读 第一条记录
	 */
	const MODE_READ_GETROW = 2;
	
	/**
	 * 读 第一条记录的第一个字段
	 */
	const MODE_READ_GETONE = 3;
	
	/**
	 * 读 记录集的指定列
	 */
	const MODE_READ_GETCOL = 4;
	
	/**
	 * 读 记录集的总个数
	 */
	const MODE_READ_ALLCOUNT = 5;
	
	/**
	 * 写 (插入) 操作
	 */
	const MODE_WRITE_INSERT = 11;
	
	/**
	 * 写 (更新) 操作
	 */
	const MODE_WRITE_UPDATE = 12;
	
	/**
	 * 写 (删除) 操作
	 */
	const MODE_WRITE_DELETE = 13;
	
	/**
	 * 数据源对象
	 * 
	 * @var Db_DataSource
	 */
	private $ds;
	
	function __construct(Db_DataSource $ds)
	{
		$this->ds = $ds;
	}	
			
	/**
	 * 执行 读 操作
	 * 
	 * @param string $mode 模式 [MODE_READ_GETALL,MODE_READ_GETROW,MODE_READ_GETONE,MODE_READ_GETCOL]
	 * @param mixed $arguments 参数[不同模式参数不同,缺省为sql字符串]
	 * @param callback $cb 查询记录集的回调处理函数
	 * 
	 * @return mixed
	 */
	function read($mode, $arguments, $cb=NULL){
		
		$arguments = (array) $arguments;
		
		$sql = array_shift($arguments);// 缺省第一个参数是sql字符串
		
		switch ($mode){
			case self::MODE_READ_GETALL: // array(sql,limit,counter),如果sql里面带了limit则不能使用counter
				$limit = array_shift($arguments);
				$counter = array_shift($arguments);
				
				$result = null;
				if ($counter)
				{
					$result = array(
						'total' => $this->ds->count($sql),
					);
				}
				if ($limit) $sql = $this->ds->sql_limit($sql, $limit);
				
				if (is_array($result))
				{
					$result['rows'] = ($result['total'] == 0) ? array() : $this->ds->all($sql);
				}
				else
				{
					$result = $this->ds->all($sql);
				}
				break;
			case self::MODE_READ_GETCOL:// array(sql,col,limit,counter) col 下标从 0开始 为第一列
				$col = (int) array_shift($arguments);
				$limit = array_shift($arguments);
				$counter = array_shift($arguments);
				
				$result = null;
				if ($counter)
				{
					$result = array(
						'total' => $this->ds->count($sql),
					);
				}
				if ($limit) $sql = $this->ds->sql_limit($sql, $limit);
				if (is_array($result))
				{
					$result['rows'] = ($result['total'] == 0) ? array() : $this->ds->col($sql,$col);
				}
				else
				{
					$result = $this->ds->col($sql,$col);
				}
				break;
			case self::MODE_READ_GETROW:
				$result = $this->ds->row($sql);
				break;
			case self::MODE_READ_GETONE:
				$result = $this->ds->one($sql);
				break;			
			case self::MODE_READ_ALLCOUNT:
				$result = $this->ds->count($sql);
				break;
			default:
				throw Exception("无效[r]: {$mode}");
		}
		
		return (empty($cb) || !is_callable($cb)) ? $result : call_user_func_array($cb,array($result));
	}
	
	/**
	 * 执行 更新/删除 操作
	 * 
	 * @param string $mode 模式 [MODE_WRITE_INSERT,MODE_WRITE_UPDATE,MODE_WRITE_DELETE]
	 * @param mixed $arguments 参数[不同模式参数不同,缺省为sql字符串]
	 * @param callback $cb 查询结果集的回调处理函数
	 * 
	 * @return mixed
	 */
	function write($mode, $arguments, $cb=NULL){
				
		$arguments = (array) $arguments;		
		$sql = array_shift($arguments);// 缺省第一个参数是sql字符串
		
		$this->ds->execute($sql);
		
		switch ($mode){			
			case self::MODE_WRITE_INSERT: // 插入操作可选 得到主键标识
				$id = array_shift($arguments);
				$result = $id ? $this->ds->insert_id() : $this->ds->affected_rows();
				break;
			case self::MODE_WRITE_UPDATE:
			case self::MODE_WRITE_DELETE:
				$result = $this->ds->affected_rows();
				break;
			default:
				throw Exception("无效[w]: {$mode}");
		}
		
		return (empty($cb) || !is_callable($cb)) ? $result : call_user_func_array($cb,array($result));
	}	
	
}

/**
 * Db 数据源
 */
class Db_DataSource
{
	
	/**
     * @var int
     */
    private $query_count = 0;

    /**
     * @var PDO
     */
    private $db;
    
    /**
     * @var int
     */
    protected $affected_rows = 0;

    function __construct(PDO $db)
    {
    	$this->db = $db;
    }
    
    function begin()
    {
        $this->db->beginTransaction();
    }

    function commit()
    {
        $this->db->commit();
    }

    function rollback()
    {
        $this->db->rollBack();
    }
	
    function qstr($value)
	{
		if (is_int($value) || is_float($value)) { return $value; }
		if (is_bool($value)) { return $value ? 1 : 0; }
		if (is_null($value)) { return 'NULL'; }
		
		return $this->db->quote($value);
	}
	
	function insert_id()
	{
		return $this->db->lastInsertId();
	}
	
    function affected_rows()
    {
    	return $this->affected_rows;
    }
	
    function execute($sql, array $args = null)
    {
    	$this->affected_rows = 0;
    	
       	if (!empty($args)) {
       		$sql = Db_SqlHelper::bind($this, $sql, $args);
		}

        Log::debug($sql);
        $result = $this->db->exec($sql);
        
        $this->query_count++;

        if ($result === false)
        {
        	$error = $this->db->errorInfo();
	    	throw new Exception("QUERY_FAILED: {$error[0]}/{$error[1]}, {$error[2]}\n{$sql}");
        }        
        $this->affected_rows = $result;    	
    }
    
    /**
     * @return PDOStatement
     */
    private function query($sql)
    {    	
    	Log::debug($sql);
    	$statement = $this->db->query($sql);
        $this->query_count++;
        
        if ($statement !== false) return $statement;
        
    	$error = $this->db->errorInfo();
	    throw new Exception("QUERY_FAILED: {$error[0]}/{$error[1]}, {$error[2]}\n{$sql}");
    }
    	
	function all($sql)
    {
        $res = $this->query($sql);
        /* @var $res PDOStatement */
        
        $rowset = $res->fetchAll(PDO::FETCH_ASSOC);
        $res = null;
        return $rowset;
    }
	
    function one($sql)
    {
    	$res = $this->query($sql);
        /* @var $res PDOStatement */
    	
    	$val = $res->fetchColumn(0);
    	$res = null;
        return $val;
    }
    
    function row($sql)
    {
    	$res = $this->query($sql);
        /* @var $res PDOStatement */
    	
    	$row = $res->fetch(PDO::FETCH_ASSOC);
    	
        $res = null;
        return $row;
    }
	
    function col($sql, $col=0)
    {
        $res = $this->query($sql);
        /* @var $res PDOStatement */
        
        $rowset = $res->fetchAll(PDO::FETCH_COLUMN,$col);
        $res = null;
        
        return $rowset;
    }

	function count($sql)
	{
		return (int) $this->one("SELECT COUNT(*) FROM ( $sql ) AS t");
	}
	
	function sql_limit($sql, $limit)
	{
		if (empty($limit)) return $sql;
				
		if (is_array($limit))
		{
			list($skip, $l) = $limit;
	        $skip = intval($skip);
          	$limit = intval($l);
	    }
	    else
	    {
	      	$skip = 0;
	       	$limit = intval($limit);
	    }
		
	    return "{$sql} LIMIT {$skip}, {$limit}";
	}
		
	/**
	 * 生成sql条件
	 * 
	 * @param mixed $cond
	 * @param bool $dash
	 * 
	 * @return string
	 */
	function sql_cond($cond, $dash=false)
	{
		return Db_SqlHelper::parseCond($this, $cond, $dash);
	}
	
}

class Db_SqlHelper
{
	
	/**
     * 根据 SQL 语句和提供的参数数组，生成最终的 SQL 语句
     *
     * @param Db_DataSource $ds
     * @param string $sql
     * @param array $inputarr
     *
     * @return string
     */
	static function bind(Db_DataSource $ds, $sql, array $inputarr)
	{
		$arr = explode('?', $sql);
        $sql = array_shift($arr);
        foreach ($inputarr as $value) {
            if (isset($arr[0])) {
                $sql .= $ds->qstr($value) . array_shift($arr);
            }
        }
        return $sql;
	}
	
	/**
	 * 解析查询条件
	 * 
	 * @param Db_DataSource $ds
	 * @param mixed $cond
	 * @param bool $dash 是否使用括号将返回的条件包括起来
	 *
	 * @return string
	 */
	static function parseCond(Db_DataSource $ds, $cond, $dash=false)
	{		
		if (empty($cond)) return '';
 		
		// 如果是字符串，则假定为自定义条件
        if (is_string($cond)) return $cond;
	
        // 如果不是数组，说明提供的查询条件有误
        if (!is_array($cond)) return '';
        
        static $equalIN = array('=','IN','NOT IN');
        static $betweenAnd = array('BETWEEN_AND','NOT_BETWEEN_AND');
        
 		$where = '';$expr = '';
 		
 		/**
         * 不过何种条件形式，一律为  字段名 => (值, 操作, 连接运算符, 值是否是SQL命令) 的形式
         */
 		foreach ($cond as $field => $d) {
 			
 			$expr = 'AND';
            
 			if (!is_string($field)) {
 				continue;
 			}
 			if (!is_array($d)) {
                // 字段名 => 值
            	$d = array($d);
            }
            reset($d);
            // 第一个元素是值
 			if (!isset($d[1])) { $d[1] = '='; }
            if (!isset($d[2])) { $d[2] = $expr; }
            if (!isset($d[3])) { $d[3] = false; }
			
            list($value, $op, $expr, $isCommand) = $d;
            
            $op = strtoupper(trim($op));            
            $expr = strtoupper(trim($expr));
            
            if (is_array($value)){
 				
 				do {
 					if (in_array($op, $equalIN)){
 						if ($op == '=') $op = 'IN';
 						$value = '(' . implode(',',array_map(array($ds, 'qstr'),$value)) . ')';
 						break;
 					} 					
 					
	 				if (in_array($op, $betweenAnd)){	 					
	 					$between = array_shift($value);
	 					
	 					$and = array_shift($value);
	 					$value = sprintf('BETWEEN %s AND %s',$ds->qstr($between),$ds->qstr($and));
	 					$op = 'NOT_BETWEEN_AND' == $op ? 'NOT' : '';// 此处已经串在 $value 中了
	 					break;
	 				}
 					
	 				// 一个字段对应 多组条件 的实现,比如 a > 15 OR a < 5 and a != 32
	 				// 'a' => array(  array( array(15,'>','OR'),array(5,'<','AND'), array(32,'!=') ) , 'FIELD_GROUP')
 					if ($op == 'FIELD_GROUP'){
 						$kv = array();
 						foreach($value as $k => $v){
 							$kv[":+{$k}+:"] = $v;
 						}
 						$value = self::parseCond($ds,$kv,true);
 						
 						foreach(array_keys($kv) as $k){
 							$value = str_ireplace($k,$field,$value);
 						}
 						
 						$field = $op = '';// 此处已经串在 $value 中了
	 					break;
 					}
 					
 				} while(false);
 				
 				$isCommand = true;
 			}
 			
 			if (!$isCommand) {
				$value = $ds->qstr($value);
			}
			$where .= "{$field} {$op} {$value} {$expr} ";
 		}
 		
        $where = substr($where, 0, - (strlen($expr) + 2));
        return $dash ? "({$where})" : $where;
	}
		
	static function qtable($table)
	{
		return "`{$table}`";
	}
	
	static function qfield($fieldName, $table = null)
	{
		$fieldName = ($fieldName == '*') ? '*' : "`{$fieldName}`";
		return $table != '' ? self::qtable($table) . '.' . $fieldName : $fieldName;
	}
		
    static function qfields($fields, $table = null, $returnArray = false)
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
            $fields = array_map('trim', $fields);
        }
        $return = array();
        foreach ($fields as $fieldName) {
            $return[] = self::qfield($fieldName, $table);
        }
       
        return $returnArray ? $return : implode(', ', $return);
    }
	
    static function placeholder(& $inputarr, $fields = null)
    {
        $holders = array();
        $values = array();
        if (is_array($fields)) {
            $fields = array_change_key_case(array_flip($fields), CASE_LOWER);
            foreach (array_keys($inputarr) as $key) {
                if (!isset($fields[strtolower($key)])) { continue; }
                $holders[] = '?';
                $values[$key] =& $inputarr[$key];
            }
        } else {
            foreach (array_keys($inputarr) as $key) {
                $holders[] = '?';
                $values[$key] =& $inputarr[$key];
            }
        }
        return array($holders, $values);
    }
    
    static function placeholderPair(& $inputarr, $fields = null)
    {
        $pairs = array();
        $values = array();
        if (is_array($fields)) {
            $fields = array_change_key_case(array_flip($fields), CASE_LOWER);
            foreach (array_keys($inputarr) as $key) {
                if (!isset($fields[strtolower($key)])) { continue; }
                $qkey = self::qfield($key);
                $pairs[] = "{$qkey}=?";
                $values[$key] =& $inputarr[$key];
            }
        } else {
            foreach (array_keys($inputarr) as $key) {
                $qkey = self::qfield($key);
                $pairs[] = "{$qkey}=?";
                $values[$key] =& $inputarr[$key];
            }
        }
        return array($pairs, $values);
    }
    
}
