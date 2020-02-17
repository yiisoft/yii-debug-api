<?php

namespace Yiisoft\Yii\Debug\Viewer\Asset;

use Yiisoft\Assets\AssetBundle;

class DbAsset extends AssetBundle
{
    public ?string $sourcePath = '@Yiisoft/Yii/Debug/assets';
    public array $js = [
        'js/db.js',
    ];
}
