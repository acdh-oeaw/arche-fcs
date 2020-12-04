<?php

/*
 * The MIT License
 *
 * Copyright 2020 zozlak.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace acdhOeaw\arche\fcs;

/**
 * A container for SRU parameters
 *
 * @author zozlak
 */
class SruParameters {

    public $operation;
    public $version;
    public $query;
    public $fcsDescription;
    public $fcsContent;
    public $fcsDataViews;
    public $fcsAllowRewrite;
    public $startRecord;
    public $maximumRecords;
    public $recordXmlEscaping;
    public $recordSchema;
    public $resultSetTtl;
    public $stylesheet;
    public $queryType;
    public $sortKeys;
    public $renderedBy;
    public $httpAccept;
    public $responseType;
    public $recordPacking;
    public $facetSort;
    public $facetStart;
    public $facetLimit;
    public $facetCount;
    public $xFcsEndpointDescription;

    public function __construct(array $src) {
        $this->operation               = $src['operation'] ?? 'explain';
        $this->version                 = $src['version'] ?? '1.2';
        $this->fcsDescription          = ($src['x-fcs-endpoint-description'] ?? '') === 'true';
        $this->fcsContent              = explode(',', $src['x-fcs-context'] ?? '');
        $this->fcsDataViews            = explode(', ', $src['x-fcs-dataviews'] ?? '');
        $this->fcsAllowRewrite         = ($src['x-fcs-rewrites-allowed'] ?? '') === 'true';
        $this->query                   = $src['query'] ?? null;
        $this->startRecord             = $src['startRecord'] ?? 1; // startPosition
        $this->maximumRecords          = $src['maximumRecords'] ?? null; // maximumItems
        $this->recordXMLEscaping       = $src['recordXMLEscaping'] ?? 'XML';
        $this->recordSchema            = $src['recordSchema'] ?? null; // responseItemType
        $this->resultSetTTL            = $src['resultSetTTL'] ?? null;
        $this->stylesheet              = $src['Stylesheet'] ?? null;
        // SRU 2.0
        $this->queryType               = $src['queryType'] ?? 'cql';
        $this->sortKeys                = $src['sortKeys'] ?? null; // sortOrder
        $this->renderedBy              = $src['RenderedBy'] ?? 'client';
        $this->httpAccept              = $src['httpAccept'] ?? ($_SERVER['HTTP_ACCEPT'] ?? 'application/sru+xml'); // responseFormat
        $this->responseType            = $src['responseType'] ?? null;
        $this->recordPacking           = $src['recordPacking'] ?? 'packed';
        $this->facetSort               = $src['facetSort'] ?? null;
        $this->facetStart              = $src['facetStart'] ?? null;
        $this->facetLimit              = $src['facetLimit'] ?? null;
        $this->facetCount              = $src['facetCount'] ?? null;
        // FCS
        $this->xFcsEndpointDescription = $src['x-fcs-endpoint-description'] ?? false;
    }

}
