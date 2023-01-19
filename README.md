# OpenEuropa Search

[![Build Status](https://drone.fpfis.eu/api/badges/openeuropa/oe_search/status.svg?branch=2.x)](https://drone.fpfis.eu/openeuropa/oe_search)

The OpenEuropa Search module integrates [Europa Search Client](https://github.com/openeuropa/europa-search-client) with [Search API](https://www.drupal.org/project/search_api).

Europa Search is the corporate search engine for the European Commission.

### Future developments planned

- File ingestion
- Search: simple, advanced, faceted
- Integration with translation

## Limitations

- Only content entities can be ingested.
- Ingested entities should expose a canonical URL. Alternatively, third-party code may alter the document being ingested and provide an arbitrary URL. See the **API** section below.

## Requirements

* PHP 8.0 or newer.
* Drupal >= 9.4.
* [Search API](https://www.drupal.org/project/search_api) Drupal module 1.19 or newer.
* [Europa Search Client](https://github.com/openeuropa/europa-search-client) library.

For a full list of dependencies, please check the [composer.json](composer.json) file.

## Development setup

You can build the development site by running the following steps:

* Install the Composer dependencies:

```bash
composer install
```

A post command hook (`drupal:site-setup`) is triggered automatically after `composer install`.
It will make sure that the necessary symlinks are properly setup in the development site.
It will also perform token substitution in development configuration files such as `behat.yml.dist`.

* Install test site by running:

```bash
./vendor/bin/run drupal:site-install
```

Your test site will be available at [http://localhost:8080/build](http://localhost:8080/build).

**Please note:** project files and directories are symlinked within the test site by using the
[OpenEuropa Task Runner's Drupal project symlink](https://github.com/openeuropa/task-runner-drupal-project-symlink) command.

If you add a new file or directory in the root of the project, you need to re-run `drupal:site-setup` in order to make
sure they are be correctly symlinked.

If you don't want to re-run a full site setup for that, you can simply run:

```
$ ./vendor/bin/run drupal:symlink-project
```

### Using Docker Compose

Alternatively, you can build a development site using [Docker](https://www.docker.com/get-docker) and
[Docker Compose](https://docs.docker.com/compose/) with the provided configuration.

Docker provides the necessary services and tools such as a web server and a database server to get the site running,
regardless of your local host configuration.

#### Requirements:

- [Docker](https://www.docker.com/get-docker)
- [Docker Compose](https://docs.docker.com/compose/)

#### Configuration

By default, Docker Compose reads two files, a `docker-compose.yml` and an optional `docker-compose.override.yml` file.
By convention, the `docker-compose.yml` contains your base configuration and it's provided by default.
The override file, as its name implies, can contain configuration overrides for existing services or entirely new
services.
If a service is defined in both files, Docker Compose merges the configurations.

Find more information on Docker Compose extension mechanism on [the official Docker Compose documentation](https://docs.docker.com/compose/extends/).

#### Usage

To start, run:

```bash
docker-compose up
```

It's advised to not daemonize `docker-compose` so you can turn it off (`CTRL+C`) quickly when you're done working.
However, if you'd like to daemonize it, you have to add the flag `-d`:

```bash
docker-compose up -d
```

Then:

```bash
docker-compose exec web composer install
docker-compose exec web ./vendor/bin/run drupal:site-install
```

Using default configuration, the development site files should be available in the `build` directory and the development site
should be available at: [http://127.0.0.1:8080/build](http://127.0.0.1:8080/build).

#### Running the tests

To run the grumphp checks:

```bash
docker-compose exec web ./vendor/bin/grumphp run
```

To run the phpunit tests:

```bash
docker-compose exec web ./vendor/bin/phpunit
```

#### Step debugging

To enable step debugging from the command line, pass the `XDEBUG_SESSION` environment variable with any value to
the container:

```bash
docker-compose exec -e XDEBUG_SESSION=1 web <your command>
```

Please note that, starting from XDebug 3, a connection error message will be outputted in the console if the variable is
set but your client is not listening for debugging connections. The error message will cause false negatives for PHPUnit
tests.

To initiate step debugging from the browser, set the correct cookie using a browser extension or a bookmarklet
like the ones generated at https://www.jetbrains.com/phpstorm/marklets/.

## Contributing

Please read [the full documentation](https://github.com/openeuropa/openeuropa) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the available versions, see the [tags on this repository](https://github.com/openeuropa/oe_search/tags).

## API

### Alter a document being ingested (indexed)

Third party modules are able to intercept and alter the indexed document subscribing to `Drupal\oe_search\Event\DocumentCreationEvent` event:

**mymodule.service.yml**:
```yaml
services:
  mymodule.alter_indexed_doc:
    class: Drupal\mymodule\EventSubscriber\AlterIndexedDocSubscriber
    tags:
      - { name: 'event_subscriber' }
```

**src/EventSubscriber/AlterIndexedDocSubscriber.php**:
```php
<?php

namespace Drupal\mymodule\EventSubscriber;

use Drupal\oe_search\Event\DocumentCreationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AlterIndexedDocSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [DocumentCreationEvent::class => 'setReleased'];
  }

  public function setReleased(DocumentCreationEvent $event): void {
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() === 'foo') {
      $event->getDocument()->setUrl("http://example.com/{$entity->uuid()}");
    }
  }

}
```
