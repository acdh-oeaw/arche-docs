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

### Limitations

A *dissemination service* must be able to fetch the arche resource on its own based on the data passed to it by a GET request. It means:

* There is no suport for dissemination services expecting disseminated service binary payload to be passed directly to them (e.g. as a POST request body).\
  If you want to handle such dissemination services, you need to set up a simple reverse proxy in front of them which will fetch arche resource's binary content and push it to the dissemination service.
* If access to the disseminated resource requires authentication, the dissemination service must be able to perform it on its own.
  Also this limitation can be overcome by setting up a reverse proxy performing the authentication (and, if needed, helping the dissemination service to bypass arche's authentication).

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

### Fundamental dissemination service metadata

Each *dissemination service* has to:

* Be of class `cfg.schema.dissServ.class`.
* Have a uqnique identifier (as every arche resource).
* Has `cfg.schema.dissServ.location` and `cfg.schema.dissServ.returnFormat` RDF properties
  (see *Dissemination service request URL generation* and *Choosing dissemination service best matching user's request* chapters below).

A minimal *dissemination service* description could look as follows:

```
<https://myDissServ/URI> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> cfg.schema.dissServ.class ;
                         cfg.schema.dissServ.location                      "https://service.location/template?resURL={RES_URI}" ;
                         cfg.schema.dissServ.returnFormat                  "text/html" .
```

Please read further for more details.

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

The matching rules system is a compromise between flexibility and complexity.
While it allows to handle most common scenarios, it can't express complex rules (e.g. with multiple indpendent alternatives like `(A or B) and (C or D)`).

Despite that there are two purely-technical requirements for rules:

* Every rule points to the dissemination service resource with the `cfg.schema.dissServ.parent` RDF property.
* Every rule is identified by its URI (there are no particular requirements here, it just has to be unique).

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
* Set of mapping rules matching any of a given set of values (here something like "any image"):
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

Once we know a given *dissemination service* is valid for a given repository resource, we need to be able to generate an HTTP GET request which triggering the dissemination.

The URL is generated by substituting a template with values coming from arche resource's metadata.

Every *dissemination service* defines its own URL template.

The URL template may contain a two kinds of placeholders:

* Predefined parameters:
    * `{RES_URI}` - the full URL of the resource, e.g. https://arche.acdh.oeaw.ac.at/api/109787.
    * `{RES_ID}` - the internal numeric identifier of the resource (the number at the end of its URL), e.g. `109787`.
    * `{nmsp_ID}` - a resource's identifier in the `nmsp` namespace.\
      The namespace has to be defined in the configuration file under `schema.namespaces.{nmsp}`. 
      See e.g. [here](https://github.com/acdh-oeaw/arche-docker-config/blob/arche/yaml/schema.yaml).
* Parameters defined by you - see the chapter below.

Every parameter value can be transformed using a set of predefined functions:

* Available functions are:
    * `add(name,value)` - treats a value as an URL and appends a given URL query parameter value to it
      (if a given query parameter exists, the URL will contain two parameters with the same name)
    * `base64` - base64 encodes the value
    * `rawurlencode` - URL encodes the value (transforming spaces into `%20`)
    * `removeprotocol` - removes a leading `http://` or `https://` from the value
    * `set` - treats a value as an URL and and appends a given URL query parameter value to it 
      (if a given query parameter exists, it's overwritten with a new value)
    * `substr(start,length)` - returns a given value substring
    * `part(...parts)` - treats a value as an URL and extracts given URL part(s) from it
        * parts can be `scheme`, `host`, `port`, `user`, `pass`, `path`, `query` and `fragment`
        * the general URL is of a form `scheme://user:pass@host:port/path?query#fragment` but not all URL have all parts
        * part-specific prefixes/suffixes are kept, e.g. ``uriPart(scheme)` applied to `https://arche.acdh.oeaw.ac.at` gives `https://` (and not just `https`)
    * `url` - URL encodes the value (transforming spaces into `+`)
* Transformations are applied using the `|` operator and can be stacked,
  e.g. `{RES_URI|part(query)|url}` first extracts the query part from the resource URI and than URL encodes it.

A few examples of *dissemination service* URL templates:

* `{RES_URI|part(scheme,host)}/browser/oeaw_detail/{RES_ID}`
  Take resource's URL scheme and host, append fixed `/browser/oeaw_detail/` URL path to it and append resource's internal numeric id at the end.
* `https://arche-thumbnails.acdh.oeaw.ac.at/{id_ID|part(host,path,query)}?width={WIDTH}&height={HEIGHT}`
  Extract host, path and query parts from resource's identifier in the `id` namespace (however the namespace is defined in the config) and append it to https://arche-thumbnails.acdh.oeaw.ac.at/.
  Finally ad `WIDTH` and `HEIGHT` user defined parameters (see below) as URL query parameters.
* `https://arche-biblatex.acdh.oeaw.ac.at/?lang={LANG}&id={id_ID|url}`
  Call https://arche-biblatex.acdh.oeaw.ac.at/?lang=LANG&id=ID` with 
  the the `id` query parameter substituted with URL-encoded arche resource identifier in the `id` namespace (however the namespace is defined in the config)
  and the `lang` query parameter taken from the a user-defined `{LANG}` parameter (see below).

For an extensive example please take a look [here](https://github.com/acdh-oeaw/arche-docker-config/blob/arche/initScripts/dissServices.ttl).

#### User-defined parameters

You can define your own URL template parameters which will be substituted based either on the arche resource's metadata values or values provided to the resolver.

Each user-defined parameter:

* Has a name determining it's placeholder (`cfg.schema.label`).
* Has a default value to be used when a given arche resource doesn't have a given metadata property (`cfg.schema.dissServ.parameterDefaultValue`).
* Must be of a `cfg.schema.dissService.parameterClass` RDF class.
* Must point to its dissemination service with the `cfg.schema.dissServ.parent` RDF property.
* As every arche resource must have an unique identifier.
* Has an optional metadata property which will be used to fetch its value from arche resource's metadata (`cfg.schema.dissServ.parameterRdfProperty`).
    * If a resource has many values of a given property, just a first encountered value is used.
    * The property URL is provided as an RDF literal (see the example below).

An example RDF definition of a parameter can look as follows:

```
<parameter> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> cfg.schema.dissServ.parameterClass ;
            cfg.schema.dissServ.parent                        <https://myDissServ/URI> ;
            cfg.schema.label                                  "XSL"@en ;
            cfg.schema.dissServ.parameterDefaultValue         "https://tei4arche.acdh-dev.oeaw.ac.at/xsl/test.xsl
            cfg.schema.dissServ.parameterRdfProperty          "https://vocabs.acdh.oeaw.ac.at/schema#hasCustomXsl" .
```

The corresponding *dissemination service* URL template placeholder is `{XSL}`.

### Choosing dissemination service best matching user's request

So far we know how to describe which dissemination services match which arche resources and how the HTTP GET request disseminating a given resource with a given service is created 
but it doesn't tell us how a dissemination resource is matched with a user's request.

Here the `cfg.schema.dissServ.returnFormat` dissemination's service property plays the key role.

While making a request to the resolver, user can specify a preffered output format.
This can be done either explicitely (by including the `format=desiredFormat` query parameter in the URL or `Accept: desiredFormat` HTTP header)
or implicitely (e.g. a web browsers always add the `Accept: text/html` HTTP header without asking user about it).

Knowing the output format desired by the user the resolver checks if there is a dissemination service
mathing the requested arche resource with a desired value of the `cfg.schema.dissServ.returnFormat` RDF property.

* If there is such a service, it's used for the dissemination.
* If there is no such service, a default dissemination service is used (typically the repository browser GUI).

Things get a little complicated where there are many dissemination services able to provide the requested output format.
This is particularly common situation when the client requests the `text/html` output format.
In such a case the so called quality value is taken into account. 

Dissemination service's `cfg.schema.dissServ.returnFormat` property value may contain not only the output format name but also a quality value.
Quality value syntax and semantics follows the one of the HTTP Accept header (see [here](https://developer.mozilla.org/en-US/docs/Glossary/Quality_values))
including the default value of 1.0.

Out of dissemination services providing the requested output format the one with the highest quality value is chosen
and if many dissemination services have the same highest quality value, just the first encountered is used.

E.g. when a user requests `text/xml,text/html;q=0.9` and there are following dissemination services matching the requested resource:

```
<service1> cfg.schema.dissServ.returnFormat "text/html;q=0.1" .
<service2> cfg.schema.dissServ.returnFormat "text/html" .
<service3> cfg.schema.dissServ.returnFormat "text/html" .
```

then:

* Output in `text/html` is provided as there is no matching dissemination service offering `text/xml` output format.
* Out of three available dissemination services either `<service2>` or `<service3>` is used (whichever is spotted first) as they share the same highest quality value of 1.0
  (as it's not explicitely specified, the default value of 1.0 is used).
  `<service1>` isn't used as it has lower quality value.

It's worth noting that:

* A single dissemination service may provide many `cfg.schema.dissServ.returnFormat` values.
* If dissemination service's `cfg.schema.dissServ.returnFormat` value isn't unique, it's always worth to add the (lower than 1.0) quality value.
  Otherwise such a service will always take a precedense over other services providing the same output format (as the default quality value is 1.0).
* While it's semantically nice to assing `cfg.schema.dissServ.returnFormat` values being MIME types it starts to be troublesome 
  when there are many dissemination services returning the same MIME type (like `text/html`).
  In such a case it might be better and more intuitive for the users to assign non-MIME but unique values 
  (it would make it easier for the users to predict which dissemination service will be used).
    * A hybrid approach is also possible when we assign one `cfg.schema.dissServ.returnFormat` value being a MIME type (with carefully chosen quality value)
      and second `cfg.schema.dissServ.returnFormat` value being a non-MIME unique dissemination service name, e.g.
      ```
      <service> cfg.schema.dissServ.returnFormat "text/html;q=0.1" ,
                                                 "iiifviewer" .
      ```
    * If you want to assign a `cfg.schema.dissServ.returnFormat` value which is already used by other dissemination service and have no idea what should be the proper quality value,
      don't assign the duplicated `cfg.schema.dissServ.returnFormat` value at all and consult someone more skilled.

## Using arche-lib-disserv

### Dissemination services matching a given arche resource

Knowing the resource URL:

```php
include 'vendor/autoload.php';
$resUrl = 'https://arche.acdh.oeaw.ac.at/api/108253';
$repo   = acdhOeaw\arche\lib\Repo::factoryFromUrl($resUrl);
$res    = new acdhOeaw\arche\lib\disserv\RepoResource($resUrl, $repo);
$availableDissServ = $res->getDissServices();
foreach ($availableDissServ as $retType => $dissService) {
    echo "$retType: " . $dissService->getRequest($res)->getUri() . "\n";
}
```

Using search:

```php
include 'vendor/autoload.php';
file_put_contents('config.yaml', file_get_contents('https://arche.acdh.oeaw.ac.at/api/describe'));
$repo       = acdhOeaw\arche\lib\Repo::factory('config.yaml');
$term       = new acdhOeaw\arche\lib\SearchTerm('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'https://vocabs.acdh.oeaw.ac.at/schema#TopCollection');
$cfg        = new acdhOeaw\arche\lib\SearchConfig();
$cfg->class = '\acdhOeaw\arche\lib\disserv\RepoResource';
$resources  = $repo->getResourcesBySearchTerms([$term], $cfg);
foreach ($resources as $res) {
    echo "----------\n" . $res->getUri() . "\n";
    foreach ($res->getDissServices() as $retType => $dissService) {
        echo "$retType: " . $dissService->getRequest($res)->getUri() . "\n";
    }
}
```

### Arche resources matching a given dissemination service

```php
include 'vendor/autoload.php';
file_put_contents('config.yaml', file_get_contents('https://arche.acdh.oeaw.ac.at/api/describe'));
$repo         = acdhOeaw\arche\lib\Repo::factory('config.yaml');
$term         = new acdhOeaw\arche\lib\SearchTerm('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'https://vocabs.acdh.oeaw.ac.at/schema#DisseminationService');
$cfg          = new acdhOeaw\arche\lib\SearchConfig();
$cfg->class   = '\acdhOeaw\arche\lib\disserv\dissemination\Service';
$dissServices = $repo->getResourcesBySearchTerms([$term], $cfg);
$dissServ     = $dissServices[0];
print_r($dissServ->getFormats());
// get up to 5 resources matching the given dissemination service
foreach($dissServ->getMatchingResources(5) as $res) {
    echo $res->getUri() . ": " . $dissServ->getRequest($res)->getUri() . "\n";
}
```

