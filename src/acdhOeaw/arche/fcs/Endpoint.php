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

use DOMDocument;
use DOMNode;
use PDO;
use acdhOeaw\cql\Parser;
use acdhOeaw\cql\ParserException;

/**
 * Description of Endpoint
 *
 * @author zozlak
 */
class Endpoint {

    const NMSP_FCS_ENDPOINT_DESC   = 'http://clarin.eu/fcs/endpoint-description';
    const NMSP_FCS_RESOURCE        = 'http://clarin.eu/fcs/resource';
    const NMSP_FCS_HITS            = 'http://clarin.eu/fcs/dataview/hits';
    const CPBLT_BASIC_SEARCH       = 'http://clarin.eu/fcs/capability/basic-search';
    const MIME_FCS_HITS            = 'application/x-clarin-fcs-hits+xml';
    const MIME_CMDI                = 'application/x-cmdi+xml';
    const ID_DATA_VIEW_HITS        = 'hits';
    const ID_DATA_VIEW_CMDI        = 'cmdi';
    const POLICY_DATA_VIEW_DEFAULT = 'send-by-default';
    const POLICY_DATA_VIEW_REQUEST = 'need-to-request';
    const FTS_PROPERTY_BINARY      = 'BINARY';
    const FRAGMENT_DELIMITER       = '@~$`';

    /**
     *
     * @var object
     */
    private $cfg;

    /**
     *
     * @var DOMNode[]
     */
    private $cmdiCache = [];

    public function __construct(object $cfg) {
        $this->cfg = $cfg;
    }

    public function handleRequest(): void {
        $resp = new SruResponse('explain', $this->cfg->defaultVersion ?? SruResponse::SRU_MAX_VERSION);
        try {
            switch ($_SERVER['REQUEST_METHOD'] ?? '') {
                case 'GET':
                    $src = $_GET;
                    break;
                case 'POST':
                    $src = $_POST;
                    break;
                case 'HEAD':
                    throw new FcsException('HEAD', -1);
                default:
                    http_response_code(405);
                    echo 'Method not allowed';
                    return;
            }
            $param = new SruParameters($src, $this->cfg->defaultVersion ?? SruResponse::SRU_MAX_VERSION);
            $resp  = new SruResponse($param->operation, $this->cfg->defaultVersion ?? SruResponse::SRU_MAX_VERSION);

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
            if ($e->getCode() === -1) {
                header('Content-Type: application/xml');
            } else if ($e->getCode() < 400 || $e->getCode() >= 500) {
                throw $e;
            }
            // accept rare cases of SRU reporting errors with HTTP codes
        }
    }

