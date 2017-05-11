# flysystem-dropbox

[![StyleCI](https://styleci.io/repos/90866593/shield?style=flat)](https://styleci.io/repos/90866593)
[![Build Status](https://img.shields.io/travis/srmklive/flysystem-dropbox-v2/master.svg?style=flat-square)](https://travis-ci.org/srmklive/flysystem-dropbox-v2)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/srmklive/flysystem-dropbox-v2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/srmklive/flysystem-dropbox-v2/?branch=master)

This package contains a [Flysystem](https://flysystem.thephpleague.com/) adapter for Dropbox API v2.

# Credits

This package is direct port of [Flysystem Dropbox](https://github.com/spatie/flysystem-dropbox) by [Freek Van der Herten](https://github.com/freekmurze) with support for PHP 5.6. 

# Installation

You can install the package via composer:

``` bash
composer require srmklive/flysystem-dropbox-v2
```

## Usage

The first thing you need to do is get an authorization token at Dropbox. A token can be generated in the [App Console](https://www.dropbox.com/developers/apps) for any Dropbox API app. You'll find more info at [the Dropbox Developer Blog](https://blogs.dropbox.com/developers/2014/05/generate-an-access-token-for-your-own-account/).
``` php
use League\Flysystem\Filesystem;
use Srmklive\Dropbox\Client\DropboxClient;
use Srmklive\Dropbox\Adapter\DropboxAdapter;

$client = new Client($authorizationToken);

$adapter = new DropboxAdapter($client);

$filesystem = new Filesystem($adapter);
```

# Testing

``` bash
$ composer test
```