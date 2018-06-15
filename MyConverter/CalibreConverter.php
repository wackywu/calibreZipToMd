<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/7
 * Time: 10:53
 */

namespace MyConverter;

use HtmlParser\ParserDom;
use League\HTMLToMarkdown\HtmlConverter;

class CalibreConverter
{
    private $converter;

    private $sourceDir;
    private $targetDir;
    private $imageDir;
    private $indexHtml;

    private $isParseCss = false;

    private $htmlPages = array();
    private $cssStyles = array();
    private $documents = array();

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

    private function parseHtmlContent($url, &$nextUrl) {
        if (empty($this->htmlPages[$url])) {
            $content = file_get_contents($url);
            //删除“pre”节点的属性
            $content = HtmlFileUtils::removeNodeAttribute($content, "pre");
            //解析Html各节点的class属性为内嵌style样式
            if ($this->isParseCss) {
                $content = HtmlCssParser::parseHtmlClasses($content, $this->cssStyles);
            }
            $html_dom = new ParserDom($content);
            $contentDiv = $html_dom->find("div.calibreEbookContent", 0);
            foreach ($contentDiv->find("div.calibreEbNavTop") as $div) {
                //获取下一页Html文件路径
                $nextNode = $div->find("a.calibreANext");
                if (!empty($nextNode) && !empty($nextNode[0]->node)) {
                    try {
                        $nextUrl = trim($nextNode[0]->getAttr("href"));
                        if (!empty($nextUrl)) {
                            $nextUrl = substr($url, 0,
                                    strlen($url) - strlen(strrchr($url, "/")))."/".$nextUrl;
                            if (!(is_file($nextUrl) && file_exists($nextUrl))) {
                                $nextUrl = null;
                            }
                        }
                    } catch(\Exception $ex) {
                        $nextUrl = null;
                    }
                }
                $div->node->parentNode->removeChild($div->node);
            }
            $this->htmlPages[$url] = array("node"=>$contentDiv->node, "next"=>$nextUrl);
        }
        $node = $this->htmlPages[$url]["node"];
        $nextUrl = $this->htmlPages[$url]["next"];
        return empty($node) ? null : new ParserDom($node);
    }

    private function filterHtmlNodes($root, $begin, $end, &$isBegin, &$isEnd) {
        $remove = array();
        $target = $root->cloneNode();
        if ($root->hasChildNodes()) {
            $childNodes = $root->childNodes;
            for ($i = 0, $len = $childNodes->length; $i < $len; $i++) {
                $childNode = $childNodes->item($i);
                $isBegin = $isBegin || empty($begin) || $childNode === $begin;
                $isEnd = $isEnd || (!empty($end) && $childNode === $end);
                if (!$isEnd) {
                    $targetNode = $this->filterHtmlNodes($childNode, $begin, $end, $isBegin, $isEnd);
                    if ($isBegin) {
                        $target->appendChild($targetNode);
                        if (!$childNode->hasChildNodes()) {
                            $remove[count($remove)] = $i;
                        }
                    } else {
                        $remove[count($remove)] = $i;
                    }
                } else {
                    break;
                }
            }

            for ($i = count($remove) - 1; $i >= 0; $i--) {
                $root->removeChild($childNodes->item($remove[$i]));
            }
        }
        return $target;
    }

    private function filterHtmlContent($root, $begin, $end) {
        $rootNode = $root->node;
        $findEndNode = function($nodes) use (&$root) {
            $endNode = null;
            if (!empty($nodes)) {
                for ($i = 0, $len = count($nodes); $i < $len; $i++) {
                    $endNode = $root->find("#".$nodes[$i], 0);
                    if ($endNode && !empty($endNode)) {
                        $endNode = $endNode->node;
                        break;
                    }
                }
            }
            return $endNode;
        };
        $beginNode = empty($begin) ? null : $root->find("#".$begin, 0);
        $beginNode = !$beginNode || empty($beginNode) ? null : $beginNode->node;
        $endNode = empty($end) ? null : $findEndNode(explode(",", $end));

        if (empty($beginNode) && empty($endNode)) {
            return $root;
        } else {
            $isEnd = false;
            $isBegin = empty($beginNode);
            $htmlNode = $this->filterHtmlNodes($rootNode, $beginNode, $endNode, $isBegin, $isEnd);
            return new ParserDom($htmlNode);
        }
    }

