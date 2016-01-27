<?php
return [
    'id' => 'testapp',
    'basePath' => dirname(__DIR__),
    'vendorPath' => '../../../vendor',
    'bootstrap' => [
        'fake-one',
        'fake-two',
        'fake-three',
        'fake-four'
    ],
    'controllerMap' => [
        'extension' => [
            'class' => \DevGroup\ExtensionsManager\commands\ExtensionController::className(),
        ],
    ],
    'components' => [
//        'db' => [
//            'class' => yii\db\Connection::className(),
//            'dsn' => 'mysql:host=localhost;dbname=yii2_extensions_manager',
//            'username' => 'root',
//            'password' => 'winston',
//        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
    ],
    'aliases' => [
        '@fakedev/FakeOne' =>  dirname(__DIR__) . '/testapp/vendor/fakedev/yii2-fake-ext/src',
        '@fakedev/FakeThree' =>  dirname(__DIR__) . '/testapp/vendor/fakedev/yii2-fake-ext3/src',
        '@fakedev2/FakeTwo' =>  dirname(__DIR__) . '/testapp/vendor/fakedev2/yii2-fake-ext2/src',
        '@fakedev2/FakeFour' =>  dirname(__DIR__) . '/testapp/vendor/fakedev2/yii2-fake-ext4/src',
    ],
    'modules' => [
        'extensions-manager' => [
            'class' => 'DevGroup\ExtensionsManager\ExtensionsManager',
        ],
        'fake-one' => [
            'class' => 'fakedev\FakeOne\FakeOneModule',
        ],
        'fake-two' => [
            'class' => 'fakedev2\FakeTwo\FakeTwoModule',
        ],
        'fake-three' => [
            'class' => 'fakedev\FakeThree\FakeThreeModule',
        ],
        'fake-four' => [
            'class' => 'fakedev2\FakeFour\FakeFourModule',
        ],
    ],
];