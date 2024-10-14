<?php

namespace applications\adminlte\ViewComponent\test;

use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\web\AssetBundle;
use yii\web\AssetConverter;
use yii\web\AssetConverterInterface;
use yii\web\AssetManager;
use Yii;

class TestAssetManager extends AssetManager
{
    public $bundles = [];

    public $basePath = '@webroot/assets';

    public $baseUrl = '@web/assets';

    public $assetMap = [];

    public $linkAssets = false;

    public $fileMode;

    public $dirMode = 0775;

    public $beforeCopy;

    public $afterCopy;

    public $forceCopy = false;

    public $appendTimestamp = false;

    public $hashCallback;

    private $_dummyBundles = [];

    public function init()
    {
        parent::init();
        $this->basePath = Yii::getAlias($this->basePath);

        $this->basePath = realpath($this->basePath);
        $this->baseUrl = rtrim(Yii::getAlias($this->baseUrl), '/');
    }

    private $_isBasePathPermissionChecked;

    public function checkBasePathPermission()
    {
        // if the check is been done already, skip further checks
        if ($this->_isBasePathPermissionChecked) {
            return;
        }

        if (!is_dir($this->basePath)) {
            throw new InvalidConfigException("The directory does not exist: {$this->basePath}");
        }

        if (!is_writable($this->basePath)) {
            throw new InvalidConfigException("The directory is not writable by the Web process: {$this->basePath}");
        }

        $this->_isBasePathPermissionChecked = true;
    }

    public function getBundle($name, $publish = true)
    {
        if ($this->bundles === false) {
            return $this->loadDummyBundle($name);
        } elseif (!isset($this->bundles[$name])) {
            return $this->bundles[$name] = $this->loadBundle($name, [], $publish);
        } elseif ($this->bundles[$name] instanceof AssetBundle) {
            return $this->bundles[$name];
        } elseif (is_array($this->bundles[$name])) {
            return $this->bundles[$name] = $this->loadBundle($name, $this->bundles[$name], $publish);
        } elseif ($this->bundles[$name] === false) {
            return $this->loadDummyBundle($name);
        }

        throw new InvalidConfigException("Invalid asset bundle configuration: $name");
    }

    protected function loadBundle($name, $config = [], $publish = true)
    {
        if (!isset($config['class'])) {
            $config['class'] = $name;
        }
        /* @var $bundle AssetBundle */
        $bundle = Yii::createObject($config);
        if ($publish) {
            $bundle->publish($this);
        }

        return $bundle;
    }

    protected function loadDummyBundle($name)
    {
        if (!isset($this->_dummyBundles[$name])) {
            $bundle = Yii::createObject(['class' => $name]);
            $bundle->sourcePath = null;
            $bundle->js = [];
            $bundle->css = [];

            $this->_dummyBundles[$name] = $bundle;
        }

        return $this->_dummyBundles[$name];
    }

    public function getAssetUrl($bundle, $asset, $appendTimestamp = null)
    {
        $assetUrl = $this->getActualAssetUrl($bundle, $asset);
        $assetPath = $this->getAssetPath($bundle, $asset);

        $withTimestamp = $this->appendTimestamp;
        if ($appendTimestamp !== null) {
            $withTimestamp = $appendTimestamp;
        }

        if ($withTimestamp && $assetPath && ($timestamp = @filemtime($assetPath)) > 0) {
            return "$assetUrl?v=$timestamp";
        }

        return $assetUrl;
    }

    public function getAssetPath($bundle, $asset)
    {
        if (($actualAsset = $this->resolveAsset($bundle, $asset)) !== false) {
            return Url::isRelative($actualAsset) ? $this->basePath . '/' . $actualAsset : false;
        }

        return Url::isRelative($asset) ? $bundle->basePath . '/' . $asset : false;
    }

    protected function resolveAsset($bundle, $asset)
    {
        if (isset($this->assetMap[$asset])) {
            return $this->assetMap[$asset];
        }
        if ($bundle->sourcePath !== null && Url::isRelative($asset)) {
            $asset = $bundle->sourcePath . '/' . $asset;
        }

        $n = mb_strlen($asset, Yii::$app->charset);
        foreach ($this->assetMap as $from => $to) {
            $n2 = mb_strlen($from, Yii::$app->charset);
            if ($n2 <= $n && substr_compare($asset, $from, $n - $n2, $n2) === 0) {
                return $to;
            }
        }

        return false;
    }

    private $_converter;

    public function getConverter()
    {
        if ($this->_converter === null) {
            $this->_converter = Yii::createObject(AssetConverter::className());
        } elseif (is_array($this->_converter) || is_string($this->_converter)) {
            if (is_array($this->_converter) && !isset($this->_converter['class'])) {
                $this->_converter['class'] = AssetConverter::className();
            }
            $this->_converter = Yii::createObject($this->_converter);
        }

        return $this->_converter;
    }

