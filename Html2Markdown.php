<?php

/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/7
 * Time: 10:32
 */

class Html2Markdown
{
    public function convertHtmlZip($fileDir) {
        $converter = new MyConverter\HtmlZipConverter();
        return $converter->convert($fileDir);
    }

    public function convertHtmlFile($fileDir) {
        $converter = new MyConverter\HtmlFileConverter();
        return $converter->convert($fileDir);
    }

    public function convertMetaFile($fileDir) {
        $converter = new MyConverter\MetaDtConverter();
        return $converter->convert($fileDir);
    }

    public function convertCalibre($fileDir) {
        $ext = strtolower(pathinfo($fileDir, PATHINFO_EXTENSION));
        if ( in_array($ext, explode('|', 'jpg|jpeg|gif|png'))) {
            return $fileDir;
        }

        $targetFile = null;
        switch ($ext) {
            case 'zip':
                $targetFile = $this->convertHtmlZip($fileDir);
                break;
            case 'opf':
                $targetFile = $this->convertMetaFile($fileDir);
                break;
            case 'html':
                $targetFile = $this->convertHtmlFile($fileDir);
                break;
            default:
                break;
        }
        return $targetFile;
    }
}