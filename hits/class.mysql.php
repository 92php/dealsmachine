<?
if (!defined('INI_WEB')){die('访问拒绝');}

class MySql  {

	var $Host     = ''; ## database system host
	var $Database = '';  // 'webtest';	 ## database name
	var $User     = '';	 ## database user
	var $Password = '';	 ## database password

  #============================================================================
  # 公共属性: 设置参数
  #----------------------------------------------------------------------------
	var $AutoFree    = true;		## true: 自动释放
	var $Debug       = false;		## true: 显示调试资讯
	var $HaltOnError = 'yes';	    ## "yes"   : 显示错误，中断执行
									## "no"    : 忽略错误，继续执行
									## "report": 显示错误，继续执行
	var $ReportError = true;       ## true: 报告详细错误寄信并给管理员。
	var $PconnectOn  = false;		## true: 使用 pconnect ，否则使用 connect

  #============================================================================
  # 公共属性: 查询结果阵列 和 当前行数
  #----------------------------------------------------------------------------
	var $record = array();
	var $Row;
	var $QueryStr = '';

  #============================================================================
  # 公共属性: 错误号码 和 错误资讯
  #----------------------------------------------------------------------------
	var $Errno = 0;
	var $Error = '';
	var $isHalt = 1;

  #============================================================================
  # 公共属性: 本数据库操作类的 资料资讯
  #----------------------------------------------------------------------------
	var $Type     = 'MySQL';
	var $Revision = '1.0';
    var $Company  = 'ahappydeal hits';
	//var $AdminMail= '441817694@qq.com';
	var $AdminMail= '979540037@qq.com';

  #============================================================================
  # 私有属性: 连接ID 查询ID
  #----------------------------------------------------------------------------
	var $LinkID  = 0;
	var $QueryID = 0;
  #============================================================================
  # 私有属性: 连接ID 查询ID
  #----------------------------------------------------------------------------
    var $insertLog = 0;  //0:不插入操作记录，1：插入操作记录。
  #============================================================================
  # 公共方法: 构造器
  #----------------------------------------------------------------------------
	function MySql($Host, $User, $Password, $Database) {
		$this->Host		= $Host;
		$this->User		= $User;
		$this->Password = $Password;
		$this->Database	= $Database;
		$this->connect();
	}

  #============================================================================
  # 公共方法: 一些琐碎的报告
  #----------------------------------------------------------------------------
	function getLinkID() {
		return $this->LinkID;
	}

	function getQueryID() {
		return $this->QueryID;
	}
  #============================================================================
  # 公共方法: 换数据库
  #----------------------------------------------------------------------------
	function select_database($db){
		@mysql_select_db($db,$this->LinkID) or $this->halt('select database error!');
	}
  #============================================================================
  # 公共方法: 连接数据库
  #----------------------------------------------------------------------------
	function connect() {
	  /*---------- 建立连接，选择数据库 ----------*/
		if ( $this->LinkID == 0 ) {
			// 建立连接
			if ( $this->PconnectOn ) {
				$this->LinkID = @mysql_pconnect($this->Host, $this->User, $this->Password);
			} else {
				$this->LinkID = @mysql_connect($this->Host, $this->User, $this->Password);
			}
			// 连接错误
			if ( $this->LinkID == 0 ) {
				if ( $this->Debug ) {
					$msg = "connect('$this->Host', '$this->User', '$this->Password') connect error!";
				} else {
					$msg = 'Sorry, due to line fault, temporarily unable to browse, we are dealing with.';
				}
				$this->halt($msg);
				return false;
			}
			// 选择数据库时错误
			if ( !@mysql_select_db($this->Database, $this->LinkID) ) {
				$this->halt("Can not open database '".$this->Database."'！");
				return false;
			}
			mysql_query("SET NAMES 'utf8'");
		}
		return $this->LinkID;
	}

  #============================================================================
  # 公共方法: 释放查询结果
  #----------------------------------------------------------------------------
	function free() {
		@mysql_free_result($this->QueryID);
		$this->QueryID = 0;
	}

