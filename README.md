LifterLMS REST API
==================

[![CircleCI](https://circleci.com/gh/gocodebox/lifterlms-rest.svg?style=svg)](https://circleci.com/gh/gocodebox/lifterlms-rest)
[![Maintainability](https://api.codeclimate.com/v1/badges/e284255ac949d5764421/maintainability)](https://codeclimate.com/github/gocodebox/lifterlms-rest/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/e284255ac949d5764421/test_coverage)](https://codeclimate.com/github/gocodebox/lifterlms-rest/test_coverage)

A REST API feature plugin for [LifterLMS](https://github.com/gocodebox/lifterlms).

## Specification

The LifterLMS REST API follows the [OpenAPI Specification (Version 3.0.0)](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md).


## Building & Developing REST API Doc spec

This repos

### Install

1. Install [Node JS](https://nodejs.org/)
2. Clone repo and run `npm install` in the repo root

### Usage

#### `npm start`
Starts the development server.

#### `npm run build`
Bundles the spec and prepares web_deploy folder with static assets.

#### `npm test`
Validates the spec.

#### `npm run gh-pages`
Deploys docs to GitHub Pages. You don't need to run it manually if you have Travis CI configured.
