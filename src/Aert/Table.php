<?php namespace Aert;
/**
 * AertDb表模型 CRUD 包装器
 * 
 * @author 449211678@qq.com
 */
class Table
{
	/**
	 * 数据库链接节点名
	 * 
	 * 子类可以覆盖此属性
	 * 
	 * @var string
	 */
	protected $queryNode = '';

	/**
	 * 数据表名
	 * 
	 * 子类可以覆盖此属性
	 * 
	 * @var string
	 */
	protected $table;
	
	/**
	 * 返回  对象实例
	 * 
	 * @param string $table
	 * @param string $query_node
	 * 
	 * @return Table
	 */
	static function instance($table,$query_node='')
	{
		static $list = array();
		$table = trim($table);
		if ( empty($table) ) return null;
		
		if (empty($list[$table]))
		{
			$list[$table] = new self();
			$list[$table]->queryNode = $query_node;
			$list[$table]->table = $table;
		}
		return $list[$table];
	}
	
	/**
	 * 从表中检索符合条件的多条记录
	 *
	 * @param mixed $cond
	 * @param string $fields
	 * @param string $sort
	 * @param int|array $limit 数组的话遵循格式 ( offset,length ) 
	 * @param bool $calc_total 计算总个数 
	 * 
	 * @return array
	 */
	function getAll($cond=null, $fields='*', $sort=null, $limit=null, $calc_total=false)
	{
		return DbRepo::queryNode($this->queryNode)->select($this->table,$cond, $fields, $sort, $limit, $calc_total);
	}
	
	/**
     * 从表中检索符合条件的一条记录
     *
     * @param mixed $cond
     * @param string $fields
     * @param string $sort
     * 
     * @return array
     */
	function getOne($cond=null ,$fields='*', $sort=null)
	{
		return DbRepo::queryNode($this->queryNode)->selectRow($this->table,$cond, $fields, $sort);
	}
	
	/**
     * 统计符合条件的记录的总数
     *
     * @param mixed $cond
     * @param string|array $fields
     * @param boolean $distinct
     *
     * @return int
     */
	function count($cond=null, $fields='*', $distinct=false)
	{
		return DbRepo::queryNode($this->queryNode)->count($this->table,$cond, $fields, $distinct);
	}
	
	/**
     * 插入一条记录
     *
     * @param array $row
     * @param bool $pkval 是否获取插入的主键值
     *
     * @return mixed
     */
	function insert(array $row, $pkval=false)
	{
		return DbRepo::queryNode($this->queryNode)->insert($this->table, $row, $pkval);
	}
	
	/**
	 * 更新表中记录,返回表中被更新行数
	 *
	 * @param array $row
	 * @param mixed $cond 条件
	 * 
	 * @return int
	 */
	function update(array $row, $cond=null)
	{
		return DbRepo::queryNode($this->queryNode)->update($this->table, $row, $cond);
	}
	
	/**
	 * 删除 表中记录,返回表中被删除行数
	 * 
	 * @param mixed $cond
	 * 
	 * @return int
	 */
	function del($cond=null)
	{
		return DbRepo::queryNode($this->queryNode)->del($this->table, $cond);
	}
	
	/**
	 * 向表中 某字段的值做 "加"运算
	 *
	 * @param string $field
	 * @param int $incr
	 * @param mixed $cond
	 * 
	 * @return int
	 */
	function incrField($field, $incr=1, $cond=null)
	{
		return DbRepo::queryNode($this->queryNode)->incrField($this->table, $field, $incr, $cond);
	}
	
	/**
	 * 向表中 某字段的值做 "减"运算
	 *
	 * @param string $field
	 * @param int $incr
	 * @param mixed $cond
	 * 
	 * @return int
	 */
	function decrField($field, $decr=1, $cond=null)
	{
		return DbRepo::queryNode($this->queryNode)->incrField($this->table, $field, (-1 * $decr), $cond);
	}
		
}