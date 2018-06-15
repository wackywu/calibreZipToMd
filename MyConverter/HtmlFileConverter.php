<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/14
 * Time: 18:04
 */

namespace MyConverter;

use HtmlParser\ParserDom;
use League\HTMLToMarkdown\HtmlConverter;

class HtmlFileConverter
{
    private $converter;
    private $contentTag = "div.content";

    private function toMarkdown($content) {
        if (empty($this->converter)) {
            $this->converter = new HtmlConverter(array("strip_tags"=>true));
        }
        $markdown = $this->converter->convert($content);
        //处理代码块因pre节点导致的错乱
        $markdown = preg_replace("/```(\r?\n)*<pre>(\r?\n)*```/", "```\r\n", $markdown);
        $markdown = preg_replace("/(\r?\n)*```(\r?\n)*```/", "\r\n```", $markdown);

        //处理代码块显示错乱问题
        $markdown = preg_replace("/```(\r?\n)*/", "```\r\n", $markdown);
        $markdown = preg_replace("/(\r?\n)*```/", "\r\n```", $markdown);

        return $markdown;
    }

    private function parseHtmlContent($htmlFile) {
        $content = file_get_contents($htmlFile);
        //删除“pre”节点的属性
        $content = HtmlFileUtils::removeNodeAttribute($content, "pre");
        //解析Html各节点的class属性为内嵌style样式
        //$content = HtmlCssParser::parseHtmlClasses($content, $this->cssStyles);

        $html_dom = new ParserDom($content);
        $contentNode = $html_dom->find($this->contentTag, 0);
        if (empty($contentNode)) {
            $contentNode = $html_dom->find("body", 0);
        }
        if (!empty($contentNode)) {
            $content = $contentNode->innerHtml();
            return $this->toMarkdown($content);
        }
        return null;
    }

    public function convert($htmlFile) {
        $rootDir = pathinfo($htmlFile, PATHINFO_DIRNAME);
        $targetFile = $rootDir.DIRECTORY_SEPARATOR.date('Ym').uniqid().".md";
        $content = $this->parseHtmlContent($htmlFile);

        file_put_contents($targetFile, $content);
        return $targetFile;
    }
}