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

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Description of SruResponse
 *
 * @author zozlak
 */
class SruResponse {

    const ZEEREX_NMSP         = 'http://explain.z3950.org/dtd/2.1/';
    const DIAGNOSTICS_NMSP_1  = 'http://www.loc.gov/zing/srw/diagnostic/';
    const DIAGNOSTICS_NMSP_2  = 'http://docs.oasis-open.org/ns/search-ws/diagnostic';
    const RECORD_SCHEMA       = 'http://explain.z3950.org/dtd/2.1/';
    const SRU_MAX_VERSION     = '2.0';
    const SRU_NMSP_1          = 'http://www.loc.gov/zing/srw/';
    const SRU_NMSP_2          = 'http://docs.oasis-open.org/ns/search-ws/sruResponse';
    const SRU_NMSP_SCAN_2     = 'http://docs.oasis-open.org/ns/search-ws/scan';
    const COUNT_PREC_EXACT    = 'exact';
    const COUNT_PREC_UNKNOWN  = 'unknown';
    const COUNT_PREC_ESTIMATE = 'estimate';
    const COUNT_PREC_MAXIMUM  = 'maximum';
    const COUNT_PREC_MINIMUM  = 'minimum';
    const COUNT_PREC_CURRENT  = 'current';

    private $version;
    private $nmsp;
    private $doc;
    private $root;
    private $recordRoot;
    private $numberOfRecords;
    private $resultCountPrecision = self::COUNT_PREC_UNKNOWN;

    public function __construct(string $responseType, string $version) {
        $this->version = (float) $version;
        $this->nmsp    = $this->version >= 2 ? self::SRU_NMSP_2 : self::SRU_NMSP_1;
        if ($this->version >= 2 && $responseType === 'scan') {
            $this->nmsp = self::SRU_NMSP_SCAN_2;
        }
        $this->doc        = new DOMDocument('1.0', 'utf-8');
        $this->root       = $this->doc->createElementNS($this->nmsp, "sru:{$responseType}Response");
        $this->doc->appendChild($this->root);
        $this->root->appendChild($this->doc->createElementNS($this->nmsp, 'sru:version', sprintf('%.1f', $this->version)));
        $this->recordRoot = $this->root;

        if ($responseType === 'searchRetrieve') {
            $this->numberOfRecords = 0;
            $this->recordRoot      = $this->root->appendChild($this->doc->createElementNS($this->nmsp, 'sru:records'));
        }
    }

    public function createElementNs(string $ns, string $el,
                                    ?string $value = null): DOMElement {
        return $this->doc->createElementNS($ns, $el, $value);
    }

    public function addDiagnostics(SruException $e): void {
        $d = $this->root->appendChild($this->createElementNs($this->nmsp, 'sru:diagnostics'));
        $e->appendToXmlNode($d, $this->version >= 2 ? self::DIAGNOSTICS_NMSP_2 : self::DIAGNOSTICS_NMSP_1);
    }

    public function addRecord(?DOMNode $content, string $schema,
                              ?string $id = null, ?int $position = null): void {
        $rec = $this->recordRoot->appendChild($this->doc->createElementNS($this->nmsp, 'sru:record'));
        if ($content === null) {
            return;
        }
        $rec->appendChild($this->doc->createElementNS($this->nmsp, 'sru:recordSchema', $schema));
        if ($this->version >= 2) {
            $rec->appendChild($this->doc->createElementNS($this->nmsp, 'sru:recordXMLEscaping', 'xml'));
        } else {
            $rec->appendChild($this->doc->createElementNS($this->nmsp, 'sru:recordPacking', 'xml'));
        }
        if (!empty($id)) {
            $rec->appendChild($this->doc->createElementNS($this->nmsp, 'sru:recordIdentifier', $id));
        }
        $d = $this->doc->createElementNS($this->nmsp, 'sru:recordData');
        $d->appendChild($content);
        $rec->appendChild($d);
        if ($position > 0) {
            $rec->appendChild($this->doc->createElementNS($this->nmsp, 'sru:recordPosition', $position));
        }

        if ($this->numberOfRecords >= 0) {
            $this->numberOfRecords++;
        }
    }

    public function addNextRecordPosition(int $position): void {
        $this->root->appendChild($this->doc->createElementNS($this->nmsp, 'sru:nextRecordPosition', $position));
    }

    public function addExtraResponseData(DOMNode $extra): void {
        $ex = $this->root->appendChild($this->doc->createElementNS($this->nmsp, 'sru:extraResponseData'));
        $ex->appendChild($extra);
    }

    public function setNumberOfRecords(int $n,
                                       string $countPrecision = self::COUNT_PREC_UNKNOWN): void {
        $this->numberOfRecords      = -$n;
        $this->resultCountPrecision = $countPrecision;
    }

    public function __toString(): string {
        if ($this->root !== $this->recordRoot) {
            $this->root->insertBefore($this->doc->createElementNS($this->nmsp, 'sru:numberOfRecords', abs($this->numberOfRecords)), $this->root->firstChild->nextSibling);
            if ($this->numberOfRecords === 0) {
                $this->root->removeChild($this->recordRoot);
            } elseif ($this->version >= 2) {
                $this->root->appendChild($this->doc->createElementNS($this->nmsp, 'sru:resultCountPrecision', $this->resultCountPrecision));
            }
        }
        return $this->doc->saveXML();
    }

}
