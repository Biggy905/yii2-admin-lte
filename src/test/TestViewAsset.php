<?php

namespace applications\adminlte\ViewComponent\test;

use yii\base\Component;
use yii\base\DynamicContentAwareInterface;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\ViewContextInterface;
use yii\base\ViewEvent;
use yii\base\ViewNotFoundException;
use yii\base\ViewRenderer;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;
use yii\widgets\Block;
use yii\widgets\ContentDecorator;
use yii\widgets\FragmentCache;
use Yii;

final class TestViewAsset extends Component implements DynamicContentAwareInterface
{
    const EVENT_BEGIN_PAGE = 'beginPage';
    const EVENT_END_PAGE = 'endPage';
    const EVENT_BEFORE_RENDER = 'beforeRender';
    const EVENT_AFTER_RENDER = 'afterRender';
    public $context;
    public $params = [];
    public $renderers;
    public $defaultExtension = 'php';
    public $theme;
    public $blocks;
    public $cacheStack = [];
    public $dynamicPlaceholders = [];

    private $_viewFiles = [];
    const EVENT_BEGIN_BODY = 'beginBody';
    const EVENT_END_BODY = 'endBody';
    const POS_HEAD = 1;
    const POS_BEGIN = 2;
    const POS_END = 3;
    const POS_READY = 4;
    const POS_LOAD = 5;
    const PH_HEAD = '<![CDATA[YII-BLOCK-HEAD]]>';
    const PH_BODY_BEGIN = '<![CDATA[YII-BLOCK-BODY-BEGIN]]>';
    const PH_BODY_END = '<![CDATA[YII-BLOCK-BODY-END]]>';

    public $assetBundles = [];
    public $title;
    public $metaTags = [];
    public $linkTags = [];
    public $css = [];
    public $cssFiles = [];
    public $js = [];
    public $jsFiles = [];

    public $scriptOptions = [];

    private $_assetManager;

    protected $isPageEnded = false;

    /**
     * Initializes the view component.
     */
    public function init()
    {
        parent::init();
        if (is_array($this->theme)) {
            if (!isset($this->theme['class'])) {
                $this->theme['class'] = 'yii\base\Theme';
            }
            $this->theme = Yii::createObject($this->theme);
        } elseif (is_string($this->theme)) {
            $this->theme = Yii::createObject($this->theme);
        }
    }

    public function render($view, $params = [], $context = null)
    {
        $viewFile = $this->findViewFile($view, $context);
        return $this->renderFile($viewFile, $params, $context);
    }

    protected function findViewFile($view, $context = null)
    {
        if (strncmp($view, '@', 1) === 0) {
            // e.g. "@app/views/main"
            $file = Yii::getAlias($view);
        } elseif (strncmp($view, '//', 2) === 0) {
            // e.g. "//layouts/main"
            $file = Yii::$app->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
        } elseif (strncmp($view, '/', 1) === 0) {
            // e.g. "/site/index"
            if (Yii::$app->controller !== null) {
                $file = Yii::$app->controller->module->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
            } else {
                throw new InvalidCallException("Unable to locate view file for view '$view': no active controller.");
            }
        } elseif ($context instanceof ViewContextInterface) {
            $file = $context->getViewPath() . DIRECTORY_SEPARATOR . $view;
        } elseif (($currentViewFile = $this->getRequestedViewFile()) !== false) {
            $file = dirname($currentViewFile) . DIRECTORY_SEPARATOR . $view;
        } else {
            throw new InvalidCallException("Unable to resolve view file for view '$view': no active view context.");
        }

        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }
        $path = $file . '.' . $this->defaultExtension;
        if ($this->defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }

