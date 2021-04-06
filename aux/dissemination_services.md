# Dissemination services

## Introduction

The way data are stored in arche is quite often not suitable for direct use, especially by humans. Who wants to browse trough metadata in raw RDF? Or view a book as a raw TEI-XML?

Dissemination services address this issue by providing transformations of arche resources into formats useful for dissemination, e.g. render TEI-XML into an HTML webpage a human being can esily view or transform RDF metadata into a BibLaTeX bibliographic entry you can use in you bibliography management software.

It's worth noting that in most cases it's not feasible to avoid dissemination services by putting resources being already in dissemnation-friendly formats into the arche.
First, there are typically plenty of dissemination formats we want to offer and this would bring a lot of data duplication and all problems connected with the data duplication (most importantly issues with keeping copies in sync and data volume issues). Second, this would make long term maintenance troublesome as once we want to adjust a given dissemination format we need to reprocess all repository resources (in technical terms we would say it breaks the separation between data and presentation).

## Definitions

In the spoken language a *dissemination service* has two meanings which are used interchangeable:

1. The service providing the transformation.\
   This is the technically correct meaning.
    * It's worth noting that from arche architecture point of view the repository browsing GUI or the OAI-PMH endpoint are also *dissemination services* in this meaning
      (the first one disseminates metadata as a webpage and the latter one transforms resource's arche metadata into OAI-PMH metadata).
2. A set of rules describing which dissemination services (in the 1st meaning) are availble for a given resource.\
   To avoid confusion this is called a *mapping* below.

## Architecture

* *Dissemination services* just work on their own.\
  The only requirement is it must be possible to describe them using the *mapping* data model.
* The [arche-lib-disserv](https://github.com/acdh-oeaw/arche-lib-disserv):
    * Determines the *mappings* data model.
    * Implements the *mappings* data model allowing to match *dissemination services* with arche resources and vice versa.
* The [arche-resolver](https://github.com/acdh-oeaw/arche-resolver) uses arche-lib-disserv to find a *dissemination service* best fulfilling the user request
  (as specified by user request's `Accept` HTTP header or `format` parameter) and redirects the user to the *dissemination service*.

It's worth noting that the [arche-core](https://github.com/acdh-oeaw/arche-core) is not aware of *dissemination services*.
The whole *dissemination service* logic is provided by higher layers of the arche software stack: arche-lib-disserv and arche-resolver.

## Dissemination service

Dissemination service in arche is any web service able to consume data stored in arche.

It can be hosted anywhere. It doesn't matter if it can deal only with arche resources or also with other data.
It just has to be reachable using an HTTP GET request and it must be able to fetch an arche resource on its own (e.g. from an URL passed to it as a part of the GET request).

## Mappings data model

*(Dissemination service) mappings* is a set of rules for:

1. Matching arche resources with *dissemination services* able to transform them.
2. Generating an HTTP request to the *dissemination service* based on the arche resource metadata.


The data model for both is determined by the [arche-lib-disserv](https://github.com/acdh-oeaw/arche-lib-disserv) library.

Mappings are defined in RDF and represented in the arche as a set of (pretty ordinary) resources.

It means you prepare mappings definitions as an RDF file and ingest them into arche as any other set of metadata.

The arche-lib-disserv doesn't enforce any particular RDF property names but it means you must choose them on your own and define them in a config file.
In examples below a `cfg.schema.dissServ.propertyCfgName` syntax will be used to denote RDF properties to be defined by you.

An extensive example of the mappings can be found [here](https://github.com/acdh-oeaw/arche-docker-config/blob/arche/initScripts/dissServices.ttl)
with RDF property mappings being defined [here](https://github.com/acdh-oeaw/arche-docker-config/blob/arche/yaml/schema.yaml).

### Matching resources with dissemination services

Matching is done based on matching rules:

* Every rule defines a metadata property and, optionally, a value.
    * If a value is provided, the rule is matched if a given arche resource has a given value of a given metadata property.
    * If a value isn't defined, the rule is matched if a given arche resource has a given metadata property (with any value).
    * Both the property URI and the optional value should be provided as RDF literals (see examples below).
* Every rule can be *optional* or *required*.\
  For an arche resource to match a given *dissemination service*:
    * The resource has to match all *required* mapping rules.
    * And if there is at least one *optional* mapping rule, the resource has to match at least one *optional* mapping rule.
* Every rule points to the dissemination service with the `cfg.schema.dissServ.parent` RDF property.

The matching rules system is a compromise between flexibility and complexity.
While it allows to handle most common scenarios, it can't express complex rules (e.g. with multiple indpendent alternatives like `(A or B) and (C or D)`).

Examples:

* Mapping rule matching all resources of a given RDF class:
  ```
  <mappingRuleURI> cfg.schema.dissServ.parent        <https://myDissServ/URI> ;
                   cfg.schema.dissServ.matchProperty "http://www.w3.org/1999/02/22-rdf-syntax-ns#type" ;
                   cfg.schema.dissServ.matchValue    "https://myClass/URI" ;
                   cfg.schema.dissServ.matchRequired "true"^^<http://www.w3.org/2001/XMLSchema#boolean> .
  ```
* Mapping rule matching all resources having a given property
  ```
  <mappingRuleURI> cfg.schema.dissServ.parent        <https://myDissServ/URI> ;
                   cfg.schema.dissServ.matchProperty "https://myProperty/URI" ;
                   cfg.schema.dissServ.matchRequired "true"^^<http://www.w3.org/2001/XMLSchema#boolean> .
  ```
* Set of mapping rules matching any of a given set of values (here a mime type suggesting an image):
  ```
  <mappingRuleURI1> cfg.schema.dissServ.parent        <https://myDissServ/URI> ;
                    cfg.schema.dissServ.matchProperty "https://property/storing/mimeType" ;
                    cfg.schema.dissServ.matchValue    "image/tiff" ;
                    cfg.schema.dissServ.matchRequired "false"^^<http://www.w3.org/2001/XMLSchema#boolean> .
  <mappingRuleURI2> cfg.schema.dissServ.parent        <https://myDissServ/URI> ;
                    cfg.schema.dissServ.matchProperty "https://property/storing/mimeType" ;
                    cfg.schema.dissServ.matchValue    "image/jpeg" ;
                    cfg.schema.dissServ.matchRequired "false"^^<http://www.w3.org/2001/XMLSchema#boolean> .
  <mappingRuleURI3> cfg.schema.dissServ.parent        <https://myDissServ/URI> ;
                    cfg.schema.dissServ.matchProperty "https://property/storing/mimeType" ;
                    cfg.schema.dissServ.matchValue    "image/png" ;
                    cfg.schema.dissServ.matchRequired "false"^^<http://www.w3.org/2001/XMLSchema#boolean> .  
  ```
* Combining required and optional rules:
  ```
  <mappingRuleURI1> cfg.schema.dissServ.parent        <https://myDissServ/URI> ;
                    cfg.schema.dissServ.matchProperty "http://www.w3.org/1999/02/22-rdf-syntax-ns#type" ;
                    cfg.schema.dissServ.matchValue    "https://myClass/URI" ;
                    cfg.schema.dissServ.matchRequired "true"^^<http://www.w3.org/2001/XMLSchema#boolean> .
  <mappingRuleURI2> cfg.schema.dissServ.parent        <https://myDissServ/URI> ;
                    cfg.schema.dissServ.matchProperty "https://property/storing/mimeType" ;
                    cfg.schema.dissServ.matchValue    "image/jpeg" ;
                    cfg.schema.dissServ.matchRequired "false"^^<http://www.w3.org/2001/XMLSchema#boolean> .
  <mappingRuleURI3> cfg.schema.dissServ.parent        <https://myDissServ/URI> ;
                    cfg.schema.dissServ.matchProperty "https://property/storing/mimeType" ;
                    cfg.schema.dissServ.matchValue    "image/png" ;
                    cfg.schema.dissServ.matchRequired "false"^^<http://www.w3.org/2001/XMLSchema#boolean> .  
  ```

### Dissemination service request URL generation

The second part of




## Using arche-lib-disserv

### Dissemination services matching a given arche resource

### Arche resources matching a given dissemination service

