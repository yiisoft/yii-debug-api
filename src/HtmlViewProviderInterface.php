<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api;

use Yiisoft\Yii\Debug\Collector\CollectorInterface;

interface HtmlViewProviderInterface extends CollectorInterface
{
    /**
     * Returns file path to the view file will be rendered when collector data is requested.
     * Example:
     * ```php
     * public static function getView(): string
     * {
     *     return '@views/debug/index';
     * }
     * ```
     */
    public static function getView(): string;
}