    private function parseIndexHtml($node, $parent, &$documents) {
        if (!empty($node) && !empty($node->node)
            && (XML_ELEMENT_NODE === $node->node->nodeType)) {
            $parentIndex = $parent;
            if ($node->node->tagName == "li") {
                $a = $node->find("a", 0);
                $aNode = $a->node;
                if (!empty($a) && !empty($aNode) &&
                    ($aNode->parentNode === $node->node)) {
                    $document = array();
                    $document["doc_name"] = $a->getPlainText();
                    $document["parent_id"] = $parentIndex;

                    $href = $a->getAttr("href");
                    $nodeId = strrchr($href, "#");
                    if (!empty($nodeId)) {
                        $document["doc_node"] = substr($nodeId, 1);
                        $document["doc_url"] = substr($href, 0, strlen($href) - strlen($nodeId));
                    } else {
                        $document["doc_node"] = null;
                        $document["doc_url"] = $href;
                    }
                    $document["calibre_url"] = substr(strrchr($href, "/"), 1);
                    if(!empty($document["doc_name"])) {
                        $documents[count($documents)] = $document;
                        $parentIndex = count($documents) - 1;
                    }
                }
            }
            foreach($node->node->childNodes as $element) {
                $this ->parseIndexHtml(new ParserDom($element), $parentIndex, $documents);
            }
        }
    }

    private function parseIndexContent($url, &$documents) {
        $root = $this->parseHtmlContent($url, $nextPage);
        $content = $this->filterHtmlContent($root, null, null);
        $this->parseIndexHtml($content, null, $documents);

        $getEndNodeIds = function($url, $index) use (&$documents) {
            $endNodes = array();
            for ($i = $index + 1, $len = count($documents); $i < $len; $i++) {
                $curUrl = $documents[$i]["doc_url"];
                $curNode = $documents[$i]["doc_node"];
                if ($curUrl != $url) {
                    break;
                } else if (!empty($curNode)) {
                    $endNodes[count($endNodes)] = $curNode;
                }
            }
            return implode(",", $endNodes);
        };

        for ($i = 0, $len = count($documents); $i < $len; $i++) {
            $url = $documents[$i]["doc_url"];
            $documents[$i]["doc_next"] = ($i < $len - 1) ? $documents[$i + 1]["doc_url"] : null;
            $documents[$i]["doc_node_start"] = $documents[$i]["doc_node"];
            $documents[$i]["doc_node_end"] = $getEndNodeIds($url, $i);
        }

        return $documents;
    }

    private function parseDocContent(&$document, &$docImages) {
        $imagePath = pathinfo( $this->imageDir, PATHINFO_BASENAME);
        $replaceImages = function ($content) use (&$docImages, &$imagePath) {
            foreach ($content->find("img") as $img) {
                if (!empty($img) && !empty($img->getAttr("src"))) {
                    $file = substr(strrchr($img->getAttr("src"), "/"), 1);
                    $img->node->setAttribute("src", $imagePath."/".$file);
                    $docImages[count($docImages)] = $file;
                }
            }
            return $content;
        };

        $fullUrl = HtmlFileUtils::getExistsDir($this->sourceDir.DIRECTORY_SEPARATOR. $document["doc_url"]);
        $nextUrl = HtmlFileUtils::getExistsDir($this->sourceDir.DIRECTORY_SEPARATOR. $document["doc_next"]);

        $root = $this->parseHtmlContent($fullUrl, $nextPage);
        $content = $this->filterHtmlContent($root,
            $document["doc_node_start"], $document["doc_node_end"]);
        $contentHtml = $replaceImages($content)->innerHtml();

        if (empty($end) && !empty($nextUrl)) {
            //文档包含多个html：当下一页的url小于下一个文档的url时，则表示下一页属于当前文档
            while (!empty($nextPage) && (strcasecmp($nextPage, $nextUrl) < 0)) {
                $nextRoot = $this->parseHtmlContent($nextPage, $nextPage);
                $nextRoot = $this->filterHtmlContent($nextRoot, null, null);

                $contentHtml .= "\r\n".$replaceImages($nextRoot)->innerHtml();
            }
        }
        $document["doc_html"] = $contentHtml;
        $document["doc_content"] = $this->toMarkdown($contentHtml);

    }

