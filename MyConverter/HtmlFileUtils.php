<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/7
 * Time: 16:38
 */

namespace MyConverter;

class HtmlFileUtils
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

    /**
     * 删除HTML标签对应节点的所有属性
     * @param $content
     * @param $tag
     * @return mixed
     */
    public static function removeNodeAttribute($content, $tag) {
        $patterNode = "/([^>]*)(<([a-z\/][-a-z0-9_:.]*)[^>\/]*(\/*)>)([^<]*)/";
        $htmlResult = preg_replace_callback($patterNode, function($matches) use(&$tag) {
            $tagName = $matches[3];
            if (!empty($tagName) && (substr($tagName, 0, 1) != "/")
                && (strtolower($tagName) == $tag)) {
                return $matches[1]."<".$tag.">".$matches[5];
            }
            return $matches[1].$matches[2].$matches[5];
        }, $content);
        return $htmlResult;
    }

    public static function deleteDir($dirPath) {
        $handler = opendir($dirPath);
        while (($filename=readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {
                if (is_dir($dirPath . "/" . $filename)) {
                    HtmlFileUtils::deleteDir($dirPath . "/" . $filename);
                } else {
                    @unlink( $dirPath . "/" . $filename );
                }
            }
        }
        @closedir($dirPath);
        @rmdir($dirPath);
    }
}