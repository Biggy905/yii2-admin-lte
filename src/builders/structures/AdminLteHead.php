<?php

namespace applications\adminlte\ViewComponent\builders\structures;

use applications\adminlte\ViewComponent\assets\AdminLteAssetManager;
use applications\adminlte\ViewComponent\assets\AssetsInterface;
use applications\adminlte\ViewComponent\components\interfaces\AdminLteEventInterface;
use applications\adminlte\ViewComponent\components\Tag;
use applications\adminlte\ViewComponent\components\TagRenderable;
use applications\adminlte\ViewComponent\components\View;
use applications\adminlte\ViewComponent\events\AdminLteEventComponent;
use applications\adminlte\ViewComponent\events\AdminLteEventHandler;
use Yii;

final class AdminLteHead
{
    private string $title = '';
    private array $style = [];
    private array $meta = [];
    private array $metaCsrf = [];
    private array $css = [];
    private array $js = [];
    private array $bundles = [];
    private array $headAfter = [];

    public function __construct(
        private readonly TagRenderable $tagRenderable,
        private readonly AdminLteAssetManager $assetManager,
        private readonly View $view,
    ) {
    }

    public function render(): string
    {
        $attributes = [];

        $content = '';
        if ($this->empty('meta')) {
            foreach ($this->meta as $meta) {
                $content .= $this->getTagRenderable(
                    'meta',
                    [
                        'name' => $meta['name'],
                        'value' => $meta['value'],
                    ],
                );
            }
        }

        if ($this->empty('metaCsrf')) {
            foreach ($this->metaCsrf as $metaCsrf) {
                $content .= $this->getTagRenderable(
                    'meta',
                    $metaCsrf,
                );
            }
        }

        if ($this->empty('css')) {
            foreach ($this->css as $css) {
                $content .= $this->getTagRenderable(
                    'link',
                    [
                        'rel' => 'stylesheet',
                        'href' => $css,
                    ]
                );
            }
        }

        if ($this->empty('js')) {
            foreach ($this->js as $js) {
                $content .= $this->getTagRenderable(
                    'script',
                    [
                        'src' => $js,
                    ]
                );
            }
        }

        if ($this->empty('style')) {
            $content .= $this->getTagRenderable(
                'style',
                [],
                'style'
            );
        }

        if ($this->empty('bundles')) {
            foreach ($this->bundles as $bundle) {
                $url = $bundle->baseUrl . '/';
                if ($bundle->cssEvent === AdminLteEventInterface::EVENT_HEAD) {
                    foreach ($bundle->css as $css) {
                        $content .= $this->getTagRenderable(
                            'link',
                            [
                                'rel' => 'stylesheet',
                                'href' => $url . $css,
                            ]
                        );
                    }
                }

                if ($bundle->jsEvent === AdminLteEventInterface::EVENT_HEAD) {
                    foreach ($bundle->js as $js) {
                        $content .= $this->getTagRenderable(
                            'script',
                            [
                                'src' => $url . $js,
                            ]
                        );
                    }
                }
            }
        }

        $content .= $this->getTagRenderable(
            'title',
            [],
            'title'
        );

        return (new TagRenderable())
            ->addTag(
                new Tag('head')
            )
            ->addAttributes($attributes)
            ->addContent($content)
            ->render();
    }

    public function title(string $title = null): AdminLteHead
    {
        if (!empty($title)) {
            $this->title = $title;
        }

        return $this;
    }

    public function meta(...$meta): AdminLteHead
    {
        $this->meta = $meta;

        return $this;
    }

    public function metaCsrf(): AdminLteHead
    {
        $request = Yii::$app->getRequest();

        $this->metaCsrf = [
            [
                'name' => 'csrf-param',
                'value' => $request->csrfParam
            ],
            [
                'name' => 'csrf-token',
                'value' => $request->getCsrfToken()
            ],
        ];

        return $this;
    }

    public function css(...$css): AdminLteHead
    {
        $this->css = $css;

        return $this;
    }

    public function style(...$style): AdminLteHead
    {
        $this->style = $style;

        return $this;
    }

    public function js(...$js): AdminLteHead
    {
        $this->js = $js;

        return $this;
    }

    public function resources(AssetsInterface ...$assets): AdminLteHead
    {
        foreach ($assets as $asset) {
            $asset::register($this->view);
            $this->bundles[$asset::class] = $this->assetManager->getBundle($asset::class);
        }

        return $this;
    }

    public function getBundles(): array
    {
        return $this->bundles;
    }

    public function bundles(array $bundles): AdminLteHead
    {
        if (!empty($this->bundles)) {
            $this->bundles += $bundles;
        } else {
            $this->bundles = $bundles;
        }

        return $this;
    }

    private function empty(string $property): bool
    {
        return !empty($this->$property);
    }

    private function getTagRenderable(
        string $tag,
        array $attributes = [],
        string $property = ''
    ): string {
        $tagRenderable = $this->tagRenderable
            ->clear()
            ->addTag(
                new Tag($tag)
            );

        if (!empty($attributes)) {
            $tagRenderable->addAttributes($attributes);
        }

        if ($this->empty($property)) {
            if (is_string($this->$property)) {
                $tagRenderable->addContent($this->$property);
            } elseif (is_array($this->$property)) {
                $string = '';
                foreach ($this->$property as $value) {
                    $string .= $value;
                }

                $tagRenderable->addContent($string);
            }
        }

        return $tagRenderable->render();
    }
}
