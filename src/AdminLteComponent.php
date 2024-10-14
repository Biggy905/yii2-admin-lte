<?php

declare(strict_types=1);

namespace applications\adminlte\ViewComponent;

use applications\adminlte\ViewComponent\builders\AdminLteBuilderInterface;
use yii\base\Component;


final class AdminLteComponent extends Component
{
    private AdminLteBuilderInterface $builder;

    public function builder(AdminLteBuilderInterface $builder): AdminLteComponent
    {
        $this->builder = $builder;

        return $this;
    }

    public function render(): string
    {
        return $this->builder->build();
    }
}
