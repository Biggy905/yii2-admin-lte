<?php

namespace applications\adminlte\ViewComponent\components;

use applications\adminlte\ViewComponent\builders\interfaces\AdminLteRenderableInterface;

final class TagRenderable implements AdminLteRenderableInterface
{
    public Tag $tag;
    public array $attributes = [];
    public array $content = [];
    private string $renderable = '';

    public function clear(): self
    {
        $this->tag = new Tag('');
        $this->attributes = [];
        $this->content = [];
        $this->renderable = '';

        return $this;
    }

    public function addTag(Tag $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function addAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function addContent(...$content): self
    {
        $this->content = $content;

        return $this;
    }

    public function render(): string
    {
        $tag = !empty($this->tag->tag) ? '<' . $this->tag->tag : '';

        $string = '';
        foreach ($this->attributes as $attribute => $value) {
            if (is_string($value)) {
                $string .= ' ' . $attribute . '="' . $value . '"';
            } elseif (is_array($value)) {
                $string .= $attribute . '="';
                foreach ($value as $val) {
                    $string .= $val . ' ';
                }
                $string .= '"';
            }
        }

        $close = match ($this->tag->isClosingTag) {
            true => !empty($this->tag->tag) ?  '/>' : '',
            default => !empty($this->tag->tag) ?  '>' : '',
        };

        $tag .= $string . $close;

        $this->renderable = $tag;
        if (!$this->tag->isClosingTag) {
            $closeTag = '</' . $this->tag->tag . '>';

            $content = '';
            foreach ($this->content as $value) {
                $content .= $value;
            }
            $this->renderable = $tag . $content . $closeTag;
        }

        return $this->renderable;
    }
}