    public function copyImageFiles($imgFiles = null) {
        $imgPath = HtmlFileUtils::getExistsDir($this->imageDir);
        $allowExt = explode('|', 'jpg|jpeg|gif|png');
        $copyFunc = function($filePath) use (&$imgPath, &$imgFiles, &$allowExt, &$copyFunc) {
            $handler = opendir($filePath);
            try {
                while(($file = readdir($handler)) !== false) {
                    $fullPath = $filePath.DIRECTORY_SEPARATOR. $file;
                    if($file == '.' || $file == '..') {
                        continue;
                    } else if (is_dir($fullPath)) {
                        $copyFunc($fullPath);
                    } else {
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $allowCopy = !empty($imgFiles) ? in_array($file, $imgFiles) :
                            (!empty($ext) && in_array($ext, $allowExt));
                        if ($allowCopy && !file_exists($imgPath . DIRECTORY_SEPARATOR . $file)) {
                            @copy($fullPath, $imgPath . DIRECTORY_SEPARATOR . $file);
                        }
                    }
                }
            } finally {
                closedir($handler);
            }
        };

        $file_path = $this->sourceDir;
        if (!empty($file_path)) {
            @mkdir($imgPath, 0777, true);
            $copyFunc(HtmlFileUtils::getExistsDir($file_path));
        }
    }

    /**
     * 在文档内容开头中增加相关参数：如"文档标题title"
     */
    private function addArguments(&$document) {
        $args = array();
        $args["title"] = $document["doc_name"];
        //$args["date"] = "";
        //$args["tags"] = "";

        $split = "---";
        $content = $split;
        foreach ($args as $key => $value) {
            if (!empty($key)) {
                $content = $content . "\n" . $key . ": " . $value;
            }
        }
        $content = $content."\n".$split;
        $document["doc_content"] =  $content."\n".$document["doc_content"];
    }

    /**
     * //替换文档中相关的链接地址为：$doc_sort + "_" + $doc_name;
     */
    private function replaceDocUrl() {
        $calibreUrl = array();
        foreach ($this->documents as $document) {
            $calibreUrl[$document["calibre_url"]] = $document["file_name"];
        }
        for ($i = 0, $len = count($this->documents); $i < $len; $i++) {
            $document = $this->documents[$i];
            $matches = array();
            $content = $document["doc_content"];
            if (!empty($content) &&
                preg_match_all("/\\]\\(part(\d+)\\.html(#.*)?\\)/", $content, $matches)) {
                foreach($matches[0] as $match) {
                    $url = substr($match, 2, strlen($match) - 3);
                    if (!empty($calibreUrl[$url])) {
                        $content = str_replace($match, "](".$calibreUrl[$url].")", $content);
                    } else {
                        $content = str_replace($match, "](#)", $content);
                    }
                }
                $document["doc_content"] = $content;
                $this->documents[$i] = $document;
            }
        }
    }

