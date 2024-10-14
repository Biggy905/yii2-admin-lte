<?php

namespace applications\adminlte\ViewComponent\test;

use applications\adminlte\ViewComponent\AdminLteAssetBundle;
use applications\adminlte\ViewComponent\assets\AssetsInterface;
use yii\web\AssetBundle;

final class AdminLteAssets extends TestAssetBundle implements AssetsInterface
{
    public $sourcePath = '@resourcesAdminLte';

    public $css = [
        'css/style.css',
    ];

    public $js = [
        'js/main.js',
    ];
}
