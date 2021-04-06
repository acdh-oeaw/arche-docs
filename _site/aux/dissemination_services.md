# Introduction

The way data are stored in arche is quite often not suitable for direct use, especially by humans. Who wants to browse trough metadata in raw RDF? Or view a book as a raw TEI-XML?

Dissemination services address this issue by providing transformations of arche resources into formats useful for dissemination, e.g. render TEI-XML into an HTML webpage a human being can esily view or transform RDF metadata into a BibLaTeX bibliographic entry you can use in you bibliography management software.

It's worth noting that in most cases it's not feasible to avoid dissemination services by putting resources being already in dissemnation-friendly formats into the arche.
First, there are typically plenty of dissemination formats we want to offer and this would bring a lot of data duplication and all problems connected with the data duplication (most importantly issues with keeping copies in sync and data volume issues). Second, this would make long term maintenance troublesome as once we want to adjust a given dissemination format we need to reprocess all repository resources (in technical terms we would say it breaks the separation between data and presentation).

# Definitions

In the spoken language a *dissemination service* has two meanings which are used interchangeable:

1. The service providing the transformation.\
   This is the technically correct meaning.
    * It's worth noting that from arche architecture point of view the repository browsing GUI or the OAI-PMH endpoint are also *dissemination services* in this meaning
      (the first one disseminates metadata as a webpage and the latter one transforms resource's arche metadata into OAI-PMH metadata).
2. A set of rules describing which dissemination services (in the 1st meaning) are availble for a given resource.\
   To avoid confusion this is called a *mapping* below.

# Architecture

* *Dissemination services* just work on their own.\
  The only requirement is it must be possible to describe them using the *mapping* data model.
* The [arche-lib-disserv](https://github.com/acdh-oeaw/arche-lib-disserv):
    * Determines the *mappings* data model.
    * Implements the *mappings* data model allowing to match *dissemination services* with arche resources and vice versa.
* The [arche-resolver](https://github.com/acdh-oeaw/arche-resolver) uses arche-lib-disserv to find a *dissemination service* best fulfilling the user request
  (as specified by user request's `Accept` HTTP header or `format` parameter) and redirects the user to the *dissemination service*.

It's worth noting that the [arche-core](https://github.com/acdh-oeaw/arche-core) is not aware of *dissemination services*.
The whole *dissemination service* logic is provided by higher layers of the arche software stack: arche-lib-disserv and arche-resolver.

# Dissemination service

Dissemination service in arche is any web service able to consume data stored in arche.

It can be hosted anywhere. It doesn't matter if it can deal only with arche resources or also with other data.
It just has to be reachable using an HTTP GET request and it must be able to fetch an arche resource on its own (e.g. from an URL passed to it as a part of the GET request).

# Mappings data model

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

## Fundamental dissemination service metadata

Each *dissemination service* has to:

* Be of class `cfg.schema.dissServ.class`.
* Have a uqnique identifier (as every arche resource).
* Has `cfg.schema.dissServ.location` and `cfg.schema.dissServ.returnFormat` RDF properties
  (see *Dissemination service request URL generation* and *Choosing dissemination service matching user's request the best* chapters below).

A minimal *dissemination service* description could look as follows:

```
<https://myDissServ/URI> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> cfg.schema.dissServ.class ;
                         cfg.schema.dissServ.location                      "https://service.location/template?resURL={RES_URI}" ;
                         cfg.schema.dissServ.returnFormat                  "text/html" .
```

Please read further for more details.

## Matching resources with dissemination services

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

## Dissemination service request URL generation

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

### User-defined parameters

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

## Choosing dissemination service matching user's request the best



# Using arche-lib-disserv

## Dissemination services matching a given arche resource

Knowing the resource URL:

```
$resUrl = 'https://arche.acdh.oeaw.ac.at/api/108253';
$repo   = acdhOeaw\acdhRepoLib\Repo::factoryFromUrl($resUrl);
$res    = new acdhOeaw\arche\disserv\RepoResource($resUrl, $repo);
$availableDissServ = $res->getDissServices();
foreach ($availableDissServ as $dissService) {
    print_r($dissService->getRequest($res));
}
```

Using search:

```
file_put_contents('config.yaml', 'https://arche.acdh.oeaw.ac.at/api/describe');
$repo       = new acdhOeaw\acdhRepoLib\Repo::factory('config.yaml');
$term       = new acdhOeaw\acdhRepoLib\SearchTerm('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'https://vocabs.acdh.oeaw.ac.at/schema#TopCollection');
$cfg        = new acdhOeaw\acdhRepoLib\SearchConfig();
$cfg->class = '\acdhOeaw\arche\disserv\RepoResource';
$resources  = $repo->getResourcesBySearchTerms([$term], $cfg);
foreach ($resources as $res) {
    echo "----------\n" . $res->getUri() . "\n";
    foreach ($res->getDissServices() as $$dissService) {
        print_r($dissService->getRequest($res));
    }
}
```

## Arche resources matching a given dissemination service

