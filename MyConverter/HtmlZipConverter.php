<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/7
 * Time: 10:56
 */

namespace MyConverter;

use ZipArchive;

class HtmlZipConverter
{
    private $calibreConverter;

    /**
     * 解压Html压缩文件到sourceDir中
     * @param $zipFile
     */
    private function unZipHtml($zipFile, $srcDir) {
        $zip = new ZipArchive();
        if ($zip->open($zipFile)) {
            $zip->extractTo($srcDir);
            $zip->close();
        }
    }

    private function addFileToZip($path, $zip, $rootPath){
        $basePath = "";
        if (strlen($path) > strlen($rootPath)) {
            $basePath = substr($path, strlen($rootPath));
            if (substr($basePath, -1) != "/") {
                $basePath = $basePath . "/";
            }
            if (substr($basePath, 0, 1) == "/") {
                $basePath = substr($basePath, 1);
            }
        }
        $handler = opendir($path);
        while (($filename=readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {
                if (is_dir($path . "/" . $filename)) {
                    $this->addFileToZip($path . "/" . $filename, $zip, $rootPath);
                } else {
                   //$zip->addFile($path . "/" . $filename);
                    $zip->addFile($path . "/" . $filename, $basePath.$filename);
                }
            }
        }
        @closedir($path);
    }

    private function zipHtml($zipPath, $targetDir) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath,ZipArchive::OVERWRITE|ZipArchive::CREATE)) {
            $this->addFileToZip($targetDir, $zip, $targetDir);
            $zip->close();
        }
    }

    private function getCalibreConverter() {
        if (empty($this->calibreConverter)) {
            $this->calibreConverter = new CalibreConverter();
        }
        return $this->calibreConverter;
    }

    public function convert($zipFile) {
        $rootDir = pathinfo($zipFile, PATHINFO_DIRNAME);
        $sourceDir = HtmlFileUtils::getExistsDir($rootDir.DIRECTORY_SEPARATOR.date('Ym').uniqid());
        $targetDir = HtmlFileUtils::getExistsDir($rootDir.DIRECTORY_SEPARATOR.date('Ym').uniqid());
        try {
            //解压Html压缩文件
            $this->unZipHtml($zipFile, $sourceDir);
            //转换Calibre的zip文件
            $this->getCalibreConverter()->convertCalibre($sourceDir, $targetDir);
            //压缩转换后文件
            $DesZipFile = $rootDir . DIRECTORY_SEPARATOR . date('Ym') . uniqid() . ".zip";
            $this->zipHtml($DesZipFile, $targetDir);
            return $DesZipFile;
        } finally {
            HtmlFileUtils::deleteDir($sourceDir);
            HtmlFileUtils::deleteDir($targetDir);
        }
    }
}