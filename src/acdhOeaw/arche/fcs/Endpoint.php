<?php

/*
 * The MIT License
 *
 * Copyright 2020 Austrian Centre for Digital Humanities.
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
 * Description of Endpoint
 *
 * @author zozlak
 */
class Endpoint {

    const NMSP_FCS_ENDPOINT_DESC = 'http://clarin.eu/fcs/endpoint-description';
    const CPBLT_BASIC_SEARCH     = 'http://clarin.eu/fcs/capability/basic-search';
    const MIME_FCS_HITS          = 'application/x-clarin-fcs-hits+xml';

    /**
     *
     * @var object
     */
    private $cfg;

    public function __construct(object $cfg) {
        $this->cfg = $cfg;
    }

    public function handleRequest(): void {
        $resp = new SruResponse('explain', '1.2');
        try {
            switch ($_SERVER['REQUEST_METHOD'] ?? '') {
                case 'GET':
                    $src = $_GET;
                    break;
                case 'POST';
                    $src = $_POST;
                    break;
                default:
                    http_response_code(405);
                    echo 'Method not allowed';
                    return;
            }
            $param = new SruParameters($src);

            switch ($param->operation) {
                case 'explain':
                    $resp = $this->handleExplain($param);
                    break;
                case 'scan':
                    $resp = $this->handleScan($param);
                    break;
                case 'searchRetrieve':
                    $resp = $this->handleSearch($param);
                    break;
                default:
                    http_response_code(400);
                    echo "Unknown operation $param->operation";
                    return;
            }
            header('Content-Type: application/xml');
            echo (string) $resp;
        } catch (SruException $e) {
            $resp->addDiagnostics($e);
            header('Content-Type: application/xml');
            echo (string) $resp;
        } catch (FcsException $e) {
            // in the rare case of SRU reporting errors with HTTP codes do nothing
            if ($e->getCode() < 400 || $e->getCode() >= 500) {
                throw $e;
            }
        }
    }

    private function handleExplain(SruParameters $param): SruResponse {
        $this->checkParam($param, 'explain');
        $resp    = new SruResponse('explain', $param->version);
        $explain = $resp->createElementNs(SruResponse::NMSP_ZEEREX, 'zr:explain');

        $si = $explain->appendChild($resp->createElementNs(SruResponse::NMSP_ZEEREX, 'zr:serverInfo'));
        $si->setAttribute('protocol', 'SRU');
        $si->setAttribute('version', '1.2');
        $si->setAttribute('transport', $this->cfg->serverInfo->transport);
        $si->setAttribute('method', $this->cfg->serverInfo->method);
        $si->appendChild($resp->createElementNs(SruResponse::NMSP_ZEEREX, 'zr:host', $this->cfg->serverInfo->host));
        $si->appendChild($resp->createElementNs(SruResponse::NMSP_ZEEREX, 'zr:port', $this->cfg->serverInfo->port));
        $si->appendChild($resp->createElementNs(SruResponse::NMSP_ZEEREX, 'zr:database', $this->cfg->serverInfo->database));

        $db = $explain->appendChild($resp->createElementNs(SruResponse::NMSP_ZEEREX, 'zr:databaseInfo'));
        foreach ($this->cfg->databaseInfo as $element => $values) {
            foreach ($values as $lang => $value) {
                $el = $db->appendChild($resp->createElementNs(SruResponse::NMSP_ZEEREX, "zr:$element", $value));
                $el->setAttribute('lang', $lang);
            }
        }

        $si = $explain->appendChild($resp->createElementNs(SruResponse::NMSP_ZEEREX, 'zr:schemaInfo'));
        $s  = $si->appendChild($resp->createElementNs(SruResponse::NMSP_ZEEREX, 'zr:schema'));
        $s->setAttribute('name', 'fcs');
        $s->setAttribute('identifier', 'http://clarin.eu/fcs/resource');

        $ci = $explain->appendChild($resp->createElementNs(SruResponse::NMSP_ZEEREX, 'zr:configInfo'));
        foreach ($this->cfg->configInfo as $element => $values) {
            foreach ($values as $property => $value) {
                $el = $ci->appendChild($resp->createElementNs(SruResponse::NMSP_ZEEREX, "zr:$element", $value));
                $el->setAttribute('type', $property);
            }
        }

        $ed = null;
        if ($param->xFcsEndpointDescription === 'true') {
            $ed   = $resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:EndpointDescription');
            $ed->setAttribute('version', '2');
            $cpbs = $ed->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Capabilities'));
            $cpbs->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Capability', self::CPBLT_BASIC_SEARCH));
            $sdvs = $ed->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:SupportedDataViews'));
            $sdv  = $sdvs->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:SupportedDataView', self::MIME_FCS_HITS));
            $sdv->setAttribute('id', 'hits');
            $sdv->setAttribute('delivery-policy', 'send-by-default');
            $sls  = $ed->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:SupportedLayers'));
            // nothing to do as Layers as only basic search is supported
            $rss  = $ed->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Resources'));
            $rs   = $rss->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Resource'));
            foreach ($this->cfg->resource->title as $lang => $title) {
                $el = $rs->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Title', $title));
                $el->setAttribute('xml:lang', $lang);
            }
            foreach ($this->cfg->resource->title as $lang => $desc) {
                $el = $rs->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Description', $desc));
                $el->setAttribute('xml:lang', $lang);
            }
            foreach ($this->cfg->resource->landingPageUri as $uri) {
                $el = $rs->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:LandingPageURI', $uri));
            }
            $ls = $rs->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Languages'));
            foreach ($this->cfg->resource->language as $lang) {
                $ls->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Language', $lang));
            }
        }

        $resp->addRecord($explain, 'http://explain.z3950.org/dtd/2.1/', $ed);
        return $resp;
    }

    private function handleSearch(SruParameters $param): SruResponse {
        $this->checkParam($param, 'search');
        $resp = new SruResponse('searchRetrieve', $param->version);
        return $resp;
    }

    private function handleScan(SruParameters $param): SruResponse {
        $this->checkParam($param, 'scan');
        $resp = new SruResponse('scan', $param->version);

        throw new SruException('', 4);

        return $resp;
    }

    private function checkParam(SruParameters $param, string $operation): void {
        if ((float) $param->version > 1.2) {
            throw new SruException('1.2', 5);
        }
        if ($operation === 'search' && $param->$param === null) {
            throw new SruException('query', 7);
        }
        if ($param->recordXMLEscaping !== 'XML') {
//            throw new SruException('', 71);
        }
        if ($param->queryType !== 'cql' && $queryType !== 'searchTerms') {
//            throw new SruException('queryType', 6);
        }
        if (!empty($param->sortKeys)) {
            throw new SruException('', 80);
        }
        if ($param->renderedBy !== 'client') {
            throw new SruException('RenderedBy', 6);
        }
        if ($param->httpAccept !== 'application/sru+xml') {
            // it makes no sense to check it as it will e.g. make it unable to test the endpoint in a browser
            // as browsers set Accept text/html
            //throw new FcsException('Not Acceptable', 406);
        }
    }

}
