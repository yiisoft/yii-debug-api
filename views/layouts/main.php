<?php

/**
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\View\WebView $this
 * @var \Yiisoft\Assets\AssetManager $assetManager
 * @var string $content
 * @var null|string $currentUrl
 */

$this->setCssFiles($assetManager->getCssFiles());
$this->setJsFiles($assetManager->getJsFiles());

$this->beginPage();
?><!DOCTYPE html>
<html lang="">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yii Debug Viewer</title>
    <?php $this->head() ?>
</head>
<body>
<?php
$this->beginBody();
echo $content;
$this->endBody();
?>
</body>
</html>
<?php
$this->endPage(true);