    private function handleExplain(SruParameters $param): SruResponse {
        $resp    = new SruResponse('explain', $param->version);
        $this->checkParam($param, 'explain');
        $explain = $resp->createElementNs(SruResponse::ZEEREX_NMSP, 'zr:explain');

        $si = $explain->appendChild($resp->createElementNs(SruResponse::ZEEREX_NMSP, 'zr:serverInfo'));
        $si->setAttribute('protocol', 'SRU');
        $si->setAttribute('version', '2.0');
        $si->setAttribute('transport', $this->cfg->serverInfo->transport);
        $si->appendChild($resp->createElementNs(SruResponse::ZEEREX_NMSP, 'zr:host', $this->cfg->serverInfo->host));
        $si->appendChild($resp->createElementNs(SruResponse::ZEEREX_NMSP, 'zr:port', $this->cfg->serverInfo->port));
        $si->appendChild($resp->createElementNs(SruResponse::ZEEREX_NMSP, 'zr:database', $this->cfg->serverInfo->database));

        $db = $explain->appendChild($resp->createElementNs(SruResponse::ZEEREX_NMSP, 'zr:databaseInfo'));
        foreach ($this->cfg->databaseInfo as $element => $values) {
            foreach ($values as $lang => $value) {
                $el = $db->appendChild($resp->createElementNs(SruResponse::ZEEREX_NMSP, "zr:$element", $value));
                $el->setAttribute('lang', $lang);
            }
        }

        $si = $explain->appendChild($resp->createElementNs(SruResponse::ZEEREX_NMSP, 'zr:schemaInfo'));
        $s  = $si->appendChild($resp->createElementNs(SruResponse::ZEEREX_NMSP, 'zr:schema'));
        $s->setAttribute('identifier', 'http://clarin.eu/fcs/resource');
        $s->setAttribute('name', 'fcs');

        $ci = $explain->appendChild($resp->createElementNs(SruResponse::ZEEREX_NMSP, 'zr:configInfo'));
        foreach ($this->cfg->configInfo as $element => $values) {
            foreach ($values as $property => $value) {
                $el = $ci->appendChild($resp->createElementNs(SruResponse::ZEEREX_NMSP, "zr:$element", $value));
                $el->setAttribute('type', $property);
            }
        }

        $resp->addRecord($explain, SruResponse::RECORD_SCHEMA);

        if ($param->xFcsEndpointDescription === 'true') {
            $ed   = $resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:EndpointDescription');
            $ed->setAttribute('version', $param->version >= 2 ? '2' : '1');
            $cpbs = $ed->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Capabilities'));
            $cpbs->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Capability', self::CPBLT_BASIC_SEARCH));
            $sdvs = $ed->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:SupportedDataViews'));
            $sdv  = $sdvs->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:SupportedDataView', self::MIME_FCS_HITS));
            $sdv->setAttribute('id', self::ID_DATA_VIEW_HITS);
            $sdv->setAttribute('delivery-policy', self::POLICY_DATA_VIEW_DEFAULT);
            $sdv  = $sdvs->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:SupportedDataView', self::MIME_CMDI));
            $sdv->setAttribute('id', self::ID_DATA_VIEW_CMDI);
            $sdv->setAttribute('delivery-policy', self::POLICY_DATA_VIEW_REQUEST);
            // nothing to do as Layers as only basic search is supported
            //$sls  = $ed->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:SupportedLayers'));
            $rss  = $ed->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Resources'));
            $this->explainDescribeResources($rss, $resp);
            $resp->addExtraResponseData($ed);
        }

        return $resp;
    }

