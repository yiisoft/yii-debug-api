<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://github.com/yiisoft.png" height="100px">
    </a>
    <h1 align="center">Yii debug API</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/yii-debug-api/v/stable.png)](https://packagist.org/packages/yiisoft/yii-debug-api)
[![Total Downloads](https://poser.pugx.org/yiisoft/yii-debug-api/downloads.png)](https://packagist.org/packages/yiisoft/yii-debug-api)
[![Build status](https://github.com/yiisoft/yii-debug-api/workflows/build/badge.svg)](https://github.com/yiisoft/yii-debug-api/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/yii-debug-api/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-debug-api/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/yii-debug-api/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/yii-debug-api/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-debug-api%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/yii-debug-api/master)
[![static analysis](https://github.com/yiisoft/yii-debug-api/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/yii-debug-api/actions?query=workflow%3A%22static+analysis%22)

This extension provides an API for [Yii Debug](https://github.com/yiisoft/yii-debug) extension.

## Requirements

- PHP 7.4 or higher.

## Installation

Add the package to your application:

```
composer require yiisoft/yii-debug-api
```

## General usage

Routes will be registered automatically within Yii application router.
You can check if everything is OK by going to `/debug` URL.

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## License

The Yii Debug API is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