  #============================================================================
  # 公共方法: 执行查询
  #----------------------------------------------------------------------------
	function query($str) {
		$this->Error = '';
		$this->Errno = 0;
		if ( $str == '' ) return false;

		if ( !$this->connect() ) return false;

	  /*------- 新查询，释放前次的查询结果 -------*/
		if ( $this->QueryID ) {
			$this->free();
		}

		// $str = varResume($str);	// 恢复被过滤的变量,还原真实的值

		$this->QueryStr = $str;

		$debugMsg = "Debug: statement";
		$debugMsg = $debugMsg;
		if ( $this->Debug ) printf($debugMsg." = %s<br>\n", $this->QueryStr);

		$time_start = microtime(true);

		$this->QueryID = @mysql_query($this->QueryStr, $this->LinkID);

		//$this->writeSlowquery($time_start);

		$this->Row   = 0;
		if ( !$this->QueryID ) {
			$this->halt("Query error:".$this->QueryStr);
			return false;
		} else {
			if($this->insertLog)$this->SqlLog($this->QueryStr);
			return $this->QueryID;
		}
	}

	function arrQuery($sql,$mType=MYSQL_ASSOC)//获取所有数据内容存入数组中
    {
		$this->query($sql);
        if (!is_resource($this->QueryID))
        {
            return false;
        }
        $this->get_all_data = array();
        while ($row = mysql_fetch_array($this->QueryID,$mType))
        {
            $this->get_all_data[] = $row;
        }
        return $this->get_all_data;
    }
  #============================================================================
  # 公共方法: 获得查询结果
  #----------------------------------------------------------------------------
	function nextRecord($mType=MYSQL_ASSOC) {
		if ( !$this->QueryID ) {
			$this->halt('Error: Query is invalid!');
			return false;
		}

		$this->record = @mysql_fetch_array($this->QueryID,$mType);
        if ("" != $this->record){
			foreach($this->record as $key => $val) {
				$this->record[$key] = $val; // 自动对 资料 语言格式进行转换
			}
			$this->record = varFilter($this->record);	// 取出的值进行安全过滤
		}

		$this->Row += 1;

		$stat = is_array($this->record);

		if ( !$stat && $this->AutoFree ) {
			$this->free();
		}
		return $stat;
	}

  #============================================================================
  # 公共方法: 取出行
  #----------------------------------------------------------------------------
    function fetchRow($query)
    {
		return mysql_fetch_assoc($query);
    }