    private function handleSearch(SruParameters $param): SruResponse {
        $param->maximumRecords ??= $this->cfg->configInfo->default->maximumRecords;
        $this->checkParam($param, 'search');
        $param->maximumRecords = (int) $param->maximumRecords;
        $param->startRecord    = (int) $param->startRecord;
        $resp                  = new SruResponse('searchRetrieve', $param->version);

        $pdo   = $this->getDbHandle();
        $query = $this->cfg->resourceQuery->query;
        $query = "
            CREATE TEMPORARY TABLE validres AS (
                SELECT id, pid, cmdipid FROM ($query) t
            )
        ";
        $query = $pdo->prepare($query);
        $query->execute($this->cfg->resourceQuery->parameters);
        $this->processFcsContext($param, $pdo);

        try {
            $cqlParser = new Parser($param->query);
        } catch (ParserException $e) {
            throw new SruException('', 10);
        }
        $tsquery    = $cqlParser->asTsquery();
        $hglghOpts  = sprintf(
            'MaxWords=%d,MinWords=%d,ShortWord=%d,MaxFragments=%d,FragmentDelimiter=%s',
            $this->cfg->highlighting->maxWords,
            $this->cfg->highlighting->minWords,
            $this->cfg->highlighting->shortWord,
            $this->cfg->highlighting->maxFragments,
            self::FRAGMENT_DELIMITER
        );
        $query      = "
            SELECT 
                id, pid, cmdipid, 
                ts_headline('simple', raw, to_tsquery('simple', ?), ?) AS hits
            FROM
                validres
                JOIN full_text_search fts USING (id)
            WHERE
                property = ?
                AND to_tsquery('simple', ?) @@ segments
            ORDER BY id
            LIMIT ?
            OFFSET ?
        ";
        $queryParam = [
            $tsquery, $hglghOpts, 
            self::FTS_PROPERTY_BINARY, $tsquery,
            $param->maximumRecords + 1, $param->startRecord - 1
        ];
        $query      = $pdo->prepare($query);
        $query->execute($queryParam);
        $n = $param->maximumRecords;
        while ($n > 0 && ($res        = $query->fetchObject())) {
            $n--;
            $xmlRes = $resp->createElementNs(self::NMSP_FCS_RESOURCE, 'fcs:Resource');
            $xmlRes->setAttribute('pid', $res->pid);

            $hits = explode(self::FRAGMENT_DELIMITER, $res->hits);
            foreach ($hits as $hit) {
                $xmlResFrag     = $xmlRes->appendChild($resp->createElementNs(self::NMSP_FCS_RESOURCE, 'fcs:ResourceFragment'));
                $xmlHitDataView = $xmlResFrag->appendChild($resp->createElementNs(self::NMSP_FCS_RESOURCE, 'fcs:DataView'));
                $xmlHitDataView->setAttribute('type', self::MIME_FCS_HITS);
                $xmlHit         = $xmlHitDataView->appendChild($resp->createElementNs(self::NMSP_FCS_HITS, 'hits:Result'));
                $offset         = 0;
                while ($p1             = strpos($hit, '<b>', $offset)) {
                    $xmlHit->appendChild($xmlHit->ownerDocument->createTextNode(substr($hit, $offset, $p1)));
                    $p2     = strpos($hit, '</b>', $offset + 3);
                    $xmlHit->appendChild($resp->createElementNs(self::NMSP_FCS_HITS, 'hits:hit', substr($hit, $p1 + 3, $p2 - $p1 - 3)));
                    $offset = $p2 + 4;
                }
                $xmlHit->appendChild($xmlHit->ownerDocument->createTextNode(substr($hit, $offset)));

                if ($n >= $param->maximumRecords) {
                    break;
                }
            }

            foreach ($param->xFcsDataviews as $dv) {
                $this->processHitDataView($dv, $res, $xmlRes);
            }

            $resp->addRecord($xmlRes, self::NMSP_FCS_RESOURCE);
        }
        if ($query->fetchObject() !== false) {
            $resp->addNextRecordPosition($param->startRecord + $param->maximumRecords);
        }

        return $resp;
    }

    private function handleScan(SruParameters $param): SruResponse {
        $this->checkParam($param, 'scan');
        $resp = new SruResponse('scan', $param->version);

        throw new SruException('', 4);
    }

    private function explainDescribeResources(DOMNode $container,
                                              SruResponse $resp): void {
        $pdo   = $this->getDbHandle();
        $query = $pdo->prepare($this->cfg->resourceQuery->query);
        $query->execute($this->cfg->resourceQuery->parameters);
        while ($res   = $query->fetchObject()) {
            $xmlRes = $container->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Resource'));
            $xmlRes->setAttribute('pid', $res->pid);
            foreach (json_decode($res->title) as $title) {
                $xmlEl = $xmlRes->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Title', $title->value));
                $xmlEl->setAttribute('xml:lang', $title->lang);
            }
            $xmlLangs = $xmlRes->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Languages'));
            foreach (json_decode($res->language) as $lang) {
                $xmlLangs->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:Language', $lang));
            }
            $xmlDataViews = $xmlRes->appendChild($resp->createElementNs(self::NMSP_FCS_ENDPOINT_DESC, 'ed:AvailableDataViews'));
            $xmlDataViews->setAttribute('ref', self::ID_DATA_VIEW_HITS . ' ' . self::ID_DATA_VIEW_CMDI);
        }
    }

