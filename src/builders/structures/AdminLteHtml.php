<?php

namespace applications\adminlte\ViewComponent\builders\structures;

use applications\adminlte\ViewComponent\assets\AdminLteAssetManager;
use applications\adminlte\ViewComponent\components\Tag;
use applications\adminlte\ViewComponent\components\TagRenderable;
use applications\adminlte\ViewComponent\components\View;
use Yii;

final class AdminLteHtml
{
    private string $html = '';
    private AdminLteHead $head;
    private AdminLteBody $body;
    private AdminLteResources $resources;

    public function __construct(
        private readonly AdminLteAssetManager $assetManager,
        private readonly View $view,
    ) {
    }

    public function render(): string
    {
        $this->start();

        return $this->html;
    }

    public function head(AdminLteHead $adminLteHead): AdminLteHtml
    {
        $this->head = $adminLteHead;

        return $this;
    }

    public function body(
        AdminLteBody $adminLteBody,
        AdminLteContent $adminLteContent
    ): AdminLteHtml {
        $this->body = $adminLteBody->content($adminLteContent);

        return $this;
    }

    public function resources(AdminLteResources $adminLteResourses): AdminLteHtml
    {
        $this->resources = $adminLteResourses;

        $this->resources->bundles();

        return $this;
    }

    private function start(): void
    {
        $attributes = [];
        $language = Yii::$app->language ?? null;
        if (!empty($language) && is_string($language)) {
            $attributes = ['lang' => $language];
        }

        $head = $this->head;
        if (!empty($this->resources)) {
            $head->bundles($this->resources->bundles);
        }
        $contentHead = $head->render();

        $body = $this->body;
        if (!empty($this->resources->bundles)) {
            $body->bundles($this->resources->bundles);
        }
        $contentBody = $body->render();

        $this->html = (new TagRenderable())
            ->addTag(
                new Tag('html')
            )
            ->addAttributes($attributes)
            ->addContent(
                $contentHead . $contentBody
            )
            ->render();
    }
}
