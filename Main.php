<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/7
 * Time: 14:56
 */
//var_dump($argv);
//exit;
spl_autoload_register(function ($name) {
    echo "Want to load $name.\n";
    $file = __DIR__ .DIRECTORY_SEPARATOR.$name.".php";
    if (file_exists($file)) {
        include_once $file;
    }
});

//echo preg_replace("/[\\\\\/:*?\"<>|\'`\r\n]/", "", "aasdf:ads|as df?*fsd\\fa\"/s|asdf\<d\>asdf\rasdf\nasdfasdff");



$converter = new Html2Markdown();
$converter->convertHtmlZip("C:/Users/Administrator/Desktop/test/MongoDBQuan Wei Zhi Nan (Di 2Ba - Huo Duo Luo Fu  (Kristina Chodo.zip");