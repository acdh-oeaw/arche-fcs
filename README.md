# ARCHE-FCS

A CLARIN [Federated Content Search 2.0](https://office.clarin.eu/v/CE-2017-1046-FCS-Specification.pdf) endpoint for [ARCHE](https://acdh-oeaw.github.io/arche-docs/).

## Limitations

* The CLARIN FCS requires search to be performed within the sentence context which is impossible in ARCHE. Therefore boolean operators are limited only to OR which isn't affected by this requirement.
* As there is only a *raw text* layer in ARCHE, both CLARIN FCS advanced search and specific OASIS-CQL indices in SRU search/CLARIN FCS basic search aren't supported.

## Installation

* In the www docroot run:
  ```
  composer require acdh-oeaw/arche-fcs
  ln -s vendor/acdh-oeaw/arche-fcs/index.php index.php
  cp vendor/acdh-oeaw/arche-fcs/config-sample.yaml config.yaml
  ```
* Adjust the `config.yaml` providing ARCHE database connection details and your FCS metadata details.

## Reference documentation

* [FCS resources list curated by the CLARIN](https://github.com/clarin-eric/awesome-fcs)
* [CLARIN FCS landing page](https://www.clarin.eu/content/federated-content-search-clarin-fcs)
* [CLARIN FCS statistics page](https://contentsearch.clarin.eu/rest/statistics)
* [CLARIN FCS 2.0 specification](https://office.clarin.eu/v/CE-2017-1046-FCS-Specification.pdf)
* [CLARIN FCS validator](https://clarin.ids-mannheim.de/fcs-endpoint-tester/app/)
* [SRU langing page](http://www.loc.gov/standards/sru/)
    * [differences between SRU 1.2 and 2.0](http://www.loc.gov/standards/sru/differences.html)

