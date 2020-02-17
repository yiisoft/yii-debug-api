<?php

namespace Yiisoft\Yii\Debug\Viewer;

interface PanelInterface
{
    public static function name(): string;

    public function render(): string;
}