    private function checkParam(SruParameters $param, string $operation): void {
        if (!in_array((float) $param->version, [1.1, 1.2, 2.0])) {
            throw new SruException(SruResponse::SRU_MAX_VERSION, 5);
        }
        if ($param->renderedBy !== 'client') {
            throw new SruException('renderedBy', 6);
        }
        if ($param->recordXMLEscaping !== 'xml') {
            throw new SruException('', 71);
        }
        if ($param->httpAccept !== 'application/sru+xml') {
            // it makes no sense to check it as it will e.g. 
            // make it unable to test the endpoint in a browser as browsers set Accept text/html
            //throw new FcsException('Not Acceptable', 406);
        }
        if ($operation === 'search') {
            if (empty($param->query)) {
                throw new SruException('query', 7);
            }
            if ($param->queryType !== 'cql' && $this->queryType !== 'searchTerms') {
                throw new SruException('queryType', 6);
            }
            if ($param->startRecord !== '' && !preg_match('/^[1-9][0-9]*$/', $param->startRecord)) {
                throw new SruException('startRecord', 6);
            }
            if ((string) $param->maximumRecords !== '' && !preg_match('/^[1-9][0-9]*$/', $param->maximumRecords)) {
                throw new SruException('maximumRecords', 6);
            }
            if ((string) $param->resultSetTTL !== '') {
                throw new SruException('resultSetTTL', 8);
            }

            if (!empty($param->recordSchema) && $param->recordSchema !== self::NMSP_FCS_RESOURCE) {
                throw new SruException($param->recordSchema, 66);
            }
            if (!empty($param->recordPacking)) {
                if ($param->version >= 2 && $param->recordPacking !== 'packed') {
                    throw new SruException('recordPacking', 6);
                } else if ($param->version < 2 && $param->recordPacking !== 'xml') {
                    throw new SruException('', 71);
                }
            }
            foreach ($param->xFcsDataviews as $i) {
                if ($i !== self::ID_DATA_VIEW_CMDI && !(empty(trim($i) || count($param->xFcsDataviews) > 1))) {
                    throw new SruException('', 4);
                }
            }
        } elseif ($operation === 'scan') {
            if (empty($param->scanClause)) {
                throw new SruException('scanClause', 7);
            }
            if ((string) $param->responsePosition !== '' && !preg_match('/^[1-9][0-9]*$/', $param->responsePosition)) {
                throw new SruException('responsePosition', 6);
            }
            if ((string) $param->maximumTerms !== '' && !preg_match('/^[1-9][0-9]*$/', $param->maximumTerms)) {
                throw new SruException('maximumTerms', 6);
            }
        }
    }

    private function processFcsContext(SruParameters $param, PDO $pdo) {
        if (count($param->xFcsContext) > 1 || !empty($param->xFcsContext[0])) {
            $query = sprintf("DELETE FROM validres WHERE pid NOT IN (%s)", substr(str_repeat('?, ', count($param->xFcsContext)), 0, -2));
            $query = $pdo->prepare($query);
            $query->execute($param->xFcsContext);
            if ($pdo->query("SELECT count(*) FROM validres")->fetchColumn() !== count($param->xFcsContext)) {
                throw new SruException('Nonexistent resources requested with x-fcs-context', 1);
            }
        }
    }

    private function processHitDataView(string $dataView, object $res,
                                        DOMNode $xmlParent): void {
        switch ($dataView) {
            case 'cmdi':
                $dataView = $xmlParent->appendChild($resp->createElementNs(self::NMSP_FCS_RESOURCE, 'fcs:DataView'));
                $dataView->setAttribute('type', self::MIME_CMDI);
                $dataView->setAttribute('pid', $res->cmdipid);
                $cmdiUrl  = sprintf($this->cfg->cmdiUrl, $res->pid);
                if (!isset($this->cmdiCache[$cmdiUrl])) {
                    $cmdi                      = new DOMDocument();
                    $cmdi->loadXML(file_get_contents());
                    $cmdi                      = $dataView->ownerDocument->importNode($cmdi->documentElement, true);
                    $this->cmdiCache[$cmdiUrl] = $cmdi;
                } else {
                    $cmdi = $this->cmdiCache[$cmdiUrl]->cloneNode(true);
                }
                $dataView->appendChild($cmdi);
                break;
        }
    }

    private function getDbHandle(): PDO {
        $pdo = new PDO('pgsql: ' . $this->cfg->dbConnStr);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction();
        return $pdo;
    }

}
