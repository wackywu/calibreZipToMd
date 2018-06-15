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
    private $sourceDir;
    private $targetDir;
    private $calibreConverter;

    /**
     * 解压Html压缩文件到sourceDir中
     * @param $zipFile
     */
    private function unZipHtml($zipFile) {
        $zip = new ZipArchive();
        if ($zip->open($zipFile)) {
            $zip->extractTo($this->sourceDir);
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
        $zipDir = is_string($zipFile) ? $zipFile :
            dirname($zipFile);
        $rootDir = pathinfo($zipDir, PATHINFO_DIRNAME);
        $this->sourceDir = HtmlFileUtils::getExistsDir($rootDir.DIRECTORY_SEPARATOR.date('Ym').uniqid());
        $this->targetDir = HtmlFileUtils::getExistsDir($rootDir.DIRECTORY_SEPARATOR.date('Ym').uniqid());
        //解压Html压缩文件
        $this->unZipHtml($zipDir);
        $this->getCalibreConverter()->convertCalibre($this->sourceDir, $this->targetDir);
        return $this->targetDir;
    }
}