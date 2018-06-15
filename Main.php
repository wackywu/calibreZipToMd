<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2018/6/7
 * Time: 14:56
 */
spl_autoload_register(function ($name) {
    echo "Want to load $name.\n";
    $file = __DIR__ .DIRECTORY_SEPARATOR.$name.".php";
    if (file_exists($file)) {
        include_once $file;
    }
});

var_dump(count($argv));
if (count($argv) < 2) {
    echo "缺少文件路径参数！";
    exit;
}
$sourceFile = $argv[1];
if (file_exists($sourceFile)) {
    $converter = new Html2Markdown();
    $targetFile = $converter->convertCalibre($sourceFile);
    var_dump ($targetFile);
} else {
    echo "文件不存在：".$sourceFile;
}

//php Main.php C:\Users\Administrator\Desktop\test\ttt.zip
//var_dump ($converter->convertCalibre("C:/Users/Administrator/Desktop/test/ttt.zip"));
//var_dump ($converter->convertCalibre("C:/Users/Administrator/Desktop/test/part0017.html"));
//var_dump ($converter->convertCalibre("C:/Users/Administrator/Desktop/test/metadata.opf"));