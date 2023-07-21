# First steps to set up your own ARCHE Suite instance

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
   Now hit `CRTL+c`.  
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

and then restart the arche-core by hitting `CTRL+c` on the console where you run `docker compose up`
and running `docker compose up` again.

### Ingesting some data

Let's ingest one metadata-only resource and a TEI-XML file as its child.

* Create a 'sampleData` folder and the `sampleData/metadata.ttl` in it containing:
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
        created http://my.domain/api/1 (1/2)
        created http://my.domain/api/2 (2/2)
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
  * note the URL of the updated resource reported in the log, e.g.:
    ```bash
    Processing /data/Baedeker-Mittelmeer_1909.xml (1/2 50%): update + upload  http://my.domain/api/2
    ```
* Leave the ingestion container with
  ```bash
  exit
  ```

Now we have some rudimentary data and we can check if our metadata schema has been picked up.

Fetch the metadata of the TEI-XML binary.  
It is http://my.domain/api/2 in my case but check your ingestion logs for yours.
```bash
curl -u 'admin:ADMIN_PSWD_as_set_in_.env_file' 'http://my.domain/api/2/metadata?readMode=resource'
```
which in my case resulted in:
```
@prefix n0: <http://my.domain/api/>.
@prefix n1: <file://>.
@prefix n2: <http://purl.org/dc/terms/>.
@prefix n3: <http://id.namespace/>.
@prefix n4: <https://vocabs.acdh.oeaw.ac.at/schema#>.

<http://my.domain/api/2> n1:modified "2023-07-06T11:56:53.559750"^^<http://www.w3.org/2001/XMLSchema#dateTime>;
    n2:title "Sample TEI-XML"@en;
    n2:creator "admin";
    n2:isPartOf <http://my.domain/api/1>;
    n2:identifier <http://id.namespace/Baedeker-Mittelmeer_1909.xml>;
    n2:extent "32380001"^^<http://www.w3.org/2001/XMLSchema#integer>;
    n2:identifier <http://my.domain/api/2>;
    n2:modified "2023-07-06T11:56:53.659461"^^<http://www.w3.org/2001/XMLSchema#dateTime>;
    n4:aclRead "admin";
    n1:modifiedUser "admin";
    n1:hash "sha1:ad8a457099d70990f6d936182f0e3b2c35a19ad6";
    n2:contributor "admin";
    n4:aclWrite "admin";
    n1:name "Baedeker-Mittelmeer_1909.xml";
    n2:format "application/xml";
    n2:created "2023-07-06T11:56:30.757867"^^<http://www.w3.org/2001/XMLSchema#dateTime>.
```  

We can see that:

* The `dc:title`, `dc:identifier` and `dc:isPartOf`
  look exactly like we set them up in the `sampleData/metadata.ttl`.
* Quite many other metadata properties have been assigned automatically, e.g.
  * `dc:creator "admin"` because we used the `admin` account for the ingestion
  * `dc:extent "32380001"^^<http://www.w3.org/2001/XMLSchema#integer>`
    and `<file://hash> "sha1:ad8a457099d70990f6d936182f0e3b2c35a19ad6"`
    because the arche-core computed them while storing the uploaded file
  * `<file://name> "Baedeker-Mittelmeer_1909.xml"` because the upload script
    provided this information while uploading the file
  * etc.
* We can also see some access control-related properties like
  `<https://vocabs.acdh.oeaw.ac.at/schema#aclWrite "admin"`
  but this is discussed in the next chapter.


You can also try:

* Fetching metadata of a given resource and all repository resources it points to in a single REST API call:
  ```bash
  curl -u 'admin:ADMIN_PSWD_as_set_in_.env_file' 'http://my.domain/api/2/metadata?readMode=neighbors'
  ```
* Fetching the resource binary:
  ```
  curl -u 'admin:ADMIN_PSWD_as_set_in_.env_file' 'http://my.domain/api/2'
  ```
* Checking that without providing the username and the password, you are denied access
  ```
  curl -i http://my.domain/api/2
  ```

### Acess control

