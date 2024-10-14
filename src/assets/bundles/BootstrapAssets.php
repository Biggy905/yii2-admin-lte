<?php

namespace applications\adminlte\ViewComponent\assets\bundles;

use applications\adminlte\ViewComponent\assets\AssetsInterface;
use applications\adminlte\ViewComponent\assets\AdminLteAssetBundle;
use applications\adminlte\ViewComponent\components\interfaces\AdminLteEventInterface;

final class BootstrapAssets extends AdminLteAssetBundle implements AssetsInterface
{
    public string $sourcePath = '@AdminLteResources';

    public array $js = [
        'plugins/bootstrap/bootstrap.js'
    ];
}
