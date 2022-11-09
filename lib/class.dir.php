<?php
/**
 * 目录，文件处理类
 *
 * @file                    class.encrypt.php
 * @author                  mashanling <msl-138@163.com>
 * @date                    2013-11-28 14:03:28
 * @lastmodify              $Date: 2014-02-20 11:06:28 +0800 (周四, 2014-02-20) $ $Author: msl $
 */

class Dir {
    /**
     * 转化路径中'\'为'/'
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-26 16:17:47
     *
     * @param   string  $path 待转化路径
     *
     * @return string 转换后的路径
     */
    static public function convertDS($path) {
        return str_replace('\\', '/', $path);
    }

    /**
     * 列目录及文件，不递归
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-26 18:20:23
     *
     * @param   string  $path       路径
     *
     * @return iterator 文件迭代器
     */
    static public function listDir($path) {
        $result     = array();
        $iterator   = new RecursiveDirectoryIterator($path);

        foreach($iterator as $spl_file_info) {

            if (0 !== strpos($spl_file_info->getFilename(), '.')) {
                $result[] = $spl_file_info;
            }
        }

        unset($iterator);

        return $result;
    }

    /**
     * 列目录，不递归
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-27 11:25:33
     *
     * @param   string  $path       路径
     *
     * @return array 文件迭代器数组
     */
    static public function listDirOnly($path) {
        $result     = array();
        $iterator   = new RecursiveDirectoryIterator($path);

        foreach($iterator as $spl_file_info) {

            if ($spl_file_info->isDir() && 0 !== strpos($spl_file_info->getFilename(), '.')) {
                $result[] = $spl_file_info;
            }
        }

        unset($iterator);

        return $result;
    }

    /**
     * 列目录，递归
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-27 11:31:47
     *
     * @param   string  $path           路径
     * @param   bool    $include_self   true包含当前路径
     *
     * @return iterator 文件迭代器
     */
    static public function listDirs($path, $include_self = false) {
        $result     = array();

        if ($include_self) {
            $result[] = new SplFileInfo($path);
        }

        $dir_arr    = self::listDirOnly($path);

        foreach($dir_arr as $dir) {
            $result[] = $dir;
            $result = array_merge($result, self::listDirs($dir, $include_self));
        }

        return $result;
    }//end listDirs

    /**
     * 列文件，递归
     *
     * @author          mashanling <msl-138@163.com>
     * @date            2013-11-26 16:33:52
     *
     * @param   string  $path       路径
     * @param   string  $extension  文件后缀名
     *
     * @return iterator|array 文件迭代器
     */
    static public function listFiles($path, $extension = null) {
        $iterator   = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        if ($extension) {
            $len    = strlen($extension);
            $array  = array();

            foreach($iterator as $key => $item) {

                if (substr($item, -$len) == $extension) {
                    $array[] = $item;
                }
            }

            unset($iterator);

            return $array;
        }

        return $iterator;
    }//end listFiles
}