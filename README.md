# ARCHE-FCS

A CLARIN [Federated Content Search 2.0](https://office.clarin.eu/v/CE-2017-1046-FCS-Specification.pdf) endpoint for [ARCHE](https://acdh-oeaw.github.io/arche-docs/).

Supports the basic search profile with AND, OR and NOT operators.

## Installation

* In the www docroot run:
  ```
  composer require acdh-oeaw/arche-fcs
  ln -s vendor/acdh-oeaw/arche-fcs/index.php index.php
  cp vendor/acdh-oeaw/arche-fcs/config-sample.yaml config.yaml
  ```
* Adjust the `config.yaml` providing ARCHE database connection details and your FCS metadata details.

## Reference documentation

* [CLARIN FCS 2.0](https://office.clarin.eu/v/CE-2017-1046-FCS-Specification.pdf)
* [SRU](http://www.loc.gov/standards/sru/)
    * [differences between SRU 1.2 and 2.0](http://www.loc.gov/standards/sru/differences.html)