    /**
     * 解析文档
     */
    private function parseDocuments() {
        //解析stylesheet.css样式文件
        if ($this->isParseCss) {
            HtmlCssParser::parseHtmlCssFile($this->sourceDir, $this->cssStyles, array("stylesheet.css"));
        }

        $documents = array();
        $this->parseIndexContent($this->indexHtml, $documents);
        for ($i = 0, $sortId = 0, $len = count($documents); $i < $len; $i++) {
            $document = $documents[$i];
            $docImages = array();
            $this->parseDocContent($document, $docImages);
            //复制文档相关的图片
            if(!empty($docImages)) {
                $this->copyImageFiles($docImages);
            }
            //获取文档排序层次doc_sort
            if (!empty($document["parent_id"])) {
                $parentId = $document["parent_id"];
                $parentDoc = $this->documents[$parentId];
                $index = empty($parentDoc["child_count"]) ? 1 : ($parentDoc["child_count"] + 1);
                $document["doc_sort"]  = $parentDoc["doc_sort"].".".$index;
                $parentDoc["child_count"] = $index;
                $this->documents[$parentId] = $parentDoc;
            } else {
                $document["doc_sort"] = ++$sortId;
            }
            //文档内容开头增加参数（如文档标题title:）
            //$this->addArguments($document);
            //设置保存文件名doc_sort + "_" + uuid
            $document["file_name"] = $document["doc_sort"]."_".uniqid().".md";
            $this->documents[$i] = $document;
        }
        //替换文档中相关的链接地址为文件名：file_name;
        $this->replaceDocUrl();
    }

    /**
     * 获取书籍的目录页面
     * @return \Illuminate\Contracts\Routing\UrlGenerator|null|string
     */
    private function getIndexHtml() {
        $indexHtml = null;
        $handler = opendir($this->sourceDir);
        try {
            while(($file = readdir($handler)) !== false) {
                $sub_dir = $this->sourceDir.DIRECTORY_SEPARATOR.$file;
                if($file == '.' || $file == '..' || is_dir($sub_dir)) {
                    continue;
                } else {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if(!empty($ext) && ($ext == "html")) {
                        $indexHtml = $this->sourceDir.DIRECTORY_SEPARATOR.$file;
                        break;
                    }
                }
            }
        } finally {
            closedir($handler);
        }
        return $indexHtml;
    }

    /**
     * 保存单个文档
     */
    private function writeDocument($document) {
        $isSuccess = false;
        $file_path = $this->targetDir.DIRECTORY_SEPARATOR.$document["file_name"];
        $fp = fopen($file_path,"w");
        try {
            fwrite($fp, $document["doc_content"]);
            $isSuccess = true;
        } catch (\Exception $ex) {
            echo "保存文档失败：".$file_path;
        } finally {
            fclose($fp);
        }
        return $isSuccess;
    }

    /**
     * 保存所有文档
     */
    private function writeDocuments() {
        @mkdir($this->sourceDir, 0777, true);
        foreach ($this->documents as $document) {
            $this->writeDocument($document);
        }
    }

    private function writeIndexMenu() {
        $content = null;
        foreach ($this->documents as $document) {
            if (!empty($content)) {
                $content = $content."\n";
            }
            $content = $content.$document["doc_sort"]."\t".$document["file_name"]."\t".$document["doc_name"];
        }
        $file_path = $this->targetDir.DIRECTORY_SEPARATOR."indexMenu.txt";
        file_put_contents($file_path, $content);
    }

    /**转换Calibre的书籍
     * @param $sourceDir
     * @param $targetDir
     */
    public function convertCalibre($sourceDir, $targetDir) {
        $this->sourceDir = $sourceDir;
        $this->targetDir = $targetDir;
        $this->imageDir =  HtmlFileUtils::getExistsDir($targetDir.DIRECTORY_SEPARATOR."images");
        $this->indexHtml = $this->getIndexHtml();//索引页面路径

        $this->documents = array();
        $this->htmlPages = array();
        $this->cssStyles = array();

        $this->parseDocuments();
        $this->writeDocuments();
        $this->writeIndexMenu();
    }

    public function getDocuments($index = null) {
        $documents = $this->documents;
        return empty($index) ? $documents : $documents[$index];
    }



    /**
     * 处理代码块因pre节点导致的错乱
     * @param $content
     * @return mixed
     */
    public static function dealCodePartContent($content) {
        $content = HtmlFileUtils::removeNodeAttribute($content, "pre");
        $content = preg_replace("/```(\r?\n)*<pre>(\r?\n)*```/", "```\r\n", $content);
        $content = preg_replace("/(\r?\n)*```(\r?\n)*```/", "\r\n```", $content);

        $content = preg_replace("/```(\r?\n)*/", "```\r\n", $content);
        $content = preg_replace("/(\r?\n)*```/", "\r\n```", $content);

        return $content;
    }
}