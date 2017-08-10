# SilverStripe HTTP/2 Server Push

## Introduction

This module extends the page controller to specify assets which should
be pushed to the client without requiring further requests to be made. 

* Versioning and caching of assets
* Push only new/modified assets from config
* Clean and simple configuration

## Installation

This module is being actively developed. For now, you can add this module
 manually via git and run: `composer dump-autoload` to generate vendor files.

Alternatively, add the following to composer.json and run `composer install`

    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/dnadesign/silverstripe-http2serverpush.git"
        }
    ],
    "require": {
        "dnadesign/silverstripe-http2serverpush": "dev-master",
    }

The module will currently automatically extend the Page_Controller
class.

## Configuration

Add a Page configuration, with a link element extension from the [W3C] list. Then
add assets relative to your theme root.

e.g. in **mysite/_config/app.yml**

    Page:
      server_push:
        style:
          - /css/dist/production_low.min.css
          - /css/dist/production_high.min.css
        script:
          - /build/global/js/vendor/modernizr.custom.min.js

Currently this has been tested with style and script.

## To do
* Enable configuration on a page type basis.
* Add module to composer
* Test all link element extensions in the [W3C] list.

[W3C]: https://w3c.github.io/preload/#link-element-interface-extensions#x3.2-link-element-extensions
