<?php

namespace applications\adminlte\ViewComponent\builders\structures;

use applications\adminlte\ViewComponent\assets\AdminLteAssetManager;
use applications\adminlte\ViewComponent\assets\AssetsInterface;
use applications\adminlte\ViewComponent\components\interfaces\AdminLteEventInterface;
use applications\adminlte\ViewComponent\components\Tag;
use applications\adminlte\ViewComponent\components\TagRenderable;
use applications\adminlte\ViewComponent\components\View;

final class AdminLteBody
{
    private array $attributes = [];
    private array $bundles = [];
    private AdminLteContent $adminLteContent;

    public function __construct(
        private readonly TagRenderable $tagRenderable,
        private readonly AdminLteAssetManager $assetManager,
        private readonly View $view,
    ) {
    }

    public function addAttributes(array $attributes): AdminLteBody
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function resources(AssetsInterface ...$assets): AdminLteBody
    {
        foreach ($assets as $asset) {
            $asset::register($this->view);
            $this->bundles[$asset::class] = $this->assetManager->getBundle($asset::class);
        }

        return $this;
    }

    public function bundles(array $bundles): AdminLteBody
    {
        if (!empty($this->bundles)) {
            $this->bundles += $bundles;
        } else {
            $this->bundles = $bundles;
        }

        return $this;
    }

    public function content(AdminLteContent $adminLteContent): AdminLteBody
    {
        $this->adminLteContent = $adminLteContent;

        return $this;
    }

    public function render(): string
    {
        $contentBodyBefore = '';
        if ($this->empty('bundles')) {
            foreach ($this->bundles as $bundle) {
                $url = $bundle->baseUrl . '/';
                if ($bundle->cssEvent === AdminLteEventInterface::EVENT_BODY_BEFORE) {
                    foreach ($bundle->css as $css) {
                        $contentBodyBefore .= $this->getTagRenderable(
                            'link',
                            [
                                'rel' => 'stylesheet',
                                'href' => $url . $css,
                            ]
                        );
                    }
                }

                if ($bundle->jsEvent === AdminLteEventInterface::EVENT_BODY_BEFORE) {
                    foreach ($bundle->js as $js) {
                        $contentBodyBefore .= $this->getTagRenderable(
                            'script',
                            [
                                'src' => $url . $js,
                            ]
                        );
                    }
                }
            }
        }

        $content = $this->adminLteContent->render();

        $contentBodyAfter = '';
        if ($this->empty('bundles')) {
            foreach ($this->bundles as $bundle) {
                $url = $bundle->baseUrl . '/';
                if ($bundle->cssEvent === AdminLteEventInterface::EVENT_BODY_AFTER) {
                    foreach ($bundle->css as $css) {
                        $contentBodyAfter .= $this->getTagRenderable(
                            'link',
                            [
                                'rel' => 'stylesheet',
                                'href' => $url . $css,
                            ]
                        );
                    }
                }

                if ($bundle->jsEvent === AdminLteEventInterface::EVENT_BODY_AFTER) {
                    foreach ($bundle->js as $js) {
                        $contentBodyAfter .= $this->getTagRenderable(
                            'script',
                            [
                                'src' => $url . $js,
                            ]
                        );
                    }
                }
            }
        }

        $bodyTag = $this->tagRenderable->addTag(new Tag('body'));
        if (!empty($this->attributes)) {
            $bodyTag->addAttributes($this->attributes);
        }

        return $bodyTag
            ->addContent(
                $contentBodyBefore . $content . $contentBodyAfter
            )
            ->render();
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
