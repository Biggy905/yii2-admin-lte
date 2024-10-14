<?php

namespace applications\adminlte\ViewComponent\builders\structures;



use applications\adminlte\ViewComponent\assets\AdminLteAssetManager;
use applications\adminlte\ViewComponent\assets\AssetsInterface;
use applications\adminlte\ViewComponent\components\View;

final class AdminLteResources
{
    public array $bundles;
    private array $assets;

    public function __construct(
        private readonly AdminLteAssetManager $assetManager,
        private readonly View $view,
    ) {

    }

    public function addResources(AssetsInterface ...$assets): self
    {
        $this->assets = $assets;

        return $this;
    }

    public function bundles(): self
    {
        foreach ($this->assets as $asset) {
            $asset::register($this->view);
            $this->bundles[$asset::class] = $this->assetManager->getBundle($asset::class);
        }

        return $this;
    }
}
