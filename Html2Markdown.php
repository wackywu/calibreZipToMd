<?php

/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/7
 * Time: 10:32
 */

class Html2Markdown
{
    public function text2Markdown($content) {
        echo "text2Markdown" + $content;
    }

    public function file2Markdown($fileDir) {

        echo "file2Markdown";
    }

    public function convertHtmlZip($fileDir) {
        $converter = new MyConverter\HtmlZipConverter();
        $converter->convertHtmlZip($fileDir);
    }

    public static function test() {
        echo "asdf";
    }
}