# Drupal.org packages.json generator

This project is a small console application which generates [`packages.json` files](https://getcomposer.org/doc/05-repositories.md#composer) from projects on [Drupal.org](https://drupal.org/download) using the updates.drupal.org API.

The goal is to allow Drupal modules and themes to be used as dependencies in [`composer.json`](https://getcomposer.org/doc/01-basic-usage.md#composer-json-project-setup) for Composer-based Drupal projects.

## Performance

The project currently splits the `packages.json` file into multiple files using [includes](https://getcomposer.org/doc/05-repositories.md#includes).

This is not optimal for the number of projects on Drupal.org which rivals the number of packages and versions on Packagist. 

Instead the project should use [provider-includes and providers-url](https://getcomposer.org/doc/05-repositories.md#provider-includes-and-providers-url).

## Status

This is work in progress. Consider it a prototype for now.




