# First steps

## Prerequisites

You need:

* [git](https://git-scm.com/)
* [Docker](https://www.docker.com/)
* any unformatted text editor
* ability to work with a command line
* a tool to make HTTP requests
  (e.g. [curl](https://curl.se/), [Podman](https://podman.io/)
  or a web browser)

Remarks:

* This guide was tested on a linux (Ubuntu 22.04) machine.
  * As we are using Docker it should be also reproducible on a Windows and macOS machine
    but this was not tested and there is a risk you will run into platform-specific issues.
  * If you are using Fedora/RedHat, then you may want to use Podman instead of Docker.
    This should work in general but there might be some differences you will need to address
    and which are not covered by this guide
    (e.g. slightly different handling of mounting host directories as volumes or lack of the *compose* action).
    For the most smooth experience I would advise you to install Docker.
  * If you are using Fedora/RedHat with selinux turned on, you might need to add `--privileged` switch to
    `docker run` commands (or `privileged: true` in the `docker-compose.yaml`).
* We will be using Docker extensively.
  If you have no experience with it at all, you may succeed with the *Really quick and dirty setup*
  but it will be really difficult for you to go beyond it.
  There are two solutions of this problem - either find someone who knows Docker
  (it is quite a widespread technology nowadays so it should not be a trouble)
  or read a little how it works on the internet.

## Really quick and dirty setup

In this step we will just set up an ARCHE Suite instance as it is used at the [ACDH-CH](https://www.oeaw.ac.at/acdh/).

It creates a fully-fledged setup with some optional services and also ingests some data into the repository.

1. Start it up with:
   ```bash
   docker run --name arche-suite -p 80:80 -e CFG_BRANCH=arche -e ADMIN_PSWD='myAdminPassword' -d acdhch/arche
   ```
2. Run:
   ```bash
   docker logs -f arche-suite
   ```
   and wait until you see something like:
   ```bash
   ##########
   # Starting supervisord
   ##########
   
   2023-06-22 08:44:35,458 INFO Included extra file "/home/www-data/config/supervisord.conf.d/postgresql.conf" during parsing
   2023-06-22 08:44:35,458 INFO Included extra file "/home/www-data/config/supervisord.conf.d/tika.conf" during parsing
   2023-06-22 08:44:35,459 INFO RPC interface 'supervisor' initialized
   2023-06-22 08:44:35,459 CRIT Server 'unix_http_server' running without any HTTP authentication checking
   2023-06-22 08:44:35,459 INFO supervisord started with pid 1253
   2023-06-22 08:44:36,462 INFO spawned: 'initScripts' with pid 1255
   2023-06-22 08:44:36,463 INFO spawned: 'apache2' with pid 1256
   2023-06-22 08:44:36,464 INFO spawned: 'postgresql' with pid 1257
   2023-06-22 08:44:36,464 INFO spawned: 'tika' with pid 1258
   2023-06-22 08:44:36,465 INFO spawned: 'txDaemon' with pid 1259
   2023-06-22 08:44:37,496 INFO success: initScripts entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
   2023-06-22 08:44:37,496 INFO success: apache2 entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
   2023-06-22 08:44:37,496 INFO success: postgresql entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
   2023-06-22 08:44:37,496 INFO success: tika entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
   2023-06-22 08:44:37,496 INFO success: txDaemon entered RUNNING state, process has stayed up for > than 1 seconds (startsecs)
   ```
   Now hit `CRTL+C`.  
   At this point the repository is up and running.
3. Wait until initial data is imported.  
   Run:
   ```bash
   docker exec -ti -u www-data arche-suite tail -f log/initScripts.log
   ```
   and wait until you see (this may take a few minutes as two big controlled vocabularies are being imported):
   ```bash
   ##########
   # INIT SCRIPTS ENDED
   ##########
   ```
4. Import a sample collection with a resource:
   * Log into the docker container with:
     ```bash
     docker exec -ti -u www-data arche-suite bash
     ```
   * Create a `collection.ttl` describing a top collection resource and a TEI-XML resource according to the [ACDH-CH metadata schema](https://github.com/acdh-oeaw/arche-schema):
     ```bash
     echo '
     @prefix n1: <https://vocabs.acdh.oeaw.ac.at/schema#>.

     <https://id.acdh.oeaw.ac.at/traveldigital> a n1:TopCollection;
         n1:hasIdentifier <https://hdl.handle.net/21.11115/0000-000C-29F3-4>;
         n1:hasPid "https://hdl.handle.net/21.11115/0000-000C-29F3-4"^^<http://www.w3.org/2001/XMLSchema#anyURI>;
         n1:hasTitle "travel!digital Collection"@en;
         n1:hasDescription "A digital collection of early German travel guides on non-European countries which were released by the Baedeker publishing house between 1875 and 1914. The collection consists of the travel!digital Corpus (XML/TEI transcriptions of first editions (5 volumes) including structural, semantic and linguistic annotations), the travel!digital Facsimiles (scans and photographs of the historical prints), the travel!digital Auxiliary Files (a TEI schema of the annotations applied in the corpus, and a list of term labels for indexing names of persons annotated in the corpus), and the travel!digital Thesaurus (a SKOS vocabulary covering designations of groups and selected sights annotated in the corpus).\n The collection was created within the GO!DIGITAL 1.0 project \"travel!digital. Exploring People and Monuments in Baedeker Guidebooks (1875-1914)\", Project-Nr.: ÖAW0204.\n Image creation was done in 2004 at the Austrian Academy of Sciences (AAC-Austrian Academy Corpus)."@en;
         n1:hasSubject "Karl Baedeker"@en,
             "historical travel guides"@en;
         n1:hasHosting <https://id.acdh.oeaw.ac.at/arche>;
         n1:hasRightsHolder <https://d-nb.info/gnd/1001454-8>;
         n1:hasLicensor <https://d-nb.info/gnd/1123037736>;
         n1:hasMetadataCreator <https://id.acdh.oeaw.ac.at/uczeitschner>;
         n1:hasCurator <https://id.acdh.oeaw.ac.at/uczeitschner>;
         n1:hasCreator <https://id.acdh.oeaw.ac.at/uczeitschner>; 
         n1:hasDepositor <https://id.acdh.oeaw.ac.at/uczeitschner>;
         n1:hasContact <https://id.acdh.oeaw.ac.at/uczeitschner>;
         n1:hasOwner <https://d-nb.info/gnd/1123037736>.

     <https://id.acdh.oeaw.ac.at/traveldigital/Corpus/Baedeker-Mittelmeer_1909.xml> a n1:Resource;
         n1:hasTitle "Karl Baedeker: Das Mittelmeer. Handbuch für Reisende: Digital Edition"@en;
         n1:hasCategory <http://purl.org/dc/dcmitype/Dataset>;
         n1:hasDepositor <https://id.acdh.oeaw.ac.at/uczeitschner>;
         n1:hasMetadataCreator <https://id.acdh.oeaw.ac.at/uczeitschner>;
         n1:hasOwner <https://d-nb.info/gnd/1123037736>;
         n1:hasRightsHolder <https://d-nb.info/gnd/1001454-8>;
         n1:hasLicensor <https://d-nb.info/gnd/1123037736>;
         n1:hasLicense <https://creativecommons.org/licenses/by/4.0/>;
         n1:hasHosting <https://id.acdh.oeaw.ac.at/arche>;
         n1:isPartOf <https://id.acdh.oeaw.ac.at/traveldigital>.
     ' > collection.ttl
     ```
   * Ingest the metadata into the repository with:
     ```bash
     composer require acdh-oeaw/arche-ingest
     ~/vendor/bin/arche-import-metadata collection.ttl http://127.0.0.1/api admin myAdminPassword
     ```
     * note down the URLs of created resources reported in the log, e.g.:
       ```bash
           created http://127.0.0.1/api/11305 (1/2)
           created http://127.0.0.1/api/11306 (2/2)
       ```
   * Download and ingest the TEI-XML resource binary:
     ```
     mkdir sampledata
     curl https://arche.acdh.oeaw.ac.at/api/29688 > sampledata/Baedeker-Mittelmeer_1909.xml
     ~/vendor/bin/arche-import-binary \
         sampledata \
         https://id.acdh.oeaw.ac.at/traveldigital/Corpus \
         http://127.0.0.1/api admin myAdminPassword
     ```

At this point we have a repository with some data in it.  
We can check it out in a few ways:

* With an API call:
  * open URLs of the two ingested resources in your browser
    (e.g. http://127.0.0.1/api/11305 and http://127.0.0.1/api/11306 from the examples above)
    * the URL of the collection resource will display a metadata view
      (note you were redirected to `{originalURL}/metadata`)
    * the URL of the TEI-XML will download the TEI-XML file
      (you can append `/metadata` to the URL to display the metadata view)
  * If you want to display metadata in RDF (instead of HTML) append the `?format={RDF format MIME}`,
    e.g. `http://127.0.0.1/api/11305/metadata?format=application/rdf%2Bxml`
  * To learn more about fetching metadata trough the API read [here](metadata_api_for_programmers.html)
* With the [ACDH-CH GUI](https://github.com/acdh-oeaw/arche-gui) - open http://localhost/browser/  
  TODO - check with Norbert why the collection view doesn't work
* With the [OAI-PMH]() service - http://127.0.0.1/oaipmh/?verb=ListRecords&metadataPrefix=oai_dc

## Step-by-step installation

Now let's take a step back and make a step-by-step installation starting from a minimal setup.

### Installing arche-core

Let's say we want to set up a repository:

* working under the http://my.domain
* [using an external Postgresql database](https://github.com/acdh-oeaw/arche-docker#with-postgresql-database-on-other-host)
* [storing data in a named Docker volume](https://github.com/acdh-oeaw/arche-docker#with-data-directories-in-docker-named-volumes)
* [storing logs in a host directory](https://github.com/acdh-oeaw/arche-docker#with-data-directories-mounted-from-host) so we can access them easily

Let's go:

* As we will set up the repository locally we need to fake that the `my.domain` points to our own machine.
  This can be done by adding a line to the `/etc/hosts`:
  (you need admin rights for that, by the way this file exists also under Windows - google it):
  ```
  127.0.0.1 my.domain
  ```
* Clone a "vanilla" version of the configuration repository.  
  This will allow us to adjust the configuration in a persistent way.
  ```bash
  git clone --depth 1 --branch master https://github.com/acdh-oeaw/arche-docker-config.git
  ```
* Adjust the configuration
  * Create the instance-specific settings
    ```bash
    cp arche-docker-config/yaml/local.yaml.sample arche-docker-config/yaml/local.yaml
    ```
    and edit it so it looks as follows:
    ```yaml
    rest:
      urlBase: http://my.domain
      pathBase: /api/
    ```
  * Turn off internal Postgresql instance within the arche-core Docker container
    (we assumed we will use an external database):
    ```
    rm arche-docker-config/supervisord.conf.d/postgresql.conf
    ```
* Make a local directory for logs:
  ```bash
  mkdir log
  ```
* Prepare a [Docker compose](https://docs.docker.com/compose/) config for our setup in the `docker-compose.yaml`:
  ```yaml
  services:
    # container for "external" Postgresql database
    postgresql:
      image: postgis/postgis:15-master
      volumes:
      - postgresql:/var/lib/postgresql/data
      networks:
      - backend
      environment:
      - "POSTGRES_PASSWORD=${MYREPO_DB_PSWD}"
    # container for the arche-core
    arche-core:
      image: acdhch/arche
      volumes:
      - data:/home/www-data/data
      - ./arche-docker-config:/home/www-data/config
      - ./log:/home/www-data/log
      environment:
      - PG_HOST=postgresql
      - PG_USER=postgres
      - "PG_PSWD=${MYREPO_DB_PSWD}"
      - PG_DBNAME=arche
      - "USER_UID=${USER_UID}"
      - "USER_GID=${USER_GID}"
      - "ADMIN_PSWD=${ADMIN_PSWD}"
      ports:
      - "80:80"
      networks:
      - backend
      - bridge
      depends_on:
      - postgresql
  networks:
    backend:
      driver: bridge
    bridge:
  volumes:
    postgresql:
    data:
  ```
  and an `.env` file containing private environment variables:
  ```
  MYREPO_DB_PSWD=strongPassword
  ADMIN_PSWD=anotherStrongPassword
  USER_UID=number reported by running id -u
  USER_GID=number reported by running id -g
  ```
* Start everything up:
  ```bash
  docker compose up
  ```
* Check if everything works by making a GET request to the http://my.domain/api/describe, e.g.
  ```bash
  curl -i http://my.domain/api/describe
  ```
  you should get a YAML file describing the repository instance configuration which might be
  relevant from the client perspective.  

Congratulations, at that point we have the repository backbone up and running.

### Troubleshooting


If something did not work, you can inspect:

* Logs displayed in the terminal in which you run the `docker compose up` command.
* ARCHE Suite logs located in the `log` directory,
  especially the `error.log`, `rest.log` and 'initScripts.log`.

### Deciding on metadata schema

ARCHE Suite itself does not enforce any metadata schema.
You can use whichever you want.

Still, some ARCHE components define concepts which have to mapped to RDF properties to make everything work, e.g.

* arche-core requires you to assign an RDF property storing repository resource identifiers and a label(s)
* arche-resolver requires you to assign various RDF properties describing dissemination services behavior
  (see [here](https://acdh-oeaw.github.io/arche-docs/aux/dissemination_services.html))
* etc.

Here and know let's create a mapping for the arche-core only assuming we want to use Dublin Core 
wherever suitable and artificial predicates for everything else
(especially for the [technical predicates used by the API](https://acdh-oeaw.github.io/arche-docs/aux/search_api_for_programmers.html#technical-rdf-properties-provided-by-the-search).

To do that please modify the top part of the `schema` section of the `arche-docker-config/yaml/schema.yaml` so it looks as follows:

```yaml
schema:
    id: http://purl.org/dc/terms/identifier 
    parent: http://purl.org/dc/terms/isPartOf
    label: http://purl.org/dc/terms/title
    delete: delete://delete
    searchMatch: search://match
    searchOrder: search://order
    searchOrderValue: search://orderValue
    searchFts: search://fts
    searchCount: search://count
    binarySize: http://purl.org/dc/terms/extent
    fileName: file://name
    mime: http://purl.org/dc/terms/format
    hash: file://hash
    modificationDate: http://purl.org/dc/terms/modified
    modificationUser: http://purl.org/dc/terms/contributor
    binaryModificationDate: file://modified
    binaryModificationUser: file://modifiedUser
    creationDate: http://purl.org/dc/terms/created
    creationUser: http://purl.org/dc/terms/creator
```

### Ingesting some data

Let's ingest one metadata-only resource and a TEI-XML file as its child.

* Create a 'sampleData` folder and the `sampleData/metadata.tll` in it containing:
  ```
  @prefix dc: <http://purl.org/dc/terms/>.
  <http://id.namespace/collection1> dc:title "Sample collection"@en .
  <http://id.namespace/Baedeker-Mittelmeer_1909.xml> dc:title "Sample TEI-XML"@en ;
      dc:isPartOf <http://id.namespace/collection1> .
  ```
* Run a dedicated temporary docker container which we will use for the ingestion
  (it will have the `sampleData` directory availble under `/data`):
  ```bash
  docker run --rm -ti --network host -v ./sampleData:/data php:8.1 bash
  ```
  and install software required for the ingestion:
  ```bash
  curl -L https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions > /usr/local/bin/install-php-extensions &&\
  chmod +x /usr/local/bin/install-php-extensions &&\
  install-php-extensions @composer &&\
  composer require acdh-oeaw/arche-ingest
  ```
* Ingest the metadata file with:
  ```bash
  vendor/bin/arche-import-metadata /data/metadata.ttl http://my.domain/api admin ADMIN_PSWD_as_set_in_.env_file
  ```
  * note down the URLs of created resources reported in the log, e.g.:
    ```bash
        created http://127.0.0.1/api/1 (2/2)
        created http://127.0.0.1/api/2 (1/2)
    ```
* Download and ingest the TEI-XML resource binary:
  ```bash
  curl https://arche.acdh.oeaw.ac.at/api/29688 > /data/Baedeker-Mittelmeer_1909.xml
  vendor/bin/arche-import-binary \
    /data \
    http://id.namespace \
    http://my.domain/api admin ADMIN_PSWD_as_set_in_.env_file \
    --skip not_exist
  ```



### Setting up basic OAI-PMH


### Plugging in checks on the data ingestion


### Adding PIDs resolver and a dissemination service

## Further considerations

Last but not least, if you have questions, please do not hesitate to [ask us](mailto:mzoltak@oeaw.ac.at).
