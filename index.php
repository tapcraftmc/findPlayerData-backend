<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

function searchFilesForString($directory, $searchStr) {
    $foundFiles = []; // 用于存储符合条件的文件名的数组

    // 打开目录
    $dir = opendir($directory);

    // 循环读取目录中的文件
    while (($file = readdir($dir)) !== false) {
        // 排除当前目录和上级目录
        if ($file != '.' && $file != '..') {
            // 读取文件的第一行
            $filePath = $directory . $file;
            $handle = fopen($filePath, "r");
            $firstLine = fgets($handle);
            $parts = explode(':', $firstLine);
            fclose($handle);

            // 检查第一行是否包含搜索字符串（不区分大小写）
            if (stripos($parts[1], $searchStr) !== false) {
                $fileNameWithoutExtension = pathinfo($file, PATHINFO_FILENAME);
                $foundFiles[] = $fileNameWithoutExtension; // 将符合条件的文件名添加到数组中
            }
        }
    }

    // 关闭目录
    closedir($dir);

    return $foundFiles; // 返回包含符合条件的文件名的数组
}

// 调用函数并传入目录和要搜索的字符串
$searchDirectory = 'userdata/';
$searchString = $_GET['name'];
if (empty($searchString) ||
    !preg_match('/^[a-zA-Z0-9_]*$/', $searchString) ||
    strlen(preg_replace('/[^a-zA-Z]/', '', $searchString)) < 3) {
    exit(json_encode(array(
        'code' => 400,
        'message' => 'Invalid name.'
    )));
}
$foundFiles = searchFilesForString($searchDirectory, $searchString);

// 输出符合条件的文件名
if (!empty($foundFiles)) {
    $returnData = array(
        'code' => 200,
        'data' => array()
    );
    foreach ($foundFiles as $file) {
        $yamlFile = "userdata/$file.yml";
        $yamlData = Yaml::parseFile($yamlFile);

        $returnData['data'][$file] = array();
        $returnData['data'][$file]['lastAccountName'] = $yamlData['lastAccountName'];
        if (!empty($yamlData['homes'])) {
            $returnData['data'][$file]['homes'] = $yamlData['homes'];
        } else {
            $returnData['data'][$file]['homes'] = array();
        }
        if (!empty($yamlData['logoutlocation'])) {
            $returnData['data'][$file]['logoutlocation'] = $yamlData['logoutlocation'];
        } else {
            $returnData['data'][$file]['logoutlocation'] = array();
        }
    }

    print_r(json_encode($returnData));
} else {
    print_r(json_encode(array(
        'code' => 404,
        'message' => 'No data found.'
    )));
}

?>
