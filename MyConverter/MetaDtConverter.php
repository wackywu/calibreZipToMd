<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/14
 * Time: 18:05
 */

namespace MyConverter;

use XMLReader;

class MetaDtConverter
{
    private $sourceDir;
    private $targetDir;

    public function convert($metaFile) {
        $this->sourceDir = $metaFile;
        $rootDir = pathinfo($metaFile, PATHINFO_DIRNAME);
        $this->targetDir = $rootDir.DIRECTORY_SEPARATOR.date('Ym').uniqid().".txt";

        $content = "";
        $reader = new XMLReader();
        $reader->open($metaFile);
        while($reader->read()) {
            if($reader->nodeType == XMLReader::ELEMENT){
                $nodeName = $reader->name;
            }
            if($reader->nodeType == XMLReader::TEXT && !empty($nodeName)){
                switch($nodeName){
                    case 'dc:title':
                        $content = $content."title\t".$reader->value."\n";
                        break;
                    case 'dc:creator':
                        $content = $content."creator\t".$reader->value."\n";
                        break;
                    case 'dc:date':
                        $content = $content."date\t".$reader->value."\n";
                        break;
                    case 'dc:publisher':
                        $content = $content."publisher\t".$reader->value."\n";
                        break;
                    case 'dc:description':
                        $content = $content."description\t".$reader->value."\n";
                        break;
                }
            }
        }
        file_put_contents($this->targetDir, $content);
        return $this->targetDir;
    }
}