    public function setConverter($value)
    {
        $this->_converter = $value;
    }

    private $_published = [];

    public function publish($path, $options = [])
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path];
        }

        if (!is_string($path) || ($src = realpath($path)) === false) {
            throw new InvalidArgumentException("The file or directory to be published does not exist: $path");
        }

        if (!is_readable($path)) {
            throw new InvalidArgumentException("The file or directory to be published is not readable: $path");
        }

        if (is_file($src)) {
            return $this->_published[$path] = $this->publishFile($src);
        }

        return $this->_published[$path] = $this->publishDirectory($src, $options);
    }

    protected function publishFile($src)
    {
        $this->checkBasePathPermission();

        $dir = $this->hash($src);
        $fileName = basename($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        $dstFile = $dstDir . DIRECTORY_SEPARATOR . $fileName;

        if (!is_dir($dstDir)) {
            FileHelper::createDirectory($dstDir, $this->dirMode, true);
        }

        if ($this->linkAssets) {
            if (!is_file($dstFile)) {
                try { // fix #6226 symlinking multi threaded
                    symlink($src, $dstFile);
                } catch (\Exception $e) {
                    if (!is_file($dstFile)) {
                        throw $e;
                    }
                }
            }
        } elseif (@filemtime($dstFile) < @filemtime($src)) {
            copy($src, $dstFile);
            if ($this->fileMode !== null) {
                @chmod($dstFile, $this->fileMode);
            }
        }

        if ($this->appendTimestamp && ($timestamp = @filemtime($dstFile)) > 0) {
            $fileName = $fileName . "?v=$timestamp";
        }

        return [$dstFile, $this->baseUrl . "/$dir/$fileName"];
    }

    protected function publishDirectory($src, $options)
    {
        $this->checkBasePathPermission();

        $dir = $this->hash($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        if ($this->linkAssets) {
            if (!is_dir($dstDir)) {
                FileHelper::createDirectory(dirname($dstDir), $this->dirMode, true);
                try { // fix #6226 symlinking multi threaded
                    symlink($src, $dstDir);
                } catch (\Exception $e) {
                    if (!is_dir($dstDir)) {
                        throw $e;
                    }
                }
            }
        } elseif (!empty($options['forceCopy']) || ($this->forceCopy && !isset($options['forceCopy'])) || !is_dir($dstDir)) {
            $opts = array_merge(
                $options,
                [
                    'dirMode' => $this->dirMode,
                    'fileMode' => $this->fileMode,
                    'copyEmptyDirectories' => false,
                ]
            );
            if (!isset($opts['beforeCopy'])) {
                if ($this->beforeCopy !== null) {
                    $opts['beforeCopy'] = $this->beforeCopy;
                } else {
                    $opts['beforeCopy'] = function ($from, $to) {
                        return strncmp(basename($from), '.', 1) !== 0;
                    };
                }
            }
            if (!isset($opts['afterCopy']) && $this->afterCopy !== null) {
                $opts['afterCopy'] = $this->afterCopy;
            }
            FileHelper::copyDirectory($src, $dstDir, $opts);
        }

        return [$dstDir, $this->baseUrl . '/' . $dir];
    }

    public function getPublishedPath($path)
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path][0];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            return $this->basePath . DIRECTORY_SEPARATOR . $this->hash($path) . (is_file($path) ? DIRECTORY_SEPARATOR . basename($path) : '');
        }

        return false;
    }

    public function getPublishedUrl($path)
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path][1];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            return $this->baseUrl . '/' . $this->hash($path) . (is_file($path) ? '/' . basename($path) : '');
        }

        return false;
    }

    protected function hash($path)
    {
        if (is_callable($this->hashCallback)) {
            return call_user_func($this->hashCallback, $path);
        }
        $path = (is_file($path) ? dirname($path) : $path) . filemtime($path);
        return sprintf('%x', crc32($path . Yii::getVersion() . '|' . $this->linkAssets));
    }

    public function getActualAssetUrl($bundle, $asset)
    {
        if (($actualAsset = $this->resolveAsset($bundle, $asset)) !== false) {
            if (strncmp($actualAsset, '@web/', 5) === 0) {
                $asset = substr($actualAsset, 5);
                $baseUrl = Yii::getAlias('@web');
            } else {
                $asset = Yii::getAlias($actualAsset);
                $baseUrl = $this->baseUrl;
            }
        } else {
            $baseUrl = $bundle->baseUrl;
        }

        if (!Url::isRelative($asset) || strncmp($asset, '/', 1) === 0) {
            return $asset;
        }

        return "$baseUrl/$asset";
    }
}
