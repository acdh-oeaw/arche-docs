# Introduction 

## What is ARCHE Suite and what is not

Probably the most important thing you need to know is that the ARCHE Suite **is a framework**
(in contrary to being an application or a service).
To put it simple **you need to be (or have around) a programmer** to make a working repository out of it.
If you are searching for an just-install-and-use app, please rather take a look at software like the [DSpace](https://dspace.lyrasis.org/).

## Unique features

If you are still reading, here are a few features which are rather unique to the ARCHE Suite:

* Proper support for the Linked Data in RDF:
  * Support for metadata input and output in any of RDF-XML, n-triples, turtle and JSON-LD serialization formats.
  * Metadata is stored in RDF with resource URL as a subject.
  * While there is no SPARQL endpoint (for performance reasons), 
    it is possible to flexibly fetch linked resources in one REST API call
    (more information can be found [here](metadata_api_for_programmers.html)).
  * Native support for multiple resource identifiers.  
    If metadata is integrated from multiple sources or external identifier sources are used (e.g. DOI),
    you might easily end up with multiple concurrent identifiers for the same resource.
    The ARCHE API allows you to identify the resource with any of them.
  * Foreign keys are checked - you can not remove a repository resource until other repository resources
    point to it in their metadata.
* No metadata schema enforced.  
  While there are some concepts which have to be assigned an RDF predicate (e.g. a predicate for storing multiple identifiers or resource labels),
  this is fully flexible and set up as a configuration.
* Extensibility.  
  You can easily plug in your own code in any programming language having the [AMQP](https://en.wikipedia.org/wiki/Advanced_Message_Queuing_Protocol) support.
  The plugins system supports all [CRUD](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) events and is covered by the transactions system.
* Very powerful OAI-PMH service
  allowing on-the-fly mapping of the RDF metadata to the OAI-PMH ones.
* Fully [ACID](https://en.wikipedia.org/wiki/ACID) data modification transactions.
* Shipped with own handles service.
* Modules (microservices) providing intergration with:
  * [CLARIN FCS](https://www.clarin.eu/content/federated-content-search-clarin-fcs-technical-details)
  * [OpenRefine](https://openrefine.org/)

## Architecture

The ARCHE Suite is build in a very modular way.
It increases flexibility and extensibility but unfortunatelly it also makes it slightly harder to understand how things work at the beginning.

The most crucial parts are:

* [arche-core](https://github.com/acdh-oeaw/arche-core) implements the REST API 
  providing the [CRUD](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) operations as well as a search.
  **It does not provide any graphical user interface.**
* [arche-docker](https://github.com/acdh-oeaw/arche-docker) provides a [Docker](https://en.wikipedia.org/wiki/Docker_(software)) image
  with the runtime environment for the arche-core.
* [arche-docker-config](https://github.com/acdh-oeaw/arche-docker-config) providing a sample configuration for the arche-core and other components.

On top of this minimal set you will be almost for sure interested in:

* [arche-oaipmh](https://github.com/acdh-oeaw/arche-oaipmh) implementing
  the [OAI-PMH](https://en.wikipedia.org/wiki/Open_Archives_Initiative_Protocol_for_Metadata_Harvesting) service.
* [arche-resolver](https://github.com/acdh-oeaw/arche-resolver) implementing
  a handles and [dissemination services](dissemination_services.html) redirection service.

There is a set of modules implementing [dissemination services](dissemination_services.html):

* [arche-biblatex](https://github.com/acdh-oeaw/arche-biblatex) for generation of [Bib(La)TeX bibliographic entries](https://en.wikipedia.org/wiki/BibTeX#Bibliographic_information_file)
  from the metadata.
* [arche-exif](https://github.com/acdh-oeaw/arche-exif) for exif metadata extraction from images stored in the repository.
* [arche-openrefine](https://github.com/acdh-oeaw/arche-openrefine) implementing the [OpenRefine](https://openrefine.org/) reconciliation API endpoint.
* [arche-thumbnails](https://github.com/acdh-oeaw/arche-thumbnails) generating minified versions of images store in the repository.

### GUI

At this point you are probably wondering where is a graphical user interface.
Well, there is none...

More precisely there is one - [arche-gui](https://github.com/acdh-oeaw/arche-gui) - which we use for our own repository instance(s)
but it is quite unlikely it will do the job for you as it is tightly coupled with our metadata schema.

**If you are using the ARCHE Suite you are expected to develop the graphical user interface for browsing the repository on your own.**

This is a well-though decision on our side.
The thing is everyone wants to adjust the GUI to their preferences and there is no one-suits-it-all solution.
So taking into account our limited resources we decided we will not aim for providing a reusable GUI.

Still the arche-core API (for more information on using it see the guides on the [main page](../)) 
and the pretty straightforward underlaying database structure should allow you do develop a GUI quite easily and fast.

### Data consistency check tools

As with the GUI we did not try to prepare a one-fits-it-all data consistency checks system.

What ARCHE Suite provides here is a flexible plugins system allowing you to execute your own code on particular events like the resource addition, modification, deletion, etc.

In our case we developed and plugged in the [arche-doorkeeper](https://github.com/acdh-oeaw/arche-doorkeeper) module.
It performs various checks (mainly metadata consistency once) and some automations (e.g. on-demand fetching of PIDs from an external handles service).

In our setup the arche-doorkeeper reads the configuration from an [OWL ontology](https://github.com/acdh-oeaw/arche-schema-ingest) 
which is stored as a set of ordinary repository resources
(as an OWL ontology is expresses in the RDF and the arche-core stores metadata in RDF, it is pretty straightforward and natural approach;
there is a separate [piece of software responsible for the ingestion](https://github.com/acdh-oeaw/arche-schema-ingest)).

While the chances that it will be directly reusable by you are rather small, you can take it as an inspiration
for your own checks implementation.

## Data ingestion tools

The ARCHE Suite comes with a quite a powerful set of data ingestion tools.
The only caveat is they are console tools (lack graphical user interface).

For end-user scripts take a look at the [arche-ingest](https://github.com/acdh-oeaw/arche-ingest)
while the underlaying PHP libraries are provided by the [arche-lib-ingest](https://github.com/acdh-oeaw/arche-lib-ingest)
