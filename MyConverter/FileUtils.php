<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/7
 * Time: 16:38
 */

namespace MyConverter;

class FileUtils
{
    public static function getExistsDir($path) {
        $iCount = 0;
        while (!file_exists($path) && $iCount < 10) {
            try {
                mkdir($path, 0777);
            } catch(\Exception $ex) {
            }
            $iCount++;
        }
        return $path;
    }
}