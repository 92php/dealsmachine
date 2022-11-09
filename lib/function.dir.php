<?php
/**
 * function.dir.php     目录、文件处理函数库
 * 
 * @author              mashanling(msl-138@163.com)
 * @date                2011-12-13
 * @last modify         2011-12-13 by mashanling
 */

/**
 * 转化路径中 “\”为“ /”
 * 
 * @param string $path 路径
 * 
 * @return string 转换后的路径
 */
function dir_path($path) {
    $path = str_replace('\\', '/', $path);
    substr($path, -1) != '/' && ($path = $path . '/');
    return $path;
}

/**
 * 创建目录，可创建多级
 * 
 * @param string $path 路径
 * @param string $mode 权限
 * 
 * @return bool 创建成功，返回true，否则返回false 
 */
function create_dir($path, $mode = 0755) {
    $path = dir_path($path);
    
    if(is_dir($path)) {
        return true;
    }
    
    return mkdir($path, $mode, true);
}

/**
 * 拷贝目录及下面所有文件
 * 
 * @param string $from 原路径
 * @param string $to   目标路径
 * 
 * @return bool 如果原路径不存在，则返回false，否则返回true
 */
function copy_dir($from, $to) {
    $from = dir_path($from);
    $to   = dir_path($to);
    
    if (!is_dir($from)) {
        return false;
    }
    
    if ($from == $to) {
        return true;
    }
    
    !is_dir($to) && create_dir($to);
    
    $list = glob($from . '*');
    
    if (!empty($list)) {
        
        foreach($list as $v) {
            $path = $to . basename($v);
            
            if(is_dir($v)) {
                copy_dir($v, $path);
            }
            else {
                copy($v, $path);
                chmod($path, 0755);
            }
        }
    }
    
    return true;
}//end copy_dir

/**
 * 删除目录及目录下面的所有文件
 * 
 * @param string $dir 路径
 */
function delete_dir($dir) {
    
    if (!is_dir($dir)) {
        return false;
    }
    
    $dir  = dir_path($dir);
    $list = glob($dir . '*');
    
    foreach($list as $v) {
        is_dir($v) ? delete_dir($v) : unlink($v);
    }
    
    return rmdir($dir);
}

/**
 * 列出目录下所有文件
 * 
 * @param string $path      路径
 * @param bool   $recursive 是否递归，默认true
 * @param string $pattern   匹配模式
 * @param array  $list      增加的文件列表
 * 
 * @return array 文件列表
 */
function list_dir($path, $recursive = true, $pattern = '*', $list= array()) {
    $path  = dir_path($path);
    
    if (!is_dir($path)) {
        return false;
    }
    
	$files = glob($path . $pattern);
	
	foreach($files as $v) {
		$list[] = $v;
		is_dir($v) && $recursive && ($list = list_dir($v, $recursive, $pattern, $list));
	}
	
	return $list;
}
/**
 * 遍历目录及其子目录
 * 
 * @param string $path    路径
 * @param bool   $recursive 是否递归，默认true
 * @param string $pattern 匹配模式
 * 
 * @return array 文件列表
 */
function scand_dir($path, $recursive = true, $pattern = '*') {
    
    if (!is_dir($path)) {
        return false;
    }
    
    $path  = dir_path($path);
    $list  = array();
    $files = glob($path . $pattern);
    
    foreach ($files as $v) {
        
        if (is_dir($v) && $recursive) {
            $v = dir_path($v);
            $k = basename($v);
            $list[$k] = scand_dir($v, $recursive, $pattern);
        }
        else {
            $list[] = $v;
        }
    }
    
    return $list;
}

/**
 * 目录列表
 * 
 * @param string $dir       路径
 * @param bool   $recursive 是否递归，默认true
 * @param int    $parent_id 父id
 * @param array  $dirs      传入的目录
 * 
 * @param array 目录列表
 */
function dir_tree($dir, $recursive = true, $parent_id = 0, $dirs = array()) {
    global $id;
    
    $parent_id == 0 && ($id = 0);
    $list = glob($dir . '*');
    
    foreach($list as $v) {
        
        if (is_dir($v) && $recursive) {
            $id++;
            $dirs[$id] = array('id' => $id, 'parentid' => $parent_id, 'name' => basename($v), 'dir' => $v . '/');
            $dirs      = dir_tree($v . '/', $recursive, $id, $dirs);
        }
    }
    
    return $dirs;
}
?>