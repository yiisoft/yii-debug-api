<?php

declare(strict_types=1);

/**
 * @var $params array
 */

use Yiisoft\Yii\Debug\Api\ApplicationState;

return [
    static function ($container) use ($params) {
        ApplicationState::$params = $params;
    },
];
