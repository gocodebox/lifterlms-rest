LifterLMS REST API
==================

[![CircleCI](https://circleci.com/gh/gocodebox/lifterlms-rest.svg?style=svg)](https://circleci.com/gh/gocodebox/lifterlms-rest)
[![Maintainability](https://api.codeclimate.com/v1/badges/e284255ac949d5764421/maintainability)](https://codeclimate.com/github/gocodebox/lifterlms-rest/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/e284255ac949d5764421/test_coverage)](https://codeclimate.com/github/gocodebox/lifterlms-rest/test_coverage)

A REST API feature plugin for [LifterLMS](https://github.com/gocodebox/lifterlms).

**This specification (and repository) is currently under construction. It is not yet a functional API.**

## Contributing [![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](.github/CONTRIBUTING.md)

We are looking for both API specification designers and developers interested in contributing. The best way to contribute is to join us in `#developers` on the official [LifterLMS Slack community](https://lifterlms.com/slack).

## Specification & Documentation

The LifterLMS REST API follows the [OpenAPI Specification (Version 3.0.0)](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md).

REST API documentation is available at [gocodebox.github.io/lifterlms-rest/](https://gocodebox.github.io/lifterlms-rest/).

The full OpenAPI spec can be downloaded in [json](https://gocodebox.github.io/lifterlms-rest/openapi.json) or [yaml](https://gocodebox.github.io/lifterlms-rest/openapi.yaml) formats.

## Building & Developing REST API Doc spec

This repo uses [ReDoc](https://github.com/Rebilly/ReDoc).

To build the docs locally for development:

+ `npm install` in the repo root.
+ `npm start`: Starts the development server.
+ `npm run build`: Bundles the spec and prepares web_deploy folder with static assets.
+ `npm test`: Validates the spec.
+ `npm run gh-pages`: Deploys docs to GitHub Pages. You don't need to run it manually if you have Travis CI configured.