        return $path;
    }

    public function renderFile($viewFile, $params = [], $context = null)
    {
        $viewFile = $requestedFile = Yii::getAlias($viewFile);

        if ($this->theme !== null) {
            $viewFile = $this->theme->applyTo($viewFile);
        }
        if (is_file($viewFile)) {
            $viewFile = FileHelper::localize($viewFile);
        } else {
            throw new ViewNotFoundException("The view file does not exist: $viewFile");
        }

        $oldContext = $this->context;
        if ($context !== null) {
            $this->context = $context;
        }
        $output = '';
        $this->_viewFiles[] = [
            'resolved' => $viewFile,
            'requested' => $requestedFile
        ];

        if ($this->beforeRender($viewFile, $params)) {
            Yii::debug("Rendering view file: $viewFile", __METHOD__);
            $ext = pathinfo($viewFile, PATHINFO_EXTENSION);
            if (isset($this->renderers[$ext])) {
                if (is_array($this->renderers[$ext]) || is_string($this->renderers[$ext])) {
                    $this->renderers[$ext] = Yii::createObject($this->renderers[$ext]);
                }
                /* @var $renderer ViewRenderer */
                $renderer = $this->renderers[$ext];
                $output = $renderer->render($this, $viewFile, $params);
            } else {
                $output = $this->renderPhpFile($viewFile, $params);
            }
            $this->afterRender($viewFile, $params, $output);
        }

        array_pop($this->_viewFiles);
        $this->context = $oldContext;

        return $output;
    }

    public function getViewFile()
    {
        return empty($this->_viewFiles) ? false : end($this->_viewFiles)['resolved'];
    }

    protected function getRequestedViewFile()
    {
        return empty($this->_viewFiles) ? false : end($this->_viewFiles)['requested'];
    }

    public function beforeRender($viewFile, $params)
    {
        $event = new ViewEvent([
            'viewFile' => $viewFile,
            'params' => $params,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER, $event);

        return $event->isValid;
    }

    public function afterRender($viewFile, $params, &$output)
    {
        if ($this->hasEventHandlers(self::EVENT_AFTER_RENDER)) {
            $event = new ViewEvent([
                'viewFile' => $viewFile,
                'params' => $params,
            ]);
            $event->output =& $output;

            $this->trigger(self::EVENT_AFTER_RENDER, $event);
        }
    }

    public function renderPhpFile($_file_, $_params_ = [])
    {
        $_obInitialLevel_ = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        extract($_params_, EXTR_OVERWRITE);
        try {
            require $_file_;
            return ob_get_clean();
        } catch (\Exception $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        } catch (\Throwable $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        }
    }

    public function renderDynamic($statements)
    {
        if (!empty($this->cacheStack)) {
            $n = count($this->dynamicPlaceholders);
            $placeholder = "<![CDATA[YII-DYNAMIC-$n]]>";
            $this->addDynamicPlaceholder($placeholder, $statements);

            return $placeholder;
        }

        return $this->evaluateDynamicContent($statements);
    }

    public function getDynamicPlaceholders()
    {
        return $this->dynamicPlaceholders;
    }

    public function setDynamicPlaceholders($placeholders)
    {
        $this->dynamicPlaceholders = $placeholders;
    }

    public function addDynamicPlaceholder($placeholder, $statements)
    {
        foreach ($this->cacheStack as $cache) {
            if ($cache instanceof DynamicContentAwareInterface) {
                $cache->addDynamicPlaceholder($placeholder, $statements);
            } else {
                // TODO: Remove in 2.1
                $cache->dynamicPlaceholders[$placeholder] = $statements;
            }
        }
        $this->dynamicPlaceholders[$placeholder] = $statements;
    }

    public function evaluateDynamicContent($statements)
    {
        return eval($statements);
    }

    public function getDynamicContents()
    {
        return $this->cacheStack;
    }

    public function pushDynamicContent(DynamicContentAwareInterface $instance)
    {
        $this->cacheStack[] = $instance;
    }

    public function popDynamicContent()
    {
        array_pop($this->cacheStack);
    }

    public function beginBlock($id, $renderInPlace = false)
    {
        return Block::begin([
            'id' => $id,
            'renderInPlace' => $renderInPlace,
            'view' => $this,
        ]);
    }

    public function endBlock()
    {
        Block::end();
    }

    public function beginContent($viewFile, $params = [])
    {
        return ContentDecorator::begin([
            'viewFile' => $viewFile,
            'params' => $params,
            'view' => $this,
        ]);
    }

    public function endContent()
    {
        ContentDecorator::end();
    }

    public function beginCache($id, $properties = [])
    {
        $properties['id'] = $id;
        $properties['view'] = $this;
        /* @var $cache FragmentCache */
        $cache = FragmentCache::begin($properties);
        if ($cache->getCachedContent() !== false) {
            $this->endCache();

            return false;
        }

        return true;
    }

    public function endCache()
    {
        FragmentCache::end();
    }

    public function beginPage()
    {
        ob_start();
        ob_implicit_flush(false);

        $this->trigger(self::EVENT_BEGIN_PAGE);
    }

    public function head()
    {
        echo self::PH_HEAD;
    }

    public function beginBody()
    {
        echo self::PH_BODY_BEGIN;
        $this->trigger(self::EVENT_BEGIN_BODY);
    }

    public function endBody()
    {
        $this->trigger(self::EVENT_END_BODY);
        echo self::PH_BODY_END;

        foreach (array_keys($this->assetBundles) as $bundle) {
            $this->registerAssetFiles($bundle);
        }
    }

    public function endPage($ajaxMode = false)
    {
        $this->trigger(self::EVENT_END_PAGE);

        $this->isPageEnded = true;

        $content = ob_get_clean();

        echo strtr($content, [
            self::PH_HEAD => $this->renderHeadHtml(),
            self::PH_BODY_BEGIN => $this->renderBodyBeginHtml(),
            self::PH_BODY_END => $this->renderBodyEndHtml($ajaxMode),
        ]);

        $this->clear();
    }

    public function renderAjax($view, $params = [], $context = null)
    {
        $viewFile = $this->findViewFile($view, $context);

        ob_start();
        ob_implicit_flush(false);

        $this->beginPage();
        $this->head();
        $this->beginBody();
        echo $this->renderFile($viewFile, $params, $context);
        $this->endBody();
        $this->endPage(true);

        return ob_get_clean();
    }

    public function getAssetManager()
    {
        return new TestAssetManager();
    }

    public function setAssetManager($value)
    {
        $this->_assetManager = $value;
    }

    public function clear()
    {
        $this->metaTags = [];
        $this->linkTags = [];
        $this->css = [];
        $this->cssFiles = [];
        $this->js = [];
        $this->jsFiles = [];
        $this->assetBundles = [];
    }

    protected function registerAssetFiles($name)
    {
        if (!isset($this->assetBundles[$name])) {
            return;
        }
        $bundle = $this->assetBundles[$name];
        if ($bundle) {
            foreach ($bundle->depends as $dep) {
                $this->registerAssetFiles($dep);
            }
            $bundle->registerAssetFiles($this);
        }
        unset($this->assetBundles[$name]);
    }

    public function registerAssetBundle($name, $position = null)
    {
        if (!isset($this->assetBundles[$name])) {
            $am = $this->getAssetManager();
            $bundle = $am->getBundle($name);
            $this->assetBundles[$name] = false;
            // register dependencies
            $pos = isset($bundle->jsOptions['position']) ? $bundle->jsOptions['position'] : null;
            foreach ($bundle->depends as $dep) {
                $this->registerAssetBundle($dep, $pos);
            }
            $this->assetBundles[$name] = $bundle;
        } elseif ($this->assetBundles[$name] === false) {
            throw new InvalidConfigException("A circular dependency is detected for bundle '$name'.");
        } else {
            $bundle = $this->assetBundles[$name];
        }

        if ($position !== null) {
            $pos = isset($bundle->jsOptions['position']) ? $bundle->jsOptions['position'] : null;
            if ($pos === null) {
                $bundle->jsOptions['position'] = $pos = $position;
            } elseif ($pos > $position) {
                throw new InvalidConfigException("An asset bundle that depends on '$name' has a higher javascript file position configured than '$name'.");
            }
            // update position for all dependencies
            foreach ($bundle->depends as $dep) {
                $this->registerAssetBundle($dep, $pos);
            }
        }

        return $bundle;
    }

    public function registerMetaTag($options, $key = null)
    {
        if ($key === null) {
            $this->metaTags[] = Html::tag('meta', '', $options);
        } else {
            $this->metaTags[$key] = Html::tag('meta', '', $options);
        }
    }

    public function registerCsrfMetaTags()
    {
        $this->metaTags['csrf_meta_tags'] = $this->renderDynamic('return yii\helpers\Html::csrfMetaTags();');
    }

    public function registerLinkTag($options, $key = null)
    {
        if ($key === null) {
            $this->linkTags[] = Html::tag('link', '', $options);
        } else {
            $this->linkTags[$key] = Html::tag('link', '', $options);
        }
    }

    public function registerCss($css, $options = [], $key = null)
    {
        $key = $key ?: md5($css);
        $this->css[$key] = Html::style($css, $options);
    }

    public function registerCssFile($url, $options = [], $key = null)
    {
        $this->registerFile('css', $url, $options, $key);
    }

    public function registerJs($js, $position = self::POS_READY, $key = null)
    {
        $key = $key ?: md5($js);
        $this->js[$position][$key] = $js;
        if ($position === self::POS_READY || $position === self::POS_LOAD) {
            JqueryAsset::register($this);
        }
    }

    private function registerFile($type, $url, $options = [], $key = null)
    {
        $url = Yii::getAlias($url);
        $key = $key ?: $url;
        $depends = ArrayHelper::remove($options, 'depends', []);
        $originalOptions = $options;
        $position = ArrayHelper::remove($options, 'position', self::POS_END);

        try {
            $assetManagerAppendTimestamp = $this->getAssetManager()->appendTimestamp;
        } catch (InvalidConfigException $e) {
            $depends = null; // the AssetManager is not available
            $assetManagerAppendTimestamp = false;
        }
        $appendTimestamp = ArrayHelper::remove($options, 'appendTimestamp', $assetManagerAppendTimestamp);

        if ($this->isPageEnded) {
            Yii::warning('You\'re trying to register a file after View::endPage() has been called.');
        }

        if (empty($depends)) {
            // register directly without AssetManager
            if ($appendTimestamp && Url::isRelative($url)) {
                $prefix = Yii::getAlias('@web');
                $prefixLength = strlen($prefix);
                $trimmedUrl = ltrim((substr($url, 0, $prefixLength) === $prefix) ? substr($url, $prefixLength) : $url, '/');
                $timestamp = @filemtime(Yii::getAlias('@webroot/' . $trimmedUrl, false));
                if ($timestamp > 0) {
                    $url = $timestamp ? "$url?v=$timestamp" : $url;
                }
            }
            if ($type === 'js') {
                $this->jsFiles[$position][$key] = Html::jsFile($url, $options);
            } else {
                $this->cssFiles[$key] = Html::cssFile($url, $options);
            }
        } else {
            $this->getAssetManager()->bundles[$key] = Yii::createObject([
                'class' => AssetBundle::className(),
                'baseUrl' => '',
                'basePath' => '@webroot',
                (string)$type => [ArrayHelper::merge([!Url::isRelative($url) ? $url : ltrim($url, '/')], $originalOptions)],
                "{$type}Options" => $options,
                'depends' => (array)$depends,
            ]);
            $this->registerAssetBundle($key);
        }
    }

    public function registerJsFile($url, $options = [], $key = null)
    {
        $this->registerFile('js', $url, $options, $key);
    }

    public function registerJsVar($name, $value, $position = self::POS_HEAD)
    {
        $js = sprintf('var %s = %s;', $name, \yii\helpers\Json::htmlEncode($value));
        $this->registerJs($js, $position, $name);
    }

    protected function renderHeadHtml()
    {
        $lines = [];
        if (!empty($this->metaTags)) {
            $lines[] = implode("\n", $this->metaTags);
        }

        if (!empty($this->linkTags)) {
            $lines[] = implode("\n", $this->linkTags);
        }
        if (!empty($this->cssFiles)) {
            $lines[] = implode("\n", $this->cssFiles);
        }
        if (!empty($this->css)) {
            $lines[] = implode("\n", $this->css);
        }
        if (!empty($this->jsFiles[self::POS_HEAD])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_HEAD]);
        }
        if (!empty($this->js[self::POS_HEAD])) {
            $lines[] = Html::script(implode("\n", $this->js[self::POS_HEAD]));
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    protected function renderBodyBeginHtml()
    {
        $lines = [];
        if (!empty($this->jsFiles[self::POS_BEGIN])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_BEGIN]);
        }
        if (!empty($this->js[self::POS_BEGIN])) {
            $lines[] = Html::script(implode("\n", $this->js[self::POS_BEGIN]));
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    protected function renderBodyEndHtml($ajaxMode)
    {
        $lines = [];

        if (!empty($this->jsFiles[self::POS_END])) {
            $lines[] = implode("\n", $this->jsFiles[self::POS_END]);
        }

        if ($ajaxMode) {
            $scripts = [];
            if (!empty($this->js[self::POS_END])) {
                $scripts[] = implode("\n", $this->js[self::POS_END]);
            }
            if (!empty($this->js[self::POS_READY])) {
                $scripts[] = implode("\n", $this->js[self::POS_READY]);
            }
            if (!empty($this->js[self::POS_LOAD])) {
                $scripts[] = implode("\n", $this->js[self::POS_LOAD]);
            }
            if (!empty($scripts)) {
                $lines[] = Html::script(implode("\n", $scripts));
            }
        } else {
            if (!empty($this->js[self::POS_END])) {
                $lines[] = Html::script(implode("\n", $this->js[self::POS_END]));
            }
            if (!empty($this->js[self::POS_READY])) {
                $js = "jQuery(function ($) {\n" . implode("\n", $this->js[self::POS_READY]) . "\n});";
                $lines[] = Html::script($js);
            }
            if (!empty($this->js[self::POS_LOAD])) {
                $js = "jQuery(window).on('load', function () {\n" . implode("\n", $this->js[self::POS_LOAD]) . "\n});";
                $lines[] = Html::script($js);
            }
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }
}
