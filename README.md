# Sentry.io integration for SilverStripe

[![Build Status](https://api.travis-ci.org/phptek/silverstripe-sentry.svg?branch=master)](https://travis-ci.org/phptek/silverstripe-sentry)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phptek/silverstripe-sentry/?branch=master)
[![License](https://poser.pugx.org/phptek/sentry/license.svg)](https://github.com/phptek/silverstripe-sentry/blob/master/LICENSE.md)

[Sentry](https://sentry.io) is an error and exception aggregation service. It takes your application's errors and stores them for later analysis and debugging. 

Imagine this: You see exceptions before your client does. This means the error > report > debug > patch > deploy cycle is the most efficient it can possibly be.

This module binds Sentry.io and locally-hosted Sentry installations, to the error & exception handler of SilverStripe. If you've used systems like 
[RayGun](https://raygun.com), [Rollbar](https://rollbar.com), [AirBrake](https://airbrake.io/) and [BugSnag](https://www.bugsnag.com/) before, you'll know roughly what to expect.

## Requirements

### SilverStripe 4

 * PHP5.6+, <7.2
 * SilverStripe v4.0.0-rc1+

### SilverStripe 3

 * PHP5.4+
 * SilverStripe v3.1.0+ < 4.0

## Setup

Add the Composer package as a dependency to your project:

	composer require phptek/sentry: 1.0

Configure your application or site with the Sentry DSN into your project's YML config:

### SilverStripe 4

    PhpTek\Sentry\Adaptor\SentryClientAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44

### SilverStripe 3

    phptek\Sentry\Adaptor\SentryClientAdaptor:
      opts:
        # Example DSN only. Obviously you'll need to setup your own Sentry "Project"
        dsn: http://deacdf9dfedb24ccdce1b90017b39dca:deacdf9dfedb24ccdce1b90017b39dca@sentry.mydomain.nz/44

## Usage

Sentry is normally setup once in your project's `_config.php` as follows, but see the [usage docs](docs/usage.md) for more detail and options.

### SilverStripe 3

    SS_Log::add_writer(\phptek\Sentry\SentryLogWriter::factory(), SS_Log::ERR, '<=');

### SilverStripe 4

    SS_Log::add_writer(\PhpTek\Sentry\Log\SentryLogWriter::factory(), SS_Log::ERR, '<=');

## TODO

See the [TODO docs](docs/todo.md) for more.