Access control is based on *roles* which are generalization of the *user* and *group* concepts.

* During the arche-core initialization an `admin` *role* is created.
* There is also a `public` role which is used to indicate unauthenticated users.

You can create and modify users using the `{repo base URL}/user` REST API endpoint
(see [swagger API documentation](https://app.swaggerhub.com/apis/zozlak/arche) for details), e.g.

* List existing roles with (the output is in JSON):
  ```bash
  curl -u 'admin:ADMIN_PSWD_as_set_in_.env_file' 'http://my.domain/api/user'
  ```
* Add a new role `bob` belonging to the `creators` role:
  ```bash
  curl -i -u 'admin:anotherStrongPassword' 'http://my.domain/api/user/bob' \
    -X PUT \
    -H 'Content-Type: application/json' \
    --data-binary '{"groups": ["creators"], "password": "randomPassword"}'
  ```

The access control settings are stored in the `accessControl` section of the `arche-docker-config/yaml/repo.yaml` file.

In our case it should look more or less as follows:

```yaml
accessControl:
    publicRole: public
    adminRoles:
        - admin
    create:
        # who can create new resources
        allowedRoles:
            - creators
        # rights assigned to the creator uppon resource creation
        creatorRights:
            - read
            - write
        # rights assigned to other roles upon resource creation
        assignRoles:
            read: []
    defaultAction:
        read: deny
        write: deny
    enforceOnMetadata: true
    schema:
        read: https://vocabs.acdh.oeaw.ac.at/schema#aclRead
        write: https://vocabs.acdh.oeaw.ac.at/schema#aclWrite
    db:
        connStr: 'pgsql: user={PG_USER_PREFIX}repo dbname={PG_DBNAME} host={PG_HOST} port={PG_PORT}'
        table: users
        userCol: user_id
        dataCol: data
    authMethods:
        - class: \zozlak\auth\authMethod\TrustedHeader
          parameters:
            - HTTP_EPPN
        - class: \zozlak\auth\authMethod\HttpBasic
          parameters:
             - repo
        - class: \zozlak\auth\authMethod\Guest
          parameters:
             - public
```

Let's analyze it step-by-step:

* `publicRole: public` - defines the name of the role used to indicate unauthorized user
* ```yaml
   adminRoles:
       - admin
  ```
  defines the list of admin roles.
  Admin rights are needed to create new roles.
  Also, having the admin rights allows to freely create, read, modify and delete repository resources.
* ```yaml
   create:
        allowedRoles:
            - creators
        creatorRights:
            - read 
            - write
        assignRoles:
            read: []
  ```
  defines who can create new repository resources 
  and what are the default access rights being set on newly created resources.
  Here:
  * only *roles* belonging to the *creators* role (and, obviously, admins) can create new resources
  * the creator is automatically assigned *read* and *write* rights on a created resource
    (which is a sane default but e.g. dropping `write` from this list would enforce
    creation of immutable repository resources, at least until you are and admin)
  * no other roles are assigned rights on a newly create resource
    * if you would like a newly created resource to be read-only by everyone you could use
      ```yaml
      assignRoles:
            read: [public]
      ```
* ```yaml
  defaultAction:
        read: deny
        write: deny
  ```
  determines what to use if no access control rule has been matched.
  In this case the access is denied.
  You can e.g. consider setting `write: allow`.
* `enforceOnMetadata: true` enforces the **read** access rights
  to be applied also to metadata (the write access rights are always applied
  both to the metadata and resource binary content).
* The `schema` section defines RDF properties used to store 
  access control information in the metadata.
  You can choose them however you want, just do the adjustment before 
  you start ingesting resources into the repository.
* The `db` section contains internal config we will not dig into.
* The `auth` section contains configuration of the [zozlak/auth](https://github.com/zozlak/auth)
  authentication framework. In this case:
  * First we check for the presence of the `EPPN` HTTP header
    and if it exists, we take the *role* name from the header value.
    This is quite a common integration scenario for the single sign-on
    authorization methods like the Shibboleth Apache module.
  * Then a standard username & password based authentication is used.
  * If both failed, a fixed role `public` is assumed.

Now let's try to use the `bob` role we created in the examples at the beginning of the chapter
to allow public read rights on the TEI-XML resource (http://my.domain/api/2 in my case).

First, we need to allow `bob` to modify the resouces which is currently possible only for the `admin` role
(and all roles with admin priviledges).
This can be done

* By adding `bob` to the `accessControl.adminRoles` list in the arche-docker-config/yaml/repo.yaml
  and restarting the repository's Docker container. But we will not use it as we do not want `bob`
  to become an admin.
* Or by granting `bob` writes to modify the resource.
  Presicely by adding the `accessControl.schema.write "bob"` triple
  (in our case `<https://vocabs.acdh.oeaw.ac.at/schema#aclWrite> "bob"`) to resource's metadata.
  This is the solution we prefer.

For that let's create a `sampleData/acl1.ttl` file:
```
<http://id.namespace/Baedeker-Mittelmeer_1909.xml> <https://vocabs.acdh.oeaw.ac.at/schema#aclWrite> "bob", "admin" .
<http://id.namespace/Baedeker-Mittelmeer_1909.xml> <https://vocabs.acdh.oeaw.ac.at/schema#aclRead> "bob", "admin" .
```

After ingesting it `bob` should be able to grant public read writes by importing a `sampleData/acl2.ttl`:
```
<http://id.namespace/Baedeker-Mittelmeer_1909.xml> <https://vocabs.acdh.oeaw.ac.at/schema#aclRead> "public" .
```

Let's ingest both metadata files the same way we did before:

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
* Try to ingest the `acl1.ttl` as the `bob` user:
  ```bash
  vendor/bin/arche-import-metadata /data/acl1.ttl http://my.domain/api bob randomPassword
  ```
  and note it failed to perform the update
  ```bash
  (...)
  Ingested resources count: 0 errors count: 0
  ```
* No try again as `admin`
  ```bash
  vendor/bin/arche-import-metadata /data/acl1.ttl http://my.domain/api admin ADMIN_PSWD_as_set_in_.env_file
  ```
  which should succeed
  ```bash
  (...)
  Importing http://id.namespace/Baedeker-Mittelmeer_1909.xml (1/1)
      updating http://my.domain/api/2 (1/1)
  Ingested resources count: 1 errors count: 0
  ```
* At this point bob should have rights to perform the update
  ```bash
  vendor/bin/arche-import-metadata /data/acl2.ttl http://my.domain/api bob randomPassword
  ```
* Leave the ingestion container
  ```bash
  exit
  ```
* And we should be able to read the resource without any authentication:
  ```bash
  curl -i 'http://my.domain/api/2/metadata?readMode=resource'
  curl 'http://my.domain/api/2'
  ```

### Setting up a basic OAI-PMH

Now let's try to add an [OAI-PMH](https://www.openarchives.org/pmh/)
service to our repository to make it harvestable by external aggregators.

We can deploy it either in the same docker container as the arche-core or in a separate one.
In this example we will use the first approach.

* Add `acdh-oeaw/arche-oaipmh` to the packages list in `arche-docker-config/composer.json`:
  ```json
  {
    "require": {
      "acdh-oeaw/arche-core": "^3",
      "zozlak/yaml-merge": "^1",
      "acdh-oeaw/arche-oaipmh": "^4.2"
    }
  }
  ```
  This will make the [arche-oaipmh](https://github.com/acdh-oeaw/arche-oaipmh) being installed
  on the Docker container startup.
* Create and **make executable** the `arche-docker-config/run.d/oaipmh.sh` file
  initializing the OAI-PMH service under the `{repoBaseUrl}/oaipmh` path:
  ```bash
  #!/bin/bash
  if [ ! -d /home/www-data/docroot/oaipmh ]; then
      su -l www-data -c 'mkdir /home/www-data/docroot/oaipmh'
      su -l www-data -c 'ln -s /home/www-data/vendor /home/www-data/docroot/oaipmh/vendor'
  fi
  su -l www-data -c 'cp /home/www-data/vendor/acdh-oeaw/arche-oaipmh/.htaccess /home/www-data/docroot/oaipmh/.htaccess'
  su -l www-data -c 'cp /home/www-data/vendor/acdh-oeaw/arche-oaipmh/index.php /home/www-data/docroot/oaipmh/index.php'

  CMD=/home/www-data/vendor/zozlak/yaml-merge/bin/yaml-edit.php
  CFGD=/home/www-data/config/yaml
  rm -f /home/www-data/docroot/oaipmh/config.yaml $CFGD/config-oaipmh.yaml
  su -l www-data -c "$CMD --src $CFGD/oaipmh.yaml --src $CFGD/local.yaml $CFGD/config-oaipmh.yaml"
  su -l www-data -c "ln -s $CFGD/config-oaipmh.yaml /home/www-data/docroot/oaipmh/config.yaml"
  ```
* Create a minimal OAI-PMH service configuration file `arche-docker-config/yaml/oaipmh.yaml`:
  ```yaml
  oai:
    info:
        repositoryName: my repository
        baseURL: http://my.domain/oaipmh/
        earliestDatestamp: "1900-01-01T00:00:00Z"
        adminEmail: admin@my.domain
        granularity: YYYY-MM-DDThh:mm:ssZ
    # the guest user is created during the arche-core initialization and we can reuse it
    dbConnStr: "pgsql: user=guest dbname=postgres host=postgresql"
    cacheDir: ""
    logging:
        file: /home/www-data/log/oaipmh.log
        level: info
    deleted:
        deletedClass: \acdhOeaw\arche\oaipmh\deleted\Tombstone
        deletedRecord: transient
    search:
        searchClass: \acdhOeaw\arche\oaipmh\search\BaseSearch
        dateProp: http://purl.org/dc/terms/modified
        idNmsp: http://my.domain
        id: http://purl.org/dc/terms/identifier
        searchMatch: search://match
        searchCount: search://count
        repoBaseUrl: http://my.domain/api/
        resumptionTimeout: 120
        resumptionDir: "tmp"
        resumptionKeepAlive: 600
    sets:
        setClass: \acdhOeaw\arche\oaipmh\set\NoSets
    formats:
        oai_dc:
            metadataPrefix: oai_dc
            schema: http://www.openarchives.org/OAI/2.0/oai_dc.xsd
            metadataNamespace: http://www.openarchives.org/OAI/2.0/oai_dc/
            class: \acdhOeaw\arche\oaipmh\metadata\DcMetadata
  ```
  For more details please look at the [sample config](https://github.com/acdh-oeaw/arche-oaipmh/blob/master/config-sample.yaml)
  provided in the arche-oaimph git repository and at the 
  [metadata format classes documentation](https://acdh-oeaw.github.io/arche-docs/devdocs/namespaces/acdhoeaw-arche-oaipmh-metadata.html).
* Restart the arche-core by hitting `CTRL+c` on the console where you run `docker compose up`
  and running `docker compose up` again.

We should have the OAI-PMH service with a very basic configuration running now.
You can try:

* http://my.domain/oaipmh/?verb=Identify
* http://my.domain/oaipmh/?verb=ListMetadataFormats
* http://my.domain/oaipmh/?verb=ListIdentifiers
* http://my.domain/oaipmh/?verb=ListRecords&metadataPrefix=oai_dc

As we internally store metadata in the Dublin Core schema, it was possible to use the very simple
metadata format class `\acdhOeaw\arche\oaipmh\metadata\DcMetadata` which does not require any
additional config.
In real-world scenarios you will almost for sure need to prepare templates using the
`\acdhOeaw\arche\oaipmh\metadata\LiveCmdiMetadata` class which will map your internal
metadata schema into the schema you want to provide to an external aggregator.
Please read [this documentation](https://acdh-oeaw.github.io/arche-docs/devdocs/classes/acdhOeaw-arche-oaipmh-metadata-LiveCmdiMetadata.html)
and [template examples used at the ACDH-CH](https://github.com/acdh-oeaw/arche-docker-config/tree/arche/oaipmhTemplates).

### Plugging in checks on the data ingestion

Now an advanced topic - plugging your own logic into the arche-core.

This is possible in two ways:

* With handlers written in PHP
  * For `txBegin`, `txCommit` and `txRollback` events your handler signature should be:
    ```php
    function myHandler(
        string $event, 
        int $transactionId, 
        array<int> $idsOfResourcesModifiedByTheTransaction)
    ): void
    ```
  * For all other events
    (`get`, `getMetadata`, `create`, `updateBinary`, `updateMetadata`, `delete`, `deleteTombstone`)
    your handler signature should be:
    ```php
    function myHandler(
        string $event,
        EasyRdf\Resource $resourceMetadata,
        ?string $pathToBinaryPayload
    ): EasyRdf\Resource
    ```
    and it should return the final metadata of the resource (as an `EasyRdf\Resource` object).
  * Handlers can break hadnling of a request by throwing an exception.
    In such a case the exception code is used as HTTP response code
    (e.g. 400 to indicate error in data provided by a user)
    and exception message is provided in the response body.
  * To register such a handler:
    * Make sure its code is being loaded or autoloadable.
    * Register it in the `rest.handlers` section of the `arche-docker-config/yaml/repo.yaml` file, e.g.
      ```yaml
      rest:
        handlers:
          create:
          - type: function
            function: myClassName::myHandler
      ```
  * You can search for an inspiration by looking at
    [our handlers code](https://github.com/acdh-oeaw/arche-doorkeeper/blob/master/src/acdhOeaw/arche/doorkeeper/Doorkeeper.php)
    and the way we [registered them in the configuration](https://github.com/acdh-oeaw/arche-docker-config/blob/arche/yaml/doorkeeper.yaml#L24).
* With handlers written in [any language with AMQP support](https://www.rabbitmq.com/devtools.html)
  and communicating with the arche-core over an AMQP broker (e.g. the [RabbitMQ](https://www.rabbitmq.com/)).
  * The message passed to the handler is a JSON object with following properties:
    * For `txBegin`, `txCommit` and `txRollback` events:
      * `method` - event name, e.g. `get`, `updateMetadata`, etc.
      * `transactionId` - identifier of the transaction
      * `resourceIds` - array containing interal ids of repository resouced 
        modified by this transaction (e.g. `[3, 8, 125]`)
    * For all other events
      (`get`, `getMetadata`, `create`, `updateBinary`, `updateMetadata`, `delete`, `deleteTombstone`):
      * `method` - event name, e.g. `get`, `updateMetadata`, etc.
      * `path` - path to the resource binary content
      * `URI` - full resource URI, e.g. http://my.domain/api/2 
      * `id` - numeric internal id of the resource, e.g. `2`
      * `metadata` - RDF metadata of the resources in the application/n-triples format
  * the handler is expected to respond with a message being a JSON object containing
    the `status` field.
    * Status of 0 means handler executed successfully.
      In case of `txBegin`, `txCommit` and `txRollback` events, that is it.
      For other events the respons from the handler should also provide target resource's
      metadata serialized in `application/n-triples` in the `metadata` property.
    * Non-0 status indicates an error.
      In such a case the request processing is being stopped and an error response is sent to the client
      with an HTTP status code equal to the `status` property value and the response body containing
      the optional `message` property value (if it is missing, a default error message is used).

By the way it is possible to mix both methods.

As the first approach (PHP handlers with no AMQP) is well illustrated in our own ARCHE Suite deployment
code (you may look [here](https://github.com/acdh-oeaw/arche-doorkeeper/blob/master/src/acdhOeaw/arche/doorkeeper/Doorkeeper.php)
and [here](https://github.com/acdh-oeaw/arche-docker-config/blob/arche/yaml/doorkeeper.yaml#L24)),
in this tutorial we will take the second approach and implement a simple metadata consistency check handler in Python
over the AMQP.

* Let's start with adding a [RabbitMQ](https://www.rabbitmq.com/) broker service to our
  Docker containers stack.
  It will pass messages between the arche-core and our handlers service.
  * Extend the `services` section of the `docker-compose.yaml` with:
    ```
    services:
      rabbitmq:
        image: rabbitmq
        networks:
        - backend
    ```
* We also need an execution enviromnent for our Python handlers service.
  This makes another Docker container.
  * Extend the `services` section of the `docker-compose.yaml` with:
    ```
    services:
      handlers:
        image: python:3.11
        networks:
        - backend
        volumes:
        - ./handlers:/opt
        entrypoint: /opt/start.sh
    ```
* Now let's create the starup script for our handlers Docker container.
  It will install required Python libraries and then start our handlers
  service.  
  * Create the `handlers` directory
  * Create and **make executable** `handlers/start.sh`:
    ```bash
    #!/bin/bash

    # install required python modules
    pip install pika
    pip install rdflib
    # give rabbitmq some time to start
    sleep 10
    # start our handlers service
    python3 /opt/handlers.py
    ```
* Create a simple handler code.  
  It will check if the `dc:license` triple is defined in the resource metadata.  
  On top of it we need a little boilerplate code to couple it with the AMQP broker.  
  Edit `handlers/handlers.py` so it contains:
  ```python
  import pika
  import json
  from rdflib import Graph, URIRef

  # this is our handler
  def checkMeta(channel, deliver, msgProperties, body):
    message = json.loads(body.decode('utf-8'))
    g = Graph()
    g.parse(data=message['metadata'])
    # if http://purl.org/dc/terms/license is not provided
    # return an empty graph and set an error message
    if (None, URIRef('http://purl.org/dc/terms/license'), None) not in g:
      retMsg = {
        'status': 400,
        'message': 'dc:license triple is missing'
      }
    else:
      retMsg = {
        'status': 0,
        'metadata': g.serialize(format='nt')
      }
    channel.basic_publish(
      exchange=deliver.exchange, 
      routing_key=msgProperties.reply_to,
      body=json.dumps(retMsg),
      properties=msgProperties
    )

  # boilerplate code coupling our handler with the AMQP broker
  connCfg = pika.ConnectionParameters(host='rabbitmq')
  connection = pika.BlockingConnection(connCfg)
  channel = connection.channel()
  channel.basic_qos(0, 1, False)
  # create the 'onModify' queue
  channel.queue_declare(queue='onModify')
  # set up a handler function
  channel.basic_consume(
    queue='onModify', 
    on_message_callback=checkMeta,
    auto_ack=True
  )
  channel.start_consuming()
  ```
* Tell the arche-core to use AMQP handlers.
  Edit the `rest.handlers` section of the `arche-docker-config/yaml/repo.yaml`:
  ```yaml
  handlers:
    rabbitMq:
      host: rabbitmq
      port: 5672
      user: guest # default settings of the rabbitmq docker image
      password: guest # default settings of the rabbitmq docker image
      timeout: 5 # handler execution timeout in seconds
      exceptionOnTimeout: true
    methods:
      create:
      - type: rpc
        queue: onModify # must match the queue name in Python code
      updateBinary: []
      updateMetadata:
      - type: rpc
        queue: onModify # must match the queue name in Python code
      txCommit: []
  ```
* Restart the arche-core by hitting `CTRL+c` on the console where you run `docker compose up`
  and running `docker compose up` again.  
  Wait until you see ` 
* Test if it works
  * Run a dedicated temporary docker container which we will use for the ingestion (it will have the sampleData directory availble under /data):
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
  * Try to reingest the `metadata.ttl` file we used in the previous chapter:
    ```bash
    vendor/bin/arche-import-metadata /data/metadata.ttl http://my.domain/api admin ADMIN_PSWD_as_set_in_.env_file
    ```
    You should get something like
    ```
    (...)
    Importing http://id.namespace/Baedeker-Mittelmeer_1909.xml (2/2)
	updating http://my.domain/api/1 (1/2)
	updating http://my.domain/api/2 (2/2)
	ERROR while processing http://id.namespace/collection1: 400 dc:license triple is missing(GuzzleHttp\Exception\ClientException)
	ERROR while processing http://id.namespace/Baedeker-Mittelmeer_1909.xml: 400 dc:license triple is missing(GuzzleHttp\Exception\ClientException)
    (...)
    ```
    which is exactly what we wanted - our metadata check procedure revoked
    the metadata update requests with the HTTP code 400 (Bad Request)
     because the `dc:licence` RDF triple was missing.
    * If you got another error code, try looking at the logs
      in the `docs/rest.log`
  * Edit the `sampleData/metadata.ttl` adding `dc:license triples`
    for both resources to it, e.g.:
    ```
    @prefix dc: <http://purl.org/dc/terms/>.
        <http://id.namespace/collection1> dc:title "Sample collection"@en ;
        dc:license "CC BY-NC" .
    <http://id.namespace/Baedeker-Mittelmeer_1909.xml> dc:title "Sample TEI-XML"@en ;
        dc:isPartOf <http://id.namespace/collection1> ;
        dc:license "CC BY-NC".
    ```
  * Try to ingest again:
    ```bash
    vendor/bin/arche-import-metadata /data/metadata.ttl http://my.domain/api admin ADMIN_PSWD_as_set_in_.env_file
    ```
    This time the ingestion should succeed.
    * If you git an error, try looking at the logs in the `docs/rest.log`
* Congratulations, you have just created and tested a very basic
  data consistency check.

Remarks about a production environment usage:

* If you want to use multiple AMQP handlers for different events
  (`txBegin/txCommit/txRollback/get/getMetadata/create/updateBinary/updateMetadata/delete/deleteTombstone`),
  you should assign each of them a unique queue name.
* You should rather make a dedicated Docker image containing a complete 
  execution environment for your handlers service rather than installing 
  libraries as a part of the `handlers/start.sh`.
* You should either set up RabbitMQ authorization or make sure you
  run in in an izolated and trusted network.
* Instead of just waiting 10 seconds for the RabbitMQ to start you should
  rather implement a loop checking and waiting until the service is available
  either in the `handlers/start.sh` or in the `handlers/handlers.py`.
* If you expect higher repository loads, you may need to write your
  handlers in a way multiple checks can run at once. Please consult
  the AMQP broker of your choice (e.g. the RabbigMQ) documentation
  to check how to do that.
  * This problem does not exist in case of handlers written in PHP
    and not using the AMQP. 

Other remarks:

* The [arche-openaire](https://github.com/acdh-oeaw/arche-openaire/) repository
  provides a set of handlers for integrating the ARCHE Suite repository with
  the [OpenAIRE usage statistics tracking](https://openaire.github.io/usage-statistics-guidelines/service-specification/service-spec/)

### Adding PIDs resolver and a dissemination service

You probably want to assign [PIDs](https://en.wikipedia.org/wiki/Persistent_identifier)
to resources in your repository.
You can depend on an external PIDs service for that (e.g. a https://www.handle.net/) but
this requires a constant maintenance (e.g. if you migrate your repository base URL, you
need to update all the handles in the external service on your own).
Alternatively you can set up your own PIDs service based on the ARCHE Suite repository
metadata.
For that you just need a dedicated (sub)domain and a deployment of the
[arche-resolver](https://github.com/acdh-oeaw/arche-resolver/) module.
The `arche-resolver` will also allow you to provide users with the content type negotation,
e.g. redirect them to a service converting the resource as it is stored in the repository
to another format.

Let's try (deploying the resolver module within the arche-core Docker container
like we did for the OAI-PMH):

* Choose a domain for your PIDs.
  Here we will use th `my.pid`.
  * For the local testing define it as pointing to the `127.0.0.1` in the `/etc/hosts`
    just like we did at the very beginning for the `my.domain`:
    ```bash
    (...)
    127.0.0.1 my.pid
    ```
* Create the `arche-docker-config/yaml/resolver.yaml` file containing the resolver module config
  (consider reading comments provided in the code below):
  ```yaml
  # this section should be in line with corresponding settings
  # in the arche-docker-config/yaml/schema.yaml
  schema:
    id: http://purl.org/dc/terms/identifier
    parent: http://purl.org/dc/terms/isPartOf
    label: http://purl.org/dc/terms/title
    searchMatch: search://match
    searchFts: search://fts
    # this section defines RDF properties used to describe dissemination services
    # here we just reuse the same settings we use at our OEAW deployment
    # see https://acdh-oeaw.github.io/arche-docs/aux/dissemination_services.html
    # for details
    dissService:
      class: https://vocabs.acdh.oeaw.ac.at/schema#DisseminationService
      location: https://vocabs.acdh.oeaw.ac.at/schema#serviceLocation
      returnFormat: https://vocabs.acdh.oeaw.ac.at/schema#hasReturnType
      matchProperty: https://vocabs.acdh.oeaw.ac.at/schema#matchesProp
      matchValue: https://vocabs.acdh.oeaw.ac.at/schema#matchesValue
      matchRequired: https://vocabs.acdh.oeaw.ac.at/schema#isRequired
      revProxy: https://vocabs.acdh.oeaw.ac.at/schema#serviceRevProxy
      parameterClass: https://vocabs.acdh.oeaw.ac.at/schema#DisseminationServiceParameter
      parameterDefaultValue: https://vocabs.acdh.oeaw.ac.at/schema#hasDefaultValue
      parameterRdfProperty: https://vocabs.acdh.oeaw.ac.at/schema#usesRdfProperty
      hasService: https://vocabs.acdh.oeaw.ac.at/schema#hasDissService
  resolver:
    logging:
      file: /var/www/html/log
      level: warning
    idProtocol: http
    idHost: my.pid
    idPathBase: ''
    defaultDissService: raw
    # redirects for dissemination formats provided by the arche-core
    fastTrack:
      raw: ''
      application/octet-stream: ''
      rdf: /metadata
      text/turtle: /metadata
      application/n-triples: /metadata
      application/rdf+xml: /metadata
      application/ld+json: /metadata
    repositories:
      # the resolver is capable of searching against multiple arche-core
      # instances but we have only one so we set up only one
      main:
        baseUrl: http://my.domain
  ```

### Batch-updating metadata

From time to time you might want to perform a batch-update of the metadata.
The most common scenario are chagnes made in the metadata schema.

Performing such changes using the REST API is technically possible but very troublesome and time-consuming.
Instead of that you can directly access the metadata database and modify it using SQL queries.

Let's say we want to change the RDF predicates used to store access control information:

* turn `https://vocabs.acdh.oeaw.ac.at/schema#aclRead` into `acl://read`
* turn `https://vocabs.acdh.oeaw.ac.at/schema#aclWrite` into `acl://write`

First, we modify the `accessControl.schema` settings in the `arche-docker-config/yaml/repo.yaml`
but this will only affect future interactions trought the REST API
so we have to update all already existing triples not to mess up everything.

Fortunately it's pretty straigtforward:

* As in this guide we are running the database in a separate Docker container called `postgresql`:
  * check the exact container name with `docker ps` - it will be the one with `postgresql` in name
    (e.g. `tmp-postgresql-1` in my case)
  * run `psql arch` inside of it with
    ```bash
    docker exec -ti -u postgres tmp-postgresql-1 psql arche
    ```
* Update the metadata:
  ```sql
  BEGIN;
  UPDATE metadata SET property = 'acl://read' WHERE property = 'https://vocabs.acdh.oeaw.ac.at/schema#aclRead';
  UPDATE metadata SET property = 'acl://write' WHERE property = 'https://vocabs.acdh.oeaw.ac.at/schema#aclWrite';
  COMMIT;
  ```
* Exit with
  ```
  \q
  ```

The direct database access can be also used to analyze the metadata, e.g.
quickly compute distribution of RDF predicated values, etc.

A little more information on the database structure is provided [here](https://github.com/acdh-oeaw/arche-core#database-structure).

## Further considerations

TODO

Last but not least, if you have questions, please do not hesitate to [ask us](mailto:mzoltak@oeaw.ac.at).
