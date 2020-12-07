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
    public $xFcsAllowRewrite;
    public $xFcsEndpointDescription;
    public $xFcsContext;
    public $xFcsDataViews;
    public $scanClause;
    public $responsePosition;
    public $maximumTerms;

    public function __construct(array $src, string $defaultVersion) {
        $this->query                   = $src['query'] ?? null;
        $this->operation               = $src['operation'] ?? '';
        $this->version                 = $src['version'] ?? $defaultVersion;
        $this->recordXMLEscaping       = $src['recordXMLEscaping'] ?? 'xml';
        $this->recordSchema            = $src['recordSchema'] ?? null; // responseItemType
        $this->resultSetTTL            = $src['resultSetTTL'] ?? null;
        $this->stylesheet              = $src['Stylesheet'] ?? null;
        $this->recordPacking           = $src['recordPacking'] ?? ($this->version >= 2 ? 'packed' : 'xml');
        // searchRetrieve-specific
        $this->startRecord             = $src['startRecord'] ?? '1'; // startPosition
        $this->maximumRecords          = $src['maximumRecords'] ?? null; // maximumItems
        // scan-specific
        $this->scanClause              = $src['scanClause'] ?? null;
        $this->responsePosition        = $src['responsePosition'] ?? null;
        $this->maximumTerms            = $src['maximumTerms'] ?? null;
        // SRU 2.0
        $this->queryType               = $src['queryType'] ?? 'cql';
        $this->sortKeys                = $src['sortKeys'] ?? null; // sortOrder
        $this->renderedBy              = $src['RenderedBy'] ?? 'client';
        $this->httpAccept              = $src['httpAccept'] ?? ($_SERVER['HTTP_ACCEPT'] ?? 'application/sru+xml'); // responseFormat
        $this->responseType            = $src['responseType'] ?? null;
        $this->facetSort               = $src['facetSort'] ?? null;
        $this->facetStart              = $src['facetStart'] ?? null;
        $this->facetLimit              = $src['facetLimit'] ?? null;
        $this->facetCount              = $src['facetCount'] ?? null;
        // FCS extensions
        $this->xFcsAllowRewrite        = ($src['x-fcs-rewrites-allowed'] ?? '') === 'true';
        $this->xFcsEndpointDescription = $src['x-fcs-endpoint-description'] ?? false;
        $this->xFcsContext             = explode(',', $src['x-fcs-context'] ?? ''); // SRU error 1 if not exists
        $this->xFcsDataviews           = explode(',', $src['x-fcs-dataviews'] ?? ''); // SRU error 4 if not exists
        // default operation handling
        if ($this->operation === '') {
            if (!empty($this->query)) {
                $this->operation = 'searchRetrieve';
            } elseif (!empty($this->scanClause)) {
                $this->operation = 'scan';
            } else {
                $this->operation = 'explain';
            }
        }
    }

}
