<?php
/**
 * affiliate 统计
 *
 */

class affiliate_stat{
	private  $user_id;
	private $date_today ;
    private $tomorrow ;
    //echo $tomorrow;
    private $date_this_week;
    private $date_next_week ;
    //echo date('Y-m-d',$date_next_week);
    private $date_this_month ;
    private $date_last_month ;

	//var $input_
	/**
	 * 
	 *
	 * @param unknown_type $user_id
	 * @return affiliate_stat
	 */
	function affiliate_stat($user_id) {
	    $this->user_id = $user_id;
	    $this->date_today = date("Y-m-d");
	    $this->tomorrow = date('Y-m-d',strtotime('+1 day'));
	    //echo $tomorrow;
	    $this->date_this_week = strtotime("-1 week sunday", time());
	    $this->date_next_week = strtotime("0 week sunday", time());
	    //echo date('Y-m-d',$date_next_week);
	    $this->date_this_month = date("Y-m-1");
	    $this->date_last_month = date('Y-m-1',strtotime('-1 month'));		
	}
	/**
	 * 
	 */
/**
 * ip 统计
 *
 */
function ip_stat(){
	global $db;
	 $sql = "select count(distinct(ips)) as ip_count,user_id from ".WJ_IP." i,".WJ_LINK." l where l.id=i.from_linkid and user_id in($this->user_id)";
    

    
    $ip_stat['today_ip'] = $db->getOne($sql." and i.adddate between UNIX_TIMESTAMP('".$this->date_today."') and UNIX_TIMESTAMP('".$this->tomorrow."')");
    $ip_stat['this_week_ip'] = $db->getOne($sql." and i.adddate between $this->date_this_week and $this->date_next_week");
    $ip_stat['this_month_ip'] = $db->getOne($sql." and i.adddate >=UNIX_TIMESTAMP('".date("Y-m-1")."')");
    $ip_stat['last_month_ip'] = $db->getOne($sql." and i.adddate between UNIX_TIMESTAMP('".$this->date_last_month."') and UNIX_TIMESTAMP('".$this->date_this_month."')");
    return $ip_stat;
}

/**
 * ip 统计
 *
 */
function order_stat(){
	global $db;
	$sql = "SELECT count(*) as order_count,SUM(order_amount) as order_amount,SUM(order_amount)*u.com_rate as commission FROM ".ORDERINFO." o,".WJ_LINK." l,".USERS." u WHERE l.user_id=u.user_id  and o.wj_linkid = l.id  AND order_status > 0 and order_status < 9 and l.user_id='$this->user_id' ";
	
      
    $order_stat['today'] = $db->selectInfo($sql." and o.pay_time between UNIX_TIMESTAMP('".$this->date_today."') and UNIX_TIMESTAMP('".$this->tomorrow."')");
    $order_stat['this_week'] = $db->selectInfo($sql." and o.pay_time between $this->date_this_week and $this->date_next_week");
    $order_stat['this_month'] = $db->selectInfo($sql." and o.pay_time >=UNIX_TIMESTAMP('".date("Y-m-1")."')");
    $order_stat['last_month'] = $db->selectInfo($sql." and o.pay_time between UNIX_TIMESTAMP('".$this->date_last_month."') and UNIX_TIMESTAMP('".$this->date_this_month."')");
    foreach ($order_stat as $k => $v){
    	$order_stat[$k]['order_amount'] = number_format($order_stat[$k]['order_amount'],2);
    }
    return $order_stat;
}
}
?>