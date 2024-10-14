<?php

namespace applications\adminlte\ViewComponent\assets;

use applications\adminlte\ViewComponent\components\interfaces\AdminLteEventInterface;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\AssetBundle;
use Yii;

abstract class AdminLteAssetBundle extends BaseObject
{
    public string $sourcePath;
    public string $basePath = '@AdminLtePublic';
    public string $baseUrl = '@AdminLteAssets';
    public array $depends = [];
    public array $js = [];
    public string $jsEvent = AdminLteEventInterface::EVENT_BODY_AFTER;
    public array $css = [];
    public string $cssEvent = AdminLteEventInterface::EVENT_HEAD;
    public array $jsOptions = [];
    public array $cssOptions = [];
    public array $publishOptions = [];

    public static function register($view): AssetBundle|AdminLteAssetBundle
    {
        return $view->registerAssetBundle(get_called_class());
    }

    public function init(): void
    {
        if ($this->sourcePath !== null) {
            $this->sourcePath = rtrim(Yii::getAlias($this->sourcePath), '/\\');
        }
        if ($this->basePath !== null) {
            $this->basePath = rtrim(Yii::getAlias($this->basePath), '/\\');
        }
        if ($this->baseUrl !== null) {
            $this->baseUrl = rtrim(Yii::getAlias($this->baseUrl), '/');
        }
    }

    public function getCssEvent(): string
    {
        return $this->cssEvent;
    }

    public function getJsEvent(): string
    {
        return $this->jsEvent;
    }

    public function registerAssetFiles($view): void
    {
        $manager = $view->getAssetManager();
        foreach ($this->js as $js) {
            if (is_array($js)) {
                $file = array_shift($js);
                $options = ArrayHelper::merge($this->jsOptions, $js);
                $view->registerJsFile($manager->getAssetUrl($this, $file, ArrayHelper::getValue($options, 'appendTimestamp')), $options);
            } elseif ($js !== null) {
                $view->registerJsFile($manager->getAssetUrl($this, $js), $this->jsOptions);
            }
        }
        foreach ($this->css as $css) {
            if (is_array($css)) {
                $file = array_shift($css);
                $options = ArrayHelper::merge($this->cssOptions, $css);
                $view->registerCssFile($manager->getAssetUrl($this, $file, ArrayHelper::getValue($options, 'appendTimestamp')), $options);
            } elseif ($css !== null) {
                $view->registerCssFile($manager->getAssetUrl($this, $css), $this->cssOptions);
            }
        }
    }

    public function publish($am): void
    {
        if ($this->sourcePath !== null && !isset($this->basePath, $this->baseUrl)) {
            list($this->basePath, $this->baseUrl) = $am->publish($this->sourcePath, $this->publishOptions);
        }

        if (isset($this->basePath, $this->baseUrl) && ($converter = $am->getConverter()) !== null) {
            foreach ($this->js as $i => $js) {
                if (is_array($js)) {
                    $file = array_shift($js);
                    if (Url::isRelative($file)) {
                        $js = ArrayHelper::merge($this->jsOptions, $js);
                        array_unshift($js, $converter->convert($file, $this->basePath));
                        $this->js[$i] = $js;
                    }
                } elseif (Url::isRelative($js)) {
                    $this->js[$i] = $converter->convert($js, $this->basePath);
                }
            }
            foreach ($this->css as $i => $css) {
                if (is_array($css)) {
                    $file = array_shift($css);
                    if (Url::isRelative($file)) {
                        $css = ArrayHelper::merge($this->cssOptions, $css);
                        array_unshift($css, $converter->convert($file, $this->basePath));
                        $this->css[$i] = $css;
                    }
                } elseif (Url::isRelative($css)) {
                    $this->css[$i] = $converter->convert($css, $this->basePath);
                }
            }
        }
    }
}
