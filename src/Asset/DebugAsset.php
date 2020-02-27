<?php

namespace Yiisoft\Yii\Debug\Viewer\Asset;

use Yiisoft\Assets\AssetBundle;

final class DebugAsset extends AssetBundle
{
    public ?string $sourcePath = __DIR__ . '/../../assets/';
    public ?string $basePath = '@public';

    public ?string $baseUrl = '@web';
    public array $css = [
        'css/main.css',
        'css/toolbar.css',
    ];
    public array $js = [
        'js/toolbar.js',
        'js/bs4-native.min.js',
    ];
    public array $jsOptions = [
        'position' => 1,
    ];
}