  #============================================================================
  # 公共方法: 获得插入的ID
  #----------------------------------------------------------------------------
	function insertId(){
		if( $result = mysql_insert_id($this->LinkID) ) {
			return $result;
		} else {
			return false;
		}
	}  #============================================================================
  # 公共方法:得到表的字段名称，返回数组
  #----------------------------------------------------------------------------
    function getCol($sql){
        $res = $this->query($sql);
        if ($res !== false){
            $arr = array();
            while ($row = mysql_fetch_row($res)){
                $arr[] = $row[0];
            }
            return $arr;
        }else{
            return false;
        }
    }  #============================================================================
  # 公共方法:自动查入库操作
  #----------------------------------------------------------------------------
    function autoExecute($table, $field_values, $mode = 'INSERT', $where = '', $querymode = '')
    {
        $field_names = $this->getCol('DESC ' . $table);
        $sql = '';
        if ($mode == 'INSERT'){
            $fields = $values = array();
            foreach ($field_names AS $value){
                if (array_key_exists($value, $field_values) == true){
                    $fields[] = $value;
                    $values[] = "'" . $field_values[$value] . "'";
                }
            }
            if (!empty($fields)){
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        }else{
            $sets = array();
            foreach ($field_names AS $value){
                if (array_key_exists($value, $field_values) == true){
                    $sets[] = $value . " = '" . $field_values[$value] . "'";
                }
            }

            if (!empty($sets)){
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
            }
        }
        if ($sql){
            return $this->query($sql, $querymode);
        }else{
            return false;
        }
    }    function autoReplace($table, $field_values, $update_values, $where = '', $querymode = '')
    {
        $field_descs = $this->getAll('DESC ' . $table);

        $primary_keys = array();
        foreach ($field_descs AS $value)
        {
            $field_names[] = $value['Field'];
            if ($value['Key'] == 'PRI')
            {
                $primary_keys[] = $value['Field'];
            }
        }

        $fields = $values = array();
        foreach ($field_names AS $value)
        {
            if (array_key_exists($value, $field_values) == true)
            {
                $fields[] = $value;
                $values[] = "'" . $field_values[$value] . "'";
            }
        }

        $sets = array();
        foreach ($update_values AS $key => $value)
        {
            if (array_key_exists($key, $field_values) == true)
            {
                if (is_int($value) || is_float($value))
                {
                    $sets[] = $key . ' = ' . $key . ' + ' . $value;
                }
                else
                {
                    $sets[] = $key . " = '" . $value . "'";
                }
            }
        }

        $sql = '';
        if (empty($primary_keys))
        {
            if (!empty($fields))
            {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        }
        else
        {
                if (!empty($fields))
                {
                    $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                    if (!empty($sets))
                    {
                        $sql .=  'ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
                    }
                }
        }

        if ($sql)
        {
            return $this->query($sql, $querymode);
        }
        else
        {
            return false;
        }
    }

    function getAll($sql)
    {
        $res = $this->query($sql);
        if ($res !== false)
        {
            $arr = array();
            while ($row = mysql_fetch_assoc($res))
            {
                $arr[] = $row;
            }

            return $arr;
        }
        else
        {
            return false;
        }
    }
  #============================================================================
  # 公共方法: 缩略方法
  #----------------------------------------------------------------------------
	function insert($table, $field, $value) {
		$str = "insert into ".$table;
		if ( $field != "" ) $str .= "(".$field.")";
		$str .= " values(".$value.")";
		if ( $this->query($str) ) {
			return true;
		} else {
			return false;
		}
	}

	function replace($table, $field, $value) {
		$str = "replace into ".$table;
		if ( $field != "" ) $str .= "(".$field.")";
		$str .= " values(".$value.")";
		if ( $this->query($str) ) {
			return true;
		} else {
			return false;
		}
	}

	function select($table, $field="*", $condition="", $order="", $limit="") {
		$str = "select ".$field." from ".$table;
		if ( $condition != "" ) $str.=" where ".$condition;
		if ( $order != "" ) $str.=" order by ".$order;
		if ( $limit != "" ) $str.=" limit ".$limit;
		if ( $this->query($str) ) {
			return $this->arrQuery($str);
		} else {
			return false;
		}
	}

	//返回一个字段的值
    function getOne($sql, $limited = false)
    {
        if ($limited == true){
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if ($res !== false){
            $row = mysql_fetch_row($res);

            if ($row !== false){
                return $row[0];
            }else{
                return '';
            }
        }else{
            return false;
        }
    }

    //用于分页
    function selectLimit($sql, $num, $start = 0)
    {
        if ($start == 0){
            $sql .= ' LIMIT ' . $num;
        }else{
            $sql .= ' LIMIT ' . $start . ', ' . $num;
        }
       return $this->arrQuery($sql);
    }	function update($table, $value, $condition="") {
		$str = "update ".$table." set ".$value;
		if ( $condition != "" ) $str.=" where ".$condition;
		if ( $this->query($str) ) {
			return true;
		} else {
			return false;
		}
	}

	function delete($table, $condition="") {
		$str = "delete from ".$table;
		if ( $condition != "" ) $str.=" where ".$condition;
		if ( $this->query($str) ) {
			return true;
		} else {
			return false;
		}
	}

	function selectInfo($str) {
		if ( $this->query($str) ) {
			if ( $this->nextRecord() ) {
				return $this->record;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function count_info($table, $field="*", $condition="") {
		$strC = "";
		if ( $condition != "" ) $strC=" where ".$condition;
		$str = "select count(".$field.") as num from ".$table.$strC;
		$this->query($str);
		$this->nextRecord();
		return $this->record['num'];
	}

	function nextData() {
		if ( $this->nextRecord() ) {
			return $this->record;
		}else{
			return false;
		}
	}

  #============================================================================
  # 公共方法: 获得SQL语句执行后受影响的行数
  #----------------------------------------------------------------------------
	function affectedRows() {
		return @mysql_affected_rows($this->LinkID);
	}

	function numRows() {
		return @mysql_num_rows($this->QueryID);
	}

	function numFields() {
		return @mysql_num_fields($this->QueryID);
	}
	function fetchArray($mType=MYSQL_ASSOC){
		return @mysql_fetch_array($this->QueryID,$mType);
	}

  #============================================================================
  # 公共方法: 缩略方法
  #----------------------------------------------------------------------------
	function nr() {
		return $this->numRows();
	}

	function np() {
		print $this->numRows();
	}

	function r($name) {
		if ( isset($this->record[$name]) ) {
			return $this->record[$name];
		}
	}

	function p($name) {
		if ( isset($this->record[$name]) ) {
			print $this->record[$name];
		}
	}

  #============================================================================
  # 公共方法: 查找表
  #----------------------------------------------------------------------------
	function tableNames() {
		$this->connect();
		$h = @mysql_query("show tables", $this->LinkID);
		if ( $this->Debug ) printf("Debug: statement = %s<br>\n", "'show tables'");
		$i = 0;
		while ( $info = @mysql_fetch_row($h) ) {
			$return[$i]["table_name"]      = $info[0];
			$return[$i]["tablespace_name"] = $this->Database;
			$return[$i]["database"]        = $this->Database;
			$i++;
		}
		@mysql_free_result($h);
		return $return;
	}

  #============================================================================
  # 公共方法: 错误处理
  #----------------------------------------------------------------------------
	function halt($msg) {
		$this->Error = @mysql_error($this->LinkID);
		$this->Errno = @mysql_errno($this->LinkID);
		$this->haltmsg($msg);

		if ( $this->HaltOnError == 'no' ) return;

		if ( $this->HaltOnError != 'report' ) die(' Database system error, the current operation has been suspended.');
	}

	function haltmsg($msg) {
	    return;
	    require_once(LIB_PATH . 'class.function.php');

	    if (!function_exists('gmtime')) {
	        function gmtime() {
	            return time() - date('Z');
	        }
	    }

		if ( $this->ReportError ) {
			$_SERVER["HTTP_REFERER"] = empty($_SERVER["HTTP_REFERER"])?'':$_SERVER["HTTP_REFERER"];
			$mailTitle = " Database error :";
			$mailMessage = " On ".$this->Company." : $msg\n";
			$mailMessage.= "MySQL error is (MySQL return error message): ".$this->Error."\n";
			$mailMessage.= "MySQL error code is (Error number): ".$this->Errno."\n";
			$mailMessage.="(date): ".date("Y-m-d l H:i:s")."\n";
			$mailMessage.="(Visitors IP):".real_ip()." (url): http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."\n";
			$mailMessage.="(referer url): ".$_SERVER["HTTP_REFERER"]."\n";

			//$mailTitle   = autoGbBig5($mailTitle);		// 自动简繁转换，不涉及 UTF8
			//$mailMessage = autoGbBig5($mailMessage);	// 自动简繁转换，不涉及 UTF8
			//@mail ($this->AdminMail, $this->Company."-".$_SERVER["HTTP_HOST"].$mailTitle,$mailMessage);


/*					$CDONTS = new COM("CDONTS.NewMail");
					$CDONTS->BodyFormat = 1;
					$CDONTS->MailFormat = 0;
					//$CDONTS->SetLocaleIDs(65001);
					$CDONTS->From = '<server@everbuying.com>';
					$CDONTS->To = $this->AdminMail;
					$CDONTS->Subject = $this->Company."-".$_SERVER["HTTP_HOST"].$mailTitle;
					$CDONTS->Body =  $mailMessage;
					$CDONTS->Send();
					$CDONTS = NULL;
	*/
					$From_mail        = 'server@ahappydeal.com';
					require_once(ROOT_PATH.'/Rmail.php');
					$mail = new Rmail();
					$mail->setFrom(' <'.$From_mail.'>');
					$mail->setSubject($this->Company."-".$_SERVER["HTTP_HOST"].$mailTitle);
					$mail->setPriority('high');
					$mail->setHTML($mailMessage);
					$mail->setReceipt($From_mail);



	                $cache_file_path = ROOT_PATH .'/data-cache/mysqlerror.php' ;
					$ErrArr = include($cache_file_path);
					$ErrArr = is_array($ErrArr)?$ErrArr:array();

					$curError['errortime']  =  gmtime();
					$curError['errorcont']  =  $mailTitle.$mailMessage;

					if (empty($ErrArr[$this->Errno])){
						$ErrArr[$this->Errno] = $curError;
						$content = "<?php\r\n";
						$content .= " return " . var_export($ErrArr, true) . ";\r\n";
						$content .= '?>';
						file_put_contents($cache_file_path, $content, LOCK_EX);
						$mail->send(array(WEB_ADMIN_EMAIL));
					}else{
						if(gmtime() - $ErrArr[$this->Errno]['errortime'] > 1800){
							//更改时间重写文件
							$ErrArr[$this->Errno]['errortime']  =  gmtime();
							$ErrArr[$this->Errno]['errorcont']  =  $mailTitle.$mailMessage;
							$content = "<?php\r\n";
							$content .= " return " . var_export($ErrArr, true) . ";\r\n";
							$content .= '?>';
							file_put_contents($cache_file_path, $content, LOCK_EX);
							$mail->send(array($this->AdminMail));
						}
					}




		} else {
			$message  = "<b>Database error:</b> ".$msg."<br>\n";
			$message .= "<b>MySQL Error</b>: ".$this->Errno." (".$this->Error.")<br>\n";
			echo $message;
		}
	}

	/**
	 * 分析SQL语句，得到SQL操作，表名，SQL语句
	 *
	 * @param string $sql_item
	 * @return  array $tableArray[0]:select|insert|update|delete, 1:tablename,2:sql
	 */
	function parseSql($sql_item){
		$sql_string = strtolower(trim($sql_item));
		$tableArray = array();
		$tableAry = array();
		preg_match("/^(\w*)/i",$sql_string,$tableArray);
		$tableArray[2] = $sql_item;
		switch($tableArray[0]){
			case "select":
				preg_match("/^(\w*).*from\s*(\w*)/i",$sql_string,$tableAry);
				$tableArray[1] = $tableAry[2];
				break;
			case "update":
				preg_match("/^(\w*).*?(\w*)\s*set/i",$sql_string,$tableAry);
				$tableArray[1] = $tableAry[2];
				break;
			case "insert":
				preg_match("/^(\w*).*?into\s*(\w*)/i",$sql_string,$tableAry);
				$tableArray[1] = $tableAry[2];
				break;
			case "delete":
				preg_match("/^(\w*).*?from\s*(\w*)/i",$sql_string,$tableAry);
				$tableArray[1] = $tableAry[2];
				break;
			default:
				$tableArray[1] = '';
		}
		return $tableArray;
	}

	function SqlLog($sql_item){
		global $webuser;
		if($webuser!=""){
			$tableArray = $this->parseSql($sql_item);
			if($tableArray[0] =='update' || $tableArray[0] =='delete'){
				$old_db=$this->Database;
				$this->select_database(WEB_LOG);
				$tbl_name=PRE_USER.date('Ym');
				$sql="CREATE TABLE if not exists `".$tbl_name."` (
	 					`id` int(11) NOT NULL auto_increment,
	  					`operate` varchar(10) NOT NULL,
	  					`tbl_name` varchar(50) NOT NULL,
	  					`sqlstr` text NOT NULL,
	  					`user_name` varchar(20) NOT NULL,
	  					`post_time` int(11) NOT NULL,
	  					PRIMARY KEY  (`id`)
						) ENGINE=MyISAM ;";
				$this->query($sql);
				$sql="insert into `".$tbl_name."`(operate,tbl_name,sqlstr,admin_user,post_time)";
				$sql.=" values('".$tableArray[0]."','".$tableArray[1]."','".mysql_escape_string($tableArray[2])."','$webuser',".time().")";
				$this->query($sql);
				$this->select_database($old_db);
			}
		}
	}

    function close() {
	    return mysql_close($this->LinkID);
	}

	/**
	 * 记录慢查询
	 *
	 * @author       mashanling(msl-138@163.com)
	 * @date         2012-10-15 14:35:24
	 * @last modify  2012-10-16 09:13:33 by mashanling
	 *
	 * @param float $time_start 开始时间
	 *
	 * @return void 无返回值
	 */
	private function writeSlowquery($time_start) {
	    $used_time = round(microtime(true) - $time_start, 6);

	    if ($used_time < 2) {
	        return;
	    }

	    $ip       = function_exists('real_ip') ? '[' . real_ip() . ']' : '';
        $format   = 'H:i:s: ';
        $log      = function_exists('local_date') ? local_date($format) : date($format, time() - date('Z') + 28800);
        $log      = $log . $ip . ' ' . $this->QueryStr;
        $log     .= "(<span style=\"color: red\">{$used_time}</span>)";
        $log     .= ' ' . $_SERVER['REQUEST_METHOD'] . ' <strong>' . $_SERVER['REQUEST_URI'] . '</strong>';
        $dir      = CRONTAB_LOG_PATH . (function_exists('local_date') ? local_date('Y/md/') : date('Y/md/', time() - date('Z') + 28800));

        !is_dir($dir) && mkdir($dir, 0755, true);

        file_put_contents($dir . 'slowquery.log', $log . PHP_EOL, FILE_APPEND);
	}
}