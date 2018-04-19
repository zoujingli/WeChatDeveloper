<?php

include '../include.php';

// 小程序配置
$config = [
    'appid'     => 'wx6bb7b70258da09c6',
    'appsecret' => '78b7b8d65bd67b078babf951d4342b42',
];

$mini = new WeMini\Qrcode($config);

echo '<pre>';
try {
//    var_dump($mini->getCode('pages/index?query=1'));
//    var_dump($mini->getCodeUnlimit('432432', 'pages/index/index'));
//    var_dump($mini->createQrcode('pages/index?query=1'));
} catch (Exception $e) {
    var_dump($e->getMessage());
}
