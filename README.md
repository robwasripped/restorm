[![SensioLabsInsight](https://insight.sensiolabs.com/projects/3db69428-3a60-4452-98cd-03f23f388e22/mini.png)](https://insight.sensiolabs.com/projects/3db69428-3a60-4452-98cd-03f23f388e22)
[![GitHub version](https://badge.fury.io/gh/thesalegroup%2Frestorm.svg)](https://badge.fury.io/gh/thesalegroup%2Frestorm)
[![Build Status](https://travis-ci.org/thesalegroup/restorm.svg?branch=master)](https://travis-ci.org/thesalegroup/restorm)
[![Coverage Status](https://coveralls.io/repos/github/thesalegroup/restorm/badge.svg?branch=master)](https://coveralls.io/github/thesalegroup/restorm?branch=master)

# RESTORM

A REST ORM library for persisting data via an HTTP REST API. Inspired by
Doctrine, RESTORM provides an entity manager to take care of persisting and
loading your entities to and from a REST API.


## Feature Overview

- :floppy_disk: Load and save entities from a RESTful API
- :busts_in_silhouette: Manage data from multiple APIs
- :arrows_clockwise: Manage unidirectional and bidirectional relationships 
  between entities
- :zzz: Effortlessly lazy load associated entities
- :balloon: Automated calls to paginated entities in loops
- :pencil: Scalar, objects, and embedded entity types


# Documentation Contents

1. [Installation](#installation)
2. [Quick Start](#quick-start)
3. [Basic Usage](#basic-usage)
    1. [Create Entities](#create-entities)
    2. [Create Configuration File](#create-configuration-file)
    3. [Initialize Manager](#initialize-manager)
4. Internals
    1. ["Find" Workflow](docs/Internals/Find.md)
    2. ["Persist" Workflow](docs/Internals/Persist.md)
5. References
    1. [Configuration Reference](docs/Reference/Configuration.md)


---

# Installation

To install RESTORM, run the following composer command:

```sh
composer install thesalegroup/restorm
```

If you haven't already done so, add the file autoloader from composer to your
project code:

```php
require __DIR__ . '/../vendor/autoload.php';
```


## Development Container

This repository includes a VS Code/Codespaces dev container for running the
project toolchain in a consistent PHP environment. Mago is installed in the
container image, not in the repository workspace.

The container uses PHP 8.4 because the current locked development toolchain,
including PHPUnit 13, requires it. Mago still reads `mago.toml`, so analysis
continues to target the project's PHP 8.1 code level.

After opening the repository in the container, the initial setup runs `composer
install` so PHPUnit is ready to use. If `composer.json` or `composer.lock`
changes later, rerun `composer install` inside the container before running the
tools below.

```sh
vendor/bin/phpunit
mago lint
mago analyze
```


# Quick Start

The example below can be used for reference for setting up a new project with
RESTORM. For more information read through the rest of this guide which will
link you to more documentation and references.

```PHP
<?php

// Use the composer autoloader to load in RESTORM
require __DIR__ . '/../vendor/autoload.php';

use Robwasripped\Restorm\Configuration\Configuration;
use Robwasripped\Restorm\EntityManager;
use App\Entity\Post;

// Create a new Configuration instance based on our configuration
$configPath = '../config/restorm.yml';
$configuration = Configuration::buildFromYaml($configPath);

// Create a new EntityManager from our Configuration instance
$entityManager = EntityManager::createFromConfiguration($configuration);

// Fetch all the posts from the API
$posts = $entityManager->getRepository(Post::class)->findAll();

// Loop over the collection of posts to see their data
foreach($posts as $post) {
    
    // Instances of Author are lazy loaded when you need them.
    $author = $post->getAuthor() // Returns an instance of Author

    // No request is made to the API for the author until you request
    // data that wasn't available in the initial request.

    // The following request will not trigger a new call …
    $authorId = $author->getId();

    // …whereas this one will quietly populate the rest of Author
    $authorName = $author->getName();
    
}

```


# Basic Usage

The following steps will show you a very simple usage of RESTORM for a project.
We will setup RESTORM to talk to an artificial endpoint at
`https://example.com/api` which serves a RESTful API. For this example we're
going to create a very simple set of entities that would be useful for a blog.
These will include a `Post` entity which has an `Author` entity associated with
it.

## Create Entities

To start off we'll create some classes for our entities. An entity is simply a
resource object that's managed by RESTORM. An entity has properties which are
populated from RESTful API calls as well as any other methods or logic you wish
to have in them. Let's create a simple `Post` class:

```PHP
<?php
# src/Entity/Post.php

namespace App\Entity;

class Post {

    private $id;

    private $title;

    public $content;

    private $author;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

}

```

Entities are not required to extend any interfaces or classes in RESTORM - any
class can become an entity. Notice as well that the `Post::$id`,
`Post::$title`, and `Post::$author` are all private properties - this will not
affect RESTORM's ability to manage these fields as it manages properties
through reflection.

In our `Post` entity the "ID", "title", and "content" will all be simple scalar
values from our API, but "author" is different; although this will be in
integer in the API that represent the ID of an author, we'll instead want this
property to be populated with an instance of `Author`. Before we create our
configuration to do manage this and the other fields, let's create the `Author`
class as well:

```PHP
<?php
# src/Entity/Author.php

namespace App\Entity;

class Author {

    private $id;

    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}

```


## Create Configuration File

With our entity classes defined now it's time to move on to creating the
configuration file. For this we're going to needs to know what URLs we need to
call to get our entity data and what properties need populating on our
entities. Let's assume for this example that we're going to need to talk to a
RESTful endpoint at `https://example.com/api`. We'll add in the the following
configuration file that will set up the connection details and the entity
mappings:

```YAML
connections:

    # Define a connection which describes where we get entities from
    default:

        # The base URI is going to include the /api path as this
        # is universal for all endpoints on this connection
        base_uri: https://example.com/api

        # Let RESTORM know what the parameters are for using
        # pagination with list endpoints
        pagination_parameters:
            page_param: page
            per_page_param: per_page

# Define the Post and Author entities
entity_mappings:

    # Use the fully qualified entity class name as the key
    App\Entity\Post:

        # Use our "default" connection we registered above
        connection: default

        # Use the internal entity repository for querying
        # for entities
        repository_class: Robwasripped\Restorm\EntityRepository

        # Define the paths to each of the actions used for
        # getting and saving entities
        paths:
            list: /posts
            get: /posts/{id}
            post: /posts/new
            patch: /posts/{id}/edit
            delete: posts/{id}

        # List our properties
        properties:
            id:
                # Let RESTORM know that the id is the identifier
                identifier: true
                type: integer
            title:
                type: string
                unique: true
            content:
                type: string
                # The content property is called "html_content"
                # in the API so map that to the "content"
                # property in the entity
                map_from: html_content
            # Relate a Post entity to an Author entity
            author:
                # We will set the type as "entity"…
                type: entity
                # …and set the entity class
                entity: Project\Entity\Author
    Project\Entity\Author:
        connection: default
        repository_class: Robwasripped\Restorm\EntityRepository
        paths:
            list: /authors
            get: /authors/{username}
            post: /authors
            patch: /authors/{username}
            delete: /authors/{username}
        properties:
            id:
                identifier: true
                type: integer
            name:
                type: string
```

[You can view the full configuration reference here:](docs/Reference/Configuration.md)


## Initialize Manager

The entity manager is the heart of RESTORM. It controls entities as they go to
and from the connections and connects each internal component. Let's create a
simple script that creates a new `EntityManager` and fetches all our posts:

```PHP
<?php

// Use the composer autoloader to load in RESTORM
require __DIR__ . '/../vendor/autoload.php';

use Robwasripped\Restorm\Configuration\Configuration;
use Robwasripped\Restorm\EntityManager;
use App\Entity\Post;

// Create a new Configuration instance based on our configuration
$configPath = '../config/restorm.yml';
$configuration = Configuration::buildFromYaml($configPath);

// Create a new EntityManager from our Configuration instance
$entityManager = EntityManager::createFromConfiguration($configuration);

```

With the `EntityManager` instantiated it can now be used to fetch entites:

```php
// Fetch all the posts from the API
$posts = $entityManager->getRepository(Post::class)->findAll();

// Loop over the collection of posts to see their data
foreach($posts as $post) {
    
    // Instances of Author are lazy loaded when you need them.
    $author = $post->getAuthor() // Returns an instance of Author

    // No request is made to the API for the author until you request
    // data that wasn't available in the initial request.

    // The following request will not trigger a new call …
    $authorId = $author->getId();

    // …whereas this one will quietly populate the rest of Author
    $authorName = $author->getName();
    
}

```
