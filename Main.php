<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/7
 * Time: 14:56
 */
spl_autoload_register(function ($name) {
    $file = __DIR__ .DIRECTORY_SEPARATOR.$name.".php";
    $file = str_replace("\\", DIRECTORY_SEPARATOR, $file);
    //echo "Want to load $name->$file.\n";
    if (file_exists($file)) {
        include_once $file;
    }
});

//var_dump(count($argv));
if (count($argv) < 2) {
    //echo "缺少文件路径参数！";
    //$converter = new Html2Markdown();
    //var_dump ($converter->convertCalibre("C:/Users/Administrator/Desktop/test/ttt.zip"));
    exit(1);
}
$sourceFile = $argv[1];
if (file_exists($sourceFile)) {
    $converter = new Html2Markdown();
    $targetFile = $converter->convertCalibre($sourceFile);
    #var_dump ($targetFile);
    echo $targetFile;
} else {
    echo "文件不存在：".$sourceFile;
    exit(1);
}
//php Main.php C:\Users\Administrator\Desktop\test\ttt.zip
//var_dump ($converter->convertCalibre("C:/Users/Administrator/Desktop/test/ttt.zip"));
//var_dump ($converter->convertCalibre("C:/Users/Administrator/Desktop/test/part0017.html"));
//var_dump ($converter->convertCalibre("C:/Users/Administrator/Desktop/test/metadata.opf"));
