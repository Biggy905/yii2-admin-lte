<?php

namespace applications\adminlte\ViewComponent\builders\structures;

use applications\adminlte\ViewComponent\components\interfaces\AdminLteEventInterface;
use applications\adminlte\ViewComponent\events\AdminLteEventHandler;
use applications\adminlte\ViewComponent\events\AdminLteEventComponent;

final class AdminLteContent
{
    private array $content;
    private array $contentBodyBefore;
    private array $contentBodyAfter;
    private string $renderable;

    public function __construct() {}

    public function addContent(string ...$content): AdminLteContent
    {
        $this->content = $content;

        return $this;
    }

    public function addBodyBefore(string ...$content): AdminLteContent
    {
        $this->contentBodyBefore = $content;

        return $this;
    }

    public function addBodyAfter(string ...$content): AdminLteContent
    {
        $this->contentBodyAfter = $content;

        return $this;
    }

    public function render(): string
    {
        $content = '';

        $event = new AdminLteEventHandler();
        $component = new AdminLteEventComponent();

        if (!empty($this->contentBodyBefore)) {
            $component->on(
                AdminLteEventInterface::EVENT_BODY_BEFORE,
                [$event, AdminLteEventInterface::EVENT_BODY_BEFORE],
                $this,
            );
            $component->trigger($event::EVENT_BODY_BEFORE, $event);

            $content .= implode('', $this->contentBodyBefore);
        }

        foreach ($this->content as $value) {
            $content .= $value;
        }

        if (!empty($this->contentBodyAfter)) {
            $component->on(
                AdminLteEventInterface::EVENT_BODY_AFTER,
                [$event, AdminLteEventInterface::EVENT_BODY_AFTER],
                $this,
            );
            $component->trigger($event::EVENT_BODY_AFTER, $event);

            $content .= implode('', $this->contentBodyAfter);
        }

        return $content;
    }
}
