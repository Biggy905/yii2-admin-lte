<?php

namespace applications\adminlte\ViewComponent\builders;

use applications\adminlte\ViewComponent\builders\structures\AdminLteBody;
use applications\adminlte\ViewComponent\builders\structures\AdminLteContent;
use applications\adminlte\ViewComponent\builders\structures\AdminLteHead;
use applications\adminlte\ViewComponent\builders\structures\AdminLteHtml;

abstract class AdminLteBuilder implements AdminLteBuilderInterface
{
    protected string $render = '';
    private AdminLteHtml $adminLteHtml;
    private AdminLteHead $adminLteHead;
    private AdminLteBody $adminLteBody;
    private AdminLteContent $adminLteContent;

    public function build(): string
    {
        $this->render = $this
            ->adminLteHtml
            ->head($this->adminLteHead)
            ->body(
                $this->adminLteBody,
                $this->adminLteContent,
            )
            ->render();

        return $this->render;
    }

    public function buildHtml(AdminLteHtml $adminLteHtml): AdminLteBuilder
    {
        $this->adminLteHtml = $adminLteHtml;

        return $this;
    }

    public function buildHead(AdminLteHead $adminLteHead): AdminLteBuilder
    {
        $this->adminLteHead = $adminLteHead;

        return $this;
    }

    public function buildBody(AdminLteBody $adminLteBody): AdminLteBuilder
    {
        $this->adminLteBody = $adminLteBody;

        return $this;
    }

    public function buildContent(AdminLteContent $adminLteContent): AdminLteBuilder
    {
        $this->adminLteContent = $adminLteContent;

        return $this;
    }
}
