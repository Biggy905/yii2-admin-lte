<?php

namespace applications\adminlte\ViewComponent\assets\bundles;

use applications\adminlte\ViewComponent\assets\AssetsInterface;
use applications\adminlte\ViewComponent\assets\AdminLteAssetBundle;
use applications\adminlte\ViewComponent\components\interfaces\AdminLteEventInterface;

final class AdminLteAssets extends AdminLteAssetBundle implements AssetsInterface
{
    public string $sourcePath = '@AdminLteResources';

    public array $css = [
        'dist/css/adminlte.css',
    ];

    public array $js = [
        'dist/js/adminlte.js'
    ];
}
