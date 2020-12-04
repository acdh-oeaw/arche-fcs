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

    const NMSP_SRU         = 'http://www.loc.gov/zing/srw/';
    const NMSP_ZEEREX      = 'http://explain.z3950.org/dtd/2.1/';
    const NMSP_DIAGNOSTICS = 'http://www.loc.gov/zing/srw/diagnostic/';
    const RECORD_SCHEMA    = 'http://explain.z3950.org/dtd/2.1/';

    private $doc;
    private $root;

    public function __construct(string $responseType, string $version) {
        $this->doc  = new DOMDocument('1.0', 'utf-8');
        $this->root = $this->doc->createElementNS(self::NMSP_SRU, "sru:$responseType");
        $this->doc->appendChild($this->root);
        $this->root->appendChild($this->doc->createElementNS(self::NMSP_SRU, 'sru:version', $version));
    }

    public function createElementNs(string $ns, string $el,
                                    ?string $value = null): DOMElement {
        return $this->doc->createElementNS($ns, $el, $value);
    }

    public function addDiagnostics(SruException $e): void {
        $e->appendToXmlNode($this->root);
    }

    public function addRecord(DOMNode $content, string $schema,
                              ?DOMNode $extra = null, string $packing = 'XML',
                              ?string $id = null, ?int $position = null): void {
        $rec = $this->doc->createElementNS(self::NMSP_SRU, 'sru:record');
        $rec->appendChild($this->doc->createElementNS(self::NMSP_SRU, 'sru:recordPacking', $packing));
        $rec->appendChild($this->doc->createElementNS(self::NMSP_SRU, 'sru:recordSchema', $schema));
        if (!empty($id)) {
            $rec->appendChild($this->doc->createElementNS(self::NMSP_SRU, 'sru:recordIdentifier', $id));
        }
        if ($position > 0) {
            $rec->appendChild($this->doc->createElementNS(self::NMSP_SRU, 'sru:recordPosition', $position));
        }
        $d = $this->doc->createElementNS(self::NMSP_SRU, 'sru:recordData');
        $d->appendChild($content);
        $rec->appendChild($d);
        if ($extra !== null) {
            $ex = $this->doc->createElementNS(self::NMSP_SRU, 'sru:extraRecordData');
            $ex->appendChild($extra);
            $rec->appendChild($ex);
        }
        $this->root->appendChild($rec);
    }

    public function __toString(): string {
        return $this->doc->saveXML();
    }

}
