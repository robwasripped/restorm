# Configuration

The configuration describes the layout of your project to RESTORM and what
properties of your entities it should handle. The configuration can be included
as a YAML file or injected as an array. Below is a reference to the complete
configuration currently available in YAML.

```YAML
connections:

    # Define a connection which describes where we get entities from
    default:
        base_uri: https://example.com
        pagination_parameters:
            page_param: page
            per_page_param: per_page
        # Describe what headers can be used to determine collection limits
        pagination_data:
            query_total_header: X-Pagination-Total
            page_total_header: X-Pagination-Page-Size
            current_page_header: X-Pagination-Page

# Define entities
entity_mappings:
    Project\Entity\Post:
        connection: default
        repository_class: Robwasripped\Restorm\EntityRepository
        paths:
            list: /posts
            get: /posts/{id}
            post: /posts/new
            patch: /posts/{id}/edit
            delete: posts/{id}
        properties:
            id:
                identifier: true
                type: integer
            title:
                type: string
                unique: true
            content:
                type: string
                map_from: html_content
            # meta can be a key/value object with no specific keys
            meta:
                type: object
                dynamic: true
            createdAt:
                type: datetime
                read_only: true
                map_from: created_at
            # Relate a Post entity to an Author entity
            author:
                type: entity
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
            username:
                identifier: true
                type: string
            # Relate many Posts to an Author to create a
            # bidirectional relationship
            posts:
                type: entity
                entity: Project\Entity\Post
                multiple: true
                inverse_field: author
            # Define a field that persists as an object type
            # which is defined by an entity. Note that the
            # entity structure does not exist outside this
            # field definition
            contactDetails:
                type: entity
                entity: Project\Entity\ContactDetails
                inline: true
                map_from: contact_details
            isActive:
                type: boolean
                map_from: is_active
    Project\Entity\ContactDetails:
        # This is an inline entity so no paths need defining
        paths: ~
        properties:
            phone:
                type: string
            email:
                type: string
```

# Connections

A connection describes the details of how to communicate with an API. A
connection can represent different website APIs or parts of a single one. All
entities, regardless of which connection they were found on, are managed by the
same entity manager and can reference an entity from any other connection.

The `connections` section contains a list of APIs you want to use with RESTORM,
each with a name that is referenced on an entity.


## base_uri
```YAML
base_uri: https://example.com
```

The URI of the endpoint. This URI must include a protocol, as well as any static
path prefixed before the REST endpoints. By allowing the path here, it may be
possible to define multiple connections to the different versions of the same
API, if the path allows for that. You could for example have something like this:

```YAML
connections:
    APIv1:
        base_uri: https://example.com/api/v1/
```

This could represent a connection to version 1 of an API which some entities may
need, whereas others may want to connection to version 2 of an API:

```YAML
connections:
    APIv2:
        base_uri: https://example.com/api/v2/
```


## Pagination Parameters
```YAML
pagination_parameters:
    page_param: page
    per_page_param: per_page
```

Pagination can be automatically handled if it's available via query parameters.
By giving details of pagination, a `PaginatedCollection` object can be returned
that is able to lazy load results from each page of a list while iterating.
`pagination_parameters` takes 2 additional pieces of information - the parameter
name for page number and the parameter name for the "items per page" count.


## Pagination Data
```YAML
pagination_data:
    query_total_header: X-Pagination-Total
    page_total_header: X-Pagination-Page-Size
    current_page_header: X-Pagination-Page
```

If the REST API returns information about the query's pagination, that information
can be returned in a `PaginatedCollection` object. Currently the pagination is
expected to be returned as headers, and those header keys can be set in the
configuration.


# Entity Mappings

Restorm needs to know what classes it needs to manage and what properties those
classes has to track. This information is stored in the entity_mappings array
of the config. Here you'll get to specify the class, the connection it uses,
the fields and their types, as well as the URL paths for fetching and
persisting the entities.

The mappings contains an array of entities. Each entity's key in the list
should be its class name. This means that only one entity can be made for each
class.


## Connection

```YAML
connection: default
```

The value for connection should be one of the configured connections in
Restorm. This field will tell RESTORM which connection to use to fetch the
information.


## Repository_class

```YAML
repository_class: Robwasripped\Restorm\EntityRepository
```

The repository class tells RESTORM which repository it should use for fetching
entities. The repository is instanciated by the `EntityManager` and return with
`EntityManager::getRepository()`. There is no default for this field however
it's recommended that the internal `EntityRepository` repository be used as it
provides many generic methods for fetching entities based on IDs or filters. In
the case that you'd want to provide your own custom method that contain their
own business logic for fetching entities then you should create your own
repository class and configure RESTORM to use that.


## Paths

```YAML
paths:
    list: /posts
    get: /posts/{id}
    post: /posts/new
    patch: /posts/{id}/edit
    delete: posts/{id}
```

The paths describe the endpoint that should be used for each action or method.
The options include a "list" action which referes to a `GET` request for
fetching many of a resource, similar to the "get" which is for fetching a
single resource.


### Parameters

The path itself is allowed to contain placeholders for values of an entity. For
example if you get a "get" endpoint that requires the "ID" of an entity to be
included in the path then you can use the parameters syntax to tell RESTORM to
include that "ID" in the path:

```
/entity/{id}
```

The parameters syntax works only for endpoint which have a single entity in the
context of RESTORM. For example when asking RESTORM to persist an entity it
will be within the context of that entity when building the URL. On the other
hand trying to get a list of entities would put RESTORM in no particular
context of that entity as the search filter (if any) will refer to a collection
of entities instead of one.

Any property of an entity can be used as a parameter in the paths.


## Properties

The properties list is used by RESTORM to understand what an entity looks like.
Restorm is only aware of the fields of an entity that are defined the
properties list and will ignore any other property on a class. Properties have
a name which is used as the key and a list of options within it. The property
name should match the definition of the property in the class as in the
following example;

```PHP
<?php

class Book {

    private $name;

    private $authorName;

    private $ISBN;
    
}
```

```YAML
entity_mappings:
    Book:
        properties:
            name:
                type: string
            authorName:
                type: string
            ISBN:
                type: string
```


### Options

#### type

The field type. Can be set to any of the following values:

- string
- boolean
- datetime
- float
- integer
- entity
- object

The types "entity" and "object" are special in that they allow additional
options to be set, and in some cases require them. The entity field will
require "entity" to be set and allows "inline" and "inverse_field" to be set.
"Object" allow "inline" and "dynamic" to be set.


#### identifier

A boolean flag to describe which property should be used as the unique
identifier of an entity. All entities must have exactly 1 field that is a
unique identifier. This identifier is used internally to keep track of entities
and to prevent duplicate instances representing the same entity data from being
created.


#### map_from

In the case where the key of an entity's value given by the connection differs
from the name of the property it's to be stored to, this option can be used to
describe that difference. For example, if the entity has the property
`createdAt` which should map to the data key "creation_date", the "map_from"
property can be used like so:

```YAML
createdAt:
    type: string
    map_from: creation_date
```

This is useful not only for when the name itself differs but also if there is a
different in naming convention such as the use of camel_case or capitalization.
if "map_from" is not set then the property name is presumed.


#### read_only

A boolean flag to indicate whether the property is read only or not. This will
prevent RESTORM from monitoring changes and attempting to update the property's
value. By default this is set to `false`.


#### entity

Required when the "type" is set to `entity`. This option informs RESTORM what
entity class should be used when populating this property. A fully qualified
class name that's defined in the "entity_mappings" configuration should be
given.


#### inline

When the "type" of a property is `entity` this option allows you to describe
whether the entity should be related via a standalone entity fetched from a
connection or if the entity should be considered a "defined object" type and be
embedded entirely within the property. If `true` then the entity is placed in
the property as an instance of that entity, otherwise it's assumed that this
field is of the same type as the referred entity's identifier field.


#### dynamic

A boolean flag used with the "type" `object`. When `true`, this will declare
that the property is an object which is not strictly defined. In practice this
will populate the property with a `stdClass` object that can have properties
defined on the fly.


#### inverse_field

If the "type" is `entity` then the "inverse_field" can be defined. This will
change the relationship of 2 standalone entities from a unidirectional
relationship to a bidirectional relationship. The inverse field should be the
name of the property on the other entity which contains this entity's
identifier.


#### multiple

A boolean flag for whether the field can contain multiple of the type given in
"type". When used with entities this will cause entities to be returned in
`EntityCollection` instances.
