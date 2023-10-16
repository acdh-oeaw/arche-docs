# If you really need SPARQL

If you are determined to query the ARCHE data with the SPARQL, you need to

* Set up your own triplestore.
* Ingest data fetched from the ARCHE API into it.
* Query it.

It is actually easy to set up using an in-memory triplestore.

While it is not the most performance architecture one can imagine,
it should work fine (allow you to prepare the response below 1s)
if you fetch less than 10k triples from the ARCHE.

To assure it works as quickly as possible:

* fetch the RDF data in the `application/n-triples` format.
* fetch only the data you actually need (e.g. skip unneeded properties)
  - see [the metadata API guide](metadata_api_for_programmers.html)

## Example

Let's give it a try in Python and RDFlib:

```python
import requests
from datetime import datetime
from rdflib import Graph

# 1. Fetch the RDF metadata from ARCHE
t0 = datetime.now()
response = requests.get(
  'https://arche.acdh.oeaw.ac.at/api/512221/metadata',
  headers={
    'Accept': 'application/n-triples',
    'X-METADATA-READ-MODE': '2_0_1_1',
  }
)
rdfdata = response.text

# 2. Parse them into RDFlib Graph
t1 = datetime.now()
g = Graph()
g.parse(data=rdfdata, format='nt')

# 3. Run a SPARQL query on the RDFlib's Graph
t2 = datetime.now()
query = """
  SELECT ?title 
  WHERE {
    ?a <https://vocabs.acdh.oeaw.ac.at/schema#hasTitle> ?title . 
    ?a <https://vocabs.acdh.oeaw.ac.at/schema#isPartOf>+ <https://arche.acdh.oeaw.ac.at/api/512221> .
  }
"""
results = g.query(query)

# Print results
t3 = datetime.now()
print('Direct and second-level children of https://arche.acdh.oeaw.ac.at/api/512221:')
for row in g.query(query):
  print(f'{row.title}')

# Print some data on timing
t4 = datetime.now()
print(f'Graph with {len(g)} triples.')
T = (t4 - t0).total_seconds()
t4 = (t4 - t3).total_seconds()
t3 = (t3 - t2).total_seconds()
t2 = (t2 - t1).total_seconds()
t1 = (t1 - t0).total_seconds()
print(f'Fetch time        {t1} ({100 * t1 / T}%)')
print(f'Parsing time      {t2} ({100 * t2 / T}%)')
print(f'SPARQL query time {t3} ({100 * t3 / T}%)')
print(f'Printing time     {t4} ({100 * t4 / T}%)')
```

