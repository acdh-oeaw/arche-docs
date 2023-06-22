# First steps

## Prerequisites

You need:

* [git](https://git-scm.com/)
* [Docker](https://www.docker.com/)
* any unformatted text editor
* ability to work with a console

Remarks:

* This guide was tested on a linux (Ubuntu 22.04) machine.
  As we are using [Docker](https://www.docker.com/) it should be also reproducible on a Windows and macOS machine
  but this was not tested and there is a risk you will run into platform-specific issues.

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
    (e.g. http://127.0.0.1/api/11305 and http://127.0.0.1/api/11306)
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


### Ingesting some data


### Adding a dissemination service


### Setting up basic OAI-PMH


### Plugging in a doorkeeper


## Further considerations

Last but not least, if you have questions, please do not hesitate to [ask us](mailto:mzoltak@oeaw.ac.at).
