<?php

namespace applications\adminlte\ViewComponent\builders;

use applications\adminlte\ViewComponent\builders\structures\AdminLteBody;
use applications\adminlte\ViewComponent\builders\structures\AdminLteContent;
use applications\adminlte\ViewComponent\builders\structures\AdminLteHead;
use applications\adminlte\ViewComponent\builders\structures\AdminLteHtml;

interface AdminLteBuilderInterface
{
    public function build(): string;

    public function buildHtml(AdminLteHtml $adminLteHtml): AdminLteBuilder;

    public function buildHead(AdminLteHead $adminLteHead): AdminLteBuilder;

    public function buildBody(AdminLteBody $adminLteBody): AdminLteBuilder;

    public function buildContent(AdminLteContent $adminLteContent): AdminLteBuilder;
}
