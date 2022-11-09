<?php
if (!defined('INI_WEB')){die('访问拒绝');}

//$MS_config = require(ROOT_PATH.'config/db_mssql_config.php');


$msdb = new MSSql($MS_config);

/**
 +------------------------------------------------------------------------------
 * MSsql数据库驱动类
 +------------------------------------------------------------------------------
 * @subpackage  Db
 * @author    wuwenlong <qngb3@163.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class MSSql{
	
    protected $autoFree         = false;
    // 是否显示调试信息 如果启用会在日志文件记录sql语句
    public $debug             = false;
    // 是否使用永久连接
    protected $pconnect       = false;
    // 当前SQL指令
    protected $queryStr       = '';
    // 最后插入ID
    protected $lastInsID      = null;
    // 返回或者影响记录数
    protected $numRows        = 0;
    // 返回字段数
    protected $numCols        = 0;
    // 事务指令数
    protected $transTimes     = 0;
    // 错误信息
    protected $error          = '';
    // 数据库连接ID 支持多个连接
    protected $linkID         = array();
    // 当前连接ID
    protected $_linkID        =   null;
    // 当前查询ID
    protected $queryID        = null;
    // 是否已经连接数据库
    protected $connected      = false;
    // 数据库连接参数配置
    protected $config         = '';
    // SQL 执行时间记录
    protected $beginTime;
    // 数据库表达式
    protected $comparison     = array('eq'=>'=','neq'=>'!=','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE');
	
	
    protected $selectSql      = 'SELECT T1.* FROM (SELECT ROW_NUMBER() OVER (%ORDER%) AS ROW_NUMBER, thinkphp.* FROM (SELECT %DISTINCT% %FIELDS% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%) AS thinkphp) AS T1 WHERE %LIMIT%';
    /**
     +----------------------------------------------------------
     * 架构函数 读取数据库配置信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $config 数据库配置数组
     +----------------------------------------------------------
     */
    public function __construct($config=''){
        if ( !function_exists('mssql_connect') ) {
             throw new Exception("PHP服务器不支持连接MSSQL数据库");
		}
        if(!empty($config)) {
            $this->config	=	$config;
        }
    }

    /**
     +----------------------------------------------------------
     * 连接数据库方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function connect($config='',$linkNum=0) {

        if ( !isset($this->linkID[$linkNum]) ) {
            if(empty($config))	$config  =  $this->config;
            $conn = $this->pconnect ? 'mssql_pconnect':'mssql_connect';
          //  mssql_query("set names utf8");
          $conn->charPage =65001;
            // 处理不带端口号的socket连接情况
            if($config['hostport'] == ''){
            	$host = $config['hostname'];
            }elseif($config['hostport'] != ''){
            	$host = $config['hostname'].','.$config['hostport'];
            }
            //$host = $config['hostname'].($config['hostport']?",{$config['hostport']}":'');
            $this->linkID[$linkNum] = $conn( $host, $config['username'], $config['password']);

            if ( !$this->linkID[$linkNum] || (!empty($config['database'])  && !mssql_select_db($config['database'], $this->linkID[$linkNum])) ) {
                throw new Exception($this->error());
            }
            // 标记连接成功
            $this->connected =  true;
            //注销数据库安全信息
            unset($this->config);
        }
        return $this->linkID[$linkNum];
    }


  #============================================================================
  # 公共方法: 执行查询
  #----------------------------------------------------------------------------
	public function query($str) {
		if ( $str == '' ) return false;
        $this->initConnect(false);
        if ( !$this->_linkID ) return false;
        $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) $this->free();
        $this->queryID = mssql_query($str, $this->_linkID);
        $this->debug();
		return  $this->queryID;
	}

    /**
     * 释放查询结果
     * @access public
     */
    public function free() {
        mssql_free_result($this->queryID);
        $this->queryID = 0;
    }
	
    public function initConnect($fou){
		if ( !$this->connected ) $this->_linkID = $this->connect();	
	}
	
    /**
     * 执行查询  返回数据集
     * @access public
     * @param string $str  sql指令
     * @return mixed
     */
    public function arrQuery($str) {
        $this->query($str);
        if ( false === $this->queryID ) {
            $this->error();
            return false;
        } else {
        	//print_r($this->queryID);
        	//exit;
            $this->numRows = mssql_num_rows($this->queryID);
            return $this->getAll();
        }
    }
    /**
     * 获得所有的查询数据
     * @access private
     * @return array
     * @throws ThinkExecption
     */
    private function getAll() {
        //返回数据集
        $result = array();
        if($this->numRows >0) {
            while($row = mssql_fetch_assoc($this->queryID))
                $result[]   =   $row;
        }
        return $result;
    }
	
	//返回一个字段的值
    function getOne($sql, $limited = false)
    {
        if ($limited == true){
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if ($res !== false){
            $row = mssql_fetch_row($res);

            if ($row !== false){
                return $row[0];
            }else{
                return '';
            }
        }else{
            return false;
        }
    }
	
	//返回一条记录。
	function selectInfo($str) {
		if ( $this->query($str) ) {
			$this->record = mssql_fetch_array($this->queryID);
			if ("" != $this->record){
				foreach($this->record as $key => $val) { 
					$this->record[$key] = $val; // 自动对 资料 语言格式进行转换
				}
				$this->record = varFilter($this->record);	// 取出的值进行安全过滤
				return $this->record;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	
    /**
     * 执行语句
     * @access public
     * @param string $str  sql指令
     * @return integer
     */
    public function getRows($str) {
        $this->query($str);
        if ( false === $this->queryID) {
            $this->error();
            return false;
        } else {
            $this->numRows = mssql_rows_affected($this->_linkID);
            $this->lastInsID = $this->mssql_insert_id();
            return $this->numRows;
        }
    }

    /**
     * 数据库调试 记录当前SQL
     * @access protected
     */
    protected function debug() {
        // 记录操作结束时间
        if ( $this->debug )    {
            $runtime    =   number_format(microtime(TRUE) - $this->beginTime, 6);
            Log::record(" RunTime:".$runtime."s SQL = ".$this->queryStr,Log::SQL);
        }
    }
    /**
     +----------------------------------------------------------
     * 用于获取最后插入的ID
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     */
    public function mssql_insert_id()
    {
        $query  =   "SELECT @@IDENTITY as last_insert_id";
        $result =   mssql_query($query, $this->_linkID);
        list($last_insert_id)   =   mssql_fetch_row($result);
        mssql_free_result($result);
        return $last_insert_id;
    }

    /**
     +----------------------------------------------------------
     * 启动事务
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function startTrans() {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        //数据rollback 支持
        if ($this->transTimes == 0) {
            mssql_query('BEGIN TRAN', $this->_linkID);
        }
        $this->transTimes++;
        return ;
    }

    /**
     +----------------------------------------------------------
     * 用于非自动提交状态下面的查询提交
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = mssql_query('COMMIT TRAN', $this->_linkID);
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
            }
        }
        return true;
    }

    /**
     +----------------------------------------------------------
     * 事务回滚
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = mssql_query('ROLLBACK TRAN', $this->_linkID);
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
            }
        }
        return true;
    }


    /**
     +----------------------------------------------------------
     * 取得数据表的字段信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    function getFields($tableName) {
        $result =   $this->query("SELECT   column_name,   data_type,   column_default,   is_nullable
        FROM    information_schema.tables AS t
        JOIN    information_schema.columns AS c
        ON  t.table_catalog = c.table_catalog
        AND t.table_schema  = c.table_schema
        AND t.table_name    = c.table_name
        WHERE   t.table_name = '$tableName'");
        $info   =   array();
        if($result) {
            foreach ($result as $key => $val) {
                $info[$val['column_name']] = array(
                    'name'    => $val['column_name'],
                    'type'    => $val['data_type'],
                    'notnull' => (bool) ($val['is_nullable'] === ''), // not null is empty, null is yes
                    'default' => $val['column_default'],
                    'primary' => false,
                    'autoinc' => false,
                );
            }
        }
        return $info;
    }

    /**
     +----------------------------------------------------------
     * 取得数据表的字段信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    function getTables($dbName='') {
        $result   =  $this->query("SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_TYPE = 'BASE TABLE'
            ");
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

	/**
     +----------------------------------------------------------
     * order分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $order
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseOrder($order) {
        return !empty($order)?  ' ORDER BY '.$order:' ORDER BY rand()';
    }

    /**
     +----------------------------------------------------------
     * limit
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function parseLimit($limit) {
		if(empty($limit)) $limit=1;
        $limit	=	explode(',',$limit);
        if(count($limit)>1)
            $limitStr	=	'(T1.ROW_NUMBER BETWEEN '.$limit[0].' + 1 AND '.$limit[0].' + '.$limit[1].')';
		else
            $limitStr = '(T1.ROW_NUMBER BETWEEN 1 AND '.$limit[0].")";
        return $limitStr;
    }

    /**
     +----------------------------------------------------------
     * 关闭数据库
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function close() {
        if (!empty($this->queryID))
            mssql_free_result($this->queryID);
        if ($this->_linkID && !mssql_close($this->_linkID)){
            throw_exception($this->error());
        }
        $this->_linkID = 0;
    }

    /**
     +----------------------------------------------------------
     * 数据库错误信息
     * 并显示当前的SQL语句
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function error() {
        $this->error = mssql_get_last_message();
        if($this->debug && '' != $this->queryStr){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        return $this->error;
    }

    /**
     +----------------------------------------------------------
     * SQL指令安全过滤
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $str  SQL指令
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function escape_string($str) {
        return addslashes($str);
    }

   /**
     +----------------------------------------------------------
     * 析构方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __destruct()
    {
        // 关闭连接
        $this->close();
    }
}//类定义结束
?>