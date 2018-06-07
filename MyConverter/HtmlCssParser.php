<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/7
 * Time: 14:02
 */

namespace MyConverter;

class HtmlCssParser
{
    /**
     * 解析Css样式内容，保存到cssStyles数组中
     * @param $content
     * @param $cssStyles
     */
    private static function parseHtmlCssStyle($content, &$cssStyles) {
        $matches = array();
        //$pattern = "/([^\\.^\\n^{\\*/}]*[\\.][^\\{]*)\\{([^\\}]*)\\}/";
        $pattern = "/([\\.][^\\{]*)\\{([^\\}]*)\\}/";
        if (preg_match_all($pattern, $content, $matches)) {
            for ($i = 0, $len = count($matches[0]); $i < $len; $i++) {
                $cssName = trim($matches[1][$i]);
                $cssValue = trim($matches[2][$i]);
                if (!empty($cssName) && !empty($cssValue)) {
                    $cssNames = explode(",", $cssName);
                    foreach ($cssNames as $name) {
                        if (!empty(trim($name))) {
                            $tag = trim($name);
                            $cssStyles[$tag] = empty($cssStyles[$tag]) ? $cssValue :
                                $cssStyles[$tag].";".$cssValue;
                        }
                    }
                }
            }
        }
    }

    /**
     * 将html的style字符串解析成样式Map
     * @param $cssStyle
     * @param $classes
     * @param $classMap
     * @return string
     */
    private static function parseCssStyle($cssStyle, $classes, $classMap) {
        $parseFunc = function($cssStyle) {
            $cssStyles = array();
            if (!empty($cssStyle)) {
                $cssValues = explode(";", $cssStyle);
                foreach ($cssValues as $css) {
                    if (!empty($css) && (count(explode(":", $css)) > 1)) {
                        $values = explode(":", $css);
                        if (!empty($values[0]) && !empty($values[1])) {
                            $cssStyles[trim($values[0])] = trim($values[1]);
                        }
                    }
                }
            }
            return $cssStyles;
        };

        $newStyle = "";
        if (!empty($cssStyle)) {
            $newStyle = $newStyle.$cssStyle;
            if (substr($cssStyle, -1) != ";") {
                $newStyle = $newStyle.";";
            }
        }
        foreach ($classes as $class) {
            if (!empty($class) && isset($classMap[$class])) {
                $cssStyles = $parseFunc($classMap[$class]);
                if (!empty($cssStyles)) {
                    foreach ($cssStyles as $key=>$value) {
                        if (!empty($key) && !empty($value) && !stripos($newStyle, $key)) {
                            $newStyle = $newStyle.$key.":".$value.";";
                        }
                    }
                }
            }
        }
        return $newStyle;
    }

    /**
     * 解析Html标签中的classes属性
     * @param $nodeHtml
     * @return array
     */
    private static function parseNodeClasses($nodeHtml) {
        $classes = array();
        if (!empty($nodeHtml)) {
            $tagName = substr(explode(" ", trim($nodeHtml))[0], 1);
            if (substr($tagName, -1) == ">") {
                $tagName = substr($tagName, 0, strlen($tagName) - 1);
            }
            $pattern = "/(<[^<]+class=\")([^\"]+)(\"[^>]*>)/";
            $oldClass = preg_replace($pattern, "$2", $nodeHtml);

            $oldClasses = ($oldClass == $nodeHtml) ? array() :
                explode(",", trim($oldClass));
            for ($i = 0, $len = count($oldClasses); $i < $len; $i++) {
                if (!empty($oldClasses[$i])) {
                    $classes[$i] = ($tagName . "." . trim($oldClasses[$i]));
                    $classes[$i + $len] = ".".trim($oldClasses[$i]);
                }
            }
            $classes[count($oldClasses) * 2] = $tagName;
        }
        return $classes;
    }

    /**
     * 查找并解析css样式文件
     * @param $filePath
     * @param array $fileNames
     */
    public static function parseHtmlCssFile($filePath, &$cssStyles, $fileNames = array()) {
        $handler = opendir($filePath);
        try {
            while(($file = readdir($handler)) !== false) {
                $fullPath = $filePath.DIRECTORY_SEPARATOR. $file;
                if($file == '.' || $file == '..') {
                    continue;
                } else if (is_dir($fullPath)) {
                    HtmlCssParser::parseHtmlCssFile($fullPath, $cssStyles, $fileNames);
                } else {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $needParse = !empty($fileNames) ? in_array($file, $fileNames) :
                        (!empty($ext) && ($ext == "css"));
                    if ($needParse && file_exists($fullPath)) {
                        $content = trim(file_get_contents($fullPath));
                        HtmlCssParser::parseHtmlCssStyle($content, $cssStyles);
                    }
                }
            }
        } finally {
            closedir($handler);
        }
    }

    /**解析Html各节点的class属性为内嵌style样式
     * @param $html
     * @return mixed
     */
    public static function parseHtmlClasses($html, &$cssStyles) {
        $patterStyle = "/(<[^<]+style=\")([^\"]+)(\"[^>]*>)/"; // Style属性样式正则表达式
        //$patterNode = "/([^>]*)(<([a-z/][-a-z0-9_:.]*)[^>/]*(\\/*)>)([^<]*)/";
        $patterNode = "/([^>]*)(<([a-z\/][-a-z0-9_:.]*)[^>\/]*(\/*)>)([^<]*)/";
        $htmlResult = preg_replace_callback($patterNode, function($matches) use(&$patterStyle, &$cssStyles) {
            $tag = $matches[3];
            $tagHtml = $matches[2];
            if (!empty($tag) && (substr($tag, 0, 1) != "/")) {
                $classes = "body" == strtolower($tag) ? array() :
                    HtmlCssParser::parseNodeClasses($tagHtml);
                if (!empty($classes)) {
                    $nodeStyles = array();
                    if (preg_match($patterStyle, $tagHtml, $nodeStyles)) {
                        $newStyle = HtmlCssParser::parseCssStyle($nodeStyles[0][2], $classes, $cssStyles);
                        if (!empty($newStyle)) {
                            $tagHtml = preg_replace("/(style=\"[^\"]+\")/", "style=\"".$newStyle."\"", $tagHtml);
                        }
                    }  else {
                        $newStyle = HtmlCssParser::parseCssStyle(null, $classes, $cssStyles);
                        if (!empty($newStyle)) {
                            $tagHtml = preg_replace("/(class=\"[^\"]+\")/", "style=\"".$newStyle."\"", $tagHtml);
                        }
                    }

                }
            }
            return $matches[1].$tagHtml.$matches[5];
        }, $html);

        return empty($htmlResult) ? $html : $htmlResult;
    }
}