# AudiencePlayer API client for PHP

## Introduction

This package contains the AudiencePlayer API-client for PHP and facilitates:

* API-integration with your own administration and/or back office.
* Custom frontend application development.

Though not strictly necessary, a pre-existing [AudiencePlayer account](https://www.audienceplayer.com/contact) with
issued OAuth2 client credentials is the main use case for which this package is optimised.

## Requirements ##

To use the AudiencePlayer API-client for PHP, the following requirements must be observed:

+ PHP >= 7.1 (recommended >= 7.3)
+ PHP JSON extension
+ PHP cURL extension
+ Up-to-date OpenSSL (or other SSL/TLS toolkit)

## Installation ##

The easiest way to install this package is with [Composer](http://getcomposer.org/doc/00-intro.md):

```bash
    $ composer require audienceplayer/audienceplayer-api-client-php
```

Alternatively, you can integrate this package by downloading it and including the file `AutoLoader.php`:

```php
    require 'src/AudiencePlayer/AudiencePlayerApiClient/AutoLoader.php';
```

## Getting started ##

### Instantiation ###

First instantiate the API-client:

```php
    $clientId = 'yourOAuthClientId';
    $clientSecret = 'yourOAuthClientSecret';
    $projectId = 123456;
    $apiBaseUrl = 'https://api.example.com';

    # Instantiate with your clientId, clientSecret, projectId and apiBaseUrl
    $apiClient = AudiencePlayerApiClient::init(
        $clientId,
        $clientSecret,
        $projectId,
        $apiBaseUrl
    );
```

Authenticate via the proper mechanism to gain access for a given user (or alternatively as an OAuth2 admin client):

```php    
    # Pre-existing bearerToken that you may have cached for given user    
    $bearerToken = 'eYarTl93Fas3...';
    # User-ID or E-mail address of the user for whom you are validating/creating a token
    $userEmail = 'info@example.com';
    # When validating with only an e-mail address, the user may be auto-registered if non-existent
    $isAutoRegister = true;
 
    # Compare new token with pre-existing token and update your stored token if necessary 
    $newBearerToken = $apiClient->hydrateValidatedOrRenewedBearerTokenForUser(
        $userId,
        $userEmail,
        $userArgs,
        $bearerToken,
        $isAutoRegister
    );
```

### Execute Queries and Mutations ###

Example of a GraphQL query operation:

```php
    # Example of fetching an article with ID=123, and requesting properties name and type
    $result = $apiClient->query
        ->Article(123)                                              # required argument $id=123
        ->properties(['name', 'type'])                              # explicitly ask for given properties
        ->execute();

    # Example of fetching an articles list
    $result = $apiClient->query
        ->ArticleList()
        ->paginate(25, 0)                                           # with limit 25 and offset 0
        ->arguments(['category_id' => 456])                         # fetched all articles part of category with id 456
        ->properties(['id', 'name', 'metas' => ['key', 'value']])   # explicitly ask for given properties
        ->sort(                                                     # sort results by id in descending order
            'id',
            $apiClient:GRAPHQL_OPERATION_SORT_DIRECTION_DESC
        )
        ->execute();

    # Example of searching for specific articles
    $result = $apiClient->query
        ->ArticleList()
        ->search('foobar')                                          # search for articles with matching metadata
        ->properties(['id', 'name', 'metas{key,value}'])            # explicitly ask for given properties
        ->sort(                                                     # sort results by name in descending order
            'name',
            $apiClient:GRAPHQL_OPERATION_SORT_DIRECTION_ASC
        )
        ->execute();
```

Example of a GraphQL mutation operation:

```php
    # Example of updating user details
    $result = $apiClient->mutation
        ->UserDetails()
        ->arguments(['email' => 'info2@example.com'])               # update with given arguments
        ->execute();
```

You can easily process the operation result object with the following helpers:

```php
    # Inspect the result
    $result->isSuccessful();                        # result was successfully retrieved and does not contain any errors
    $result->hasErrors();                           # result contains errors
    $result->getErrors();                           # obtain a list of errors

    # Access result properties
    $result->data;                                  # the returned data
    $result->getData();
    
    # Inspect the last graphql operation, which may be helpful for debugging purposes
    $result->getOperationQuery();                   # the executed raw graphQL query, e.g. "query{UserDetails{id,email}}"
    $result->getOperationVariables();               # the used graphQL variables (if applicable), e.g. "{id:1}"
```

### Custom Queries and Mutations ###

You can also execute custom GraphQL queries and mutations.
For more information about the GraplhQL syntax, please see [graphql.org](https://graphql.org).

```php
    $operation = 'query Article($articleId:Int) {
                  CustomArticleQuery: Article (id:$articleId) {id name}
              }
    ';

    $result = $apiClient->executeRawGraphQLCall(
        Globals::OAUTH_SCOPE_USER,                  # the oauth-scope you wish to address the api with
        $operation,                                 # raw GraphQL operation (query/mutation) 
        ['articleId' => 1],                         # variables for the operation (if necessary)
        true,                                       # execute as POST request (false for GET)
        true,                                       # retrieve result as parsed object (false for raw JSON)
        'Article',                                  # operation name
        $bearerToken                                # your stored/saved OAuth bearer token
    );

    $result = $apiClient->executeRawGraphQLCall(
        Globals::OAUTH_SCOPE_USER,                  # The oauth-scope you wish to address the api with
        'query{Article(id:1){id,name}}',            # simple raw GraphQL operation without variables 
        [],                                         # the variables for the operation (if necessary)
        true,                                       # execute as POST request (false for GET)
        true,                                       # retrieve result as parsed object (false for raw JSON)
        'Article',                                  # operation name
        $bearerToken                                # your stored/saved OAuth bearer token
    );
```

### Testing ###

You can run unit and static tests by executing:

```bash
    $ composer run test
```

Or run them separately:

```bash
    $ ./vendor/bin/phpunit
    $ ./vendor/bin/phpstan
```

## License ##

[BSD (Berkeley Software Distribution) License](https://opensource.org/licenses/bsd-license.php).
Copyright (c) 2020, AudiencePlayer

## Support ##

Contact:

- [www.audienceplayer.com](https://www.audienceplayer.com)
- [support@audienceplayer.com](mailto:support@audienceplayer.com)
