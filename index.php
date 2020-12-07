<?php

// https://office.clarin.eu/v/CE-2017-1046-FCS-Specification.pdf
// http://www.loc.gov/standards/sru/
// http://www.loc.gov/standards/sru/differences.html
require_once 'vendor/autoload.php';

use acdhOeaw\arche\fcs\Endpoint;

$cfg      = json_decode(json_encode(yaml_parse_file('config.yaml')));
$endpoint = new Endpoint($cfg);
$endpoint->handleRequest();

/*
$test = '>ns1="http://ns1v" > ns2 = "http://ns2v" arz_eng_006.author=="Karlheinz, Mörth" and (arz_eng_006.entry = ʕēn or arz_eng_006.entry =/ns1.mod=foo "مية") sortby dc.date/sort.descending/foo.bar dc.title';
$test = '"Karlheinz, Mörth" ʕēn "مية" lazy dog';
$test = '"Karlheinz, Mörth" and (ʕēn or "مية" and lazy OR "AND")';
//$test = '"Karlheinz, Mörth" and (ʕēn or "مية" and lazy dog)';
//$test = 'not ((a or b) and (c or d))';
$parser = new \acdhOeaw\cql\Parser($test);
*/