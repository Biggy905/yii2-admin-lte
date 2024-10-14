<?php

namespace applications\adminlte\ViewComponent\components;

final class Tag
{
    public string $tag;
    public bool $isClosingTag = false;
    private array $closingTagList = [
        'area',
        'base',
        'br',
        'col',
        'command',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    public function __construct(
        string $tag
    ) {
        foreach ($this->closingTagList as $closingTag) {
            if ($closingTag === $tag) {
                $this->isClosingTag = true;
            }
        }

        $this->tag = $tag;
    }
}
