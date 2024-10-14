<?php

namespace applications\adminlte\ViewComponent\events;

use applications\adminlte\ViewComponent\components\interfaces\AdminLteEventInterface;
use applications\adminlte\ViewComponent\components\Tag;
use applications\adminlte\ViewComponent\components\TagRenderable;
use yii\base\Event;

final class AdminLteEventHandler extends Event implements AdminLteEventInterface
{
    public function head(AdminLteEventHandler $event): void
    {
        $adminLteHead = $event->data;

        $tagRenderable = new TagRenderable();

        $content = $tagRenderable
            ->clear()
            ->addTag(
                new Tag('script')
            )->addAttributes(
                [
                    'src' => 'asdasd',
                ]
            )->render();

        $adminLteHead->addHead($content);
    }

    public function beforeBody(AdminLteEventHandler $event): void
    {
        $adminLteContent = $event->data;

        $tagRenderable = new TagRenderable();

        $content = $tagRenderable
            ->clear()
            ->addTag(
                new Tag('script')
            )->addAttributes(
                [
                    'src' => 'asdasd',
                ]
            )->render();

        $adminLteContent->addBodyBefore($content);
    }

    public static function afterBody(AdminLteEventHandler $event): void
    {
        $adminLteContent = $event->data;

        $tagRenderable = new TagRenderable();

        $content = $tagRenderable
            ->clear()
            ->addTag(
                new Tag('script')
            )->addAttributes(
                [
                    'src' => '/src/sad/dfhdfg.js',
                ]
            )->render();

        $adminLteContent->addBodyAfter($content);
    }
}
