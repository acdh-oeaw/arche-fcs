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

use DOMNode;

/**
 * Description of SruException
 *
 * @author zozlak
 */
class SruException extends FcsException {

    /**
     * List of all SRU diagnostic codes.
     * 
     * See https://www.loc.gov/standards/sru/diagnostics/diagnosticsList.html
     * @var string[] 
     */
    static private $exceptions = [
        1   => 'General system error', // Debugging information (traceback)
        2   => 'System temporarily unavailable',
        3   => 'Authentication error',
        4   => 'Unsupported operation',
        5   => 'Unsupported version', // Highest version supported
        6   => 'Unsupported parameter value', // Name of parameter
        7   => 'Mandatory parameter not supplied', // Name of missing parameter
        8   => 'Unsupported parameter', //  	Name of the unsupported parameter
        9   => 'Unsupported combination of parameters',
        10  => 'Query syntax error',
        12  => 'Too many characters in query', // Maximum supported
        13  => 'Invalid or unsupported use of parentheses', // Character offset to error
        14  => 'Invalid or unsupported use of quotes', // Character offset to error
        15  => 'Unsupported context set', // URI or short name of context set
        16  => 'Unsupported index', // Name of index
        18  => 'Unsupported combination of indexes', // Space delimited index names 
        19  => 'Unsupported relation', // Relation
        20  => 'Unsupported relation modifier', // Modifier
        21  => 'Unsupported combination of relation modifers', // Slash separated relation modifiers
        22  => 'Unsupported combination of relation and index', // Space separated index and relation
        23  => 'Too many characters in term', // Length of longest term
        24  => 'Unsupported combination of relation and term', // Space separated relation and term
        26  => 'Non special character escaped in term', // Character incorrectly escaped
        27  => 'Empty term unsupported',
        28  => 'Masking character not supported',
        29  => 'Masked words too short', // Minimum word length
        30  => 'Too many masking characters in term', // Maximum number supported
        31  => 'Anchoring character not supported',
        32  => 'Anchoring character in unsupported position', // Character offset
        33  => 'Combination of proximity/adjacency and masking characters not supported',
        34  => 'Combination of proximity/adjacency and anchoring characters not supported',
        35  => 'Term contains only stopwords', // Value
        36  => 'Term in invalid format for index or relation',
        37  => 'Unsupported boolean operator', // Value
        38  => 'Too many boolean operators in query', // Maximum number supported
        39  => 'Proximity not supported',
        40  => 'Unsupported proximity relation', // Value
        41  => 'Unsupported proximity distance', // Value
        42  => 'Unsupported proximity unit', // Value
        43  => 'Unsupported proximity ordering', // Value
        44  => 'Unsupported combination of proximity modifiers', // Slash separated values
        46  => 'Unsupported boolean modifier', // Value
        47  => 'Cannot process query; reason unknown',
        48  => 'Query feature unsupported', // Feature
        49  => 'Masking character in unsupported position', // the rejected term
        50  => 'Result sets not supported',
        51  => 'Result set does not exist', // Result set identifier
        52  => 'Result set temporarily unavailable', // Result set identifier
        53  => 'Result sets only supported for retrieval',
        55  => 'Combination of result sets with search terms not supported',
        58  => 'Result set created with unpredictable partial results available',
        59  => 'Result set created with valid partial results available',
        60  => 'Result set not created: too many matching records', // Maximum number
        61  => 'First record position out of range',
        64  => 'Record temporarily unavailable',
        65  => 'Record does not exist',
        66  => 'Unknown schema for retrieval', // Schema URI or short name
        67  => 'Record not available in this schema', // Schema URI or short name
        68  => 'Not authorized to send record',
        69  => 'Not authorized to send record in this schema',
        70  => 'Record too large to send', // Maximum record size
        71  => 'Unsupported recordXMLEscaping/recordPacking value',
        72  => 'XPath retrieval unsupported',
        73  => 'XPath expression contains unsupported feature', // Feature
        74  => 'Unable to evaluate XPath expression',
        80  => 'Sort not supported',
        82  => 'Unsupported sort sequence', // Sequence
        83  => 'Too many records to sort', // Maximum number supported
        84  => 'Too many sort keys to sort', // Maximum number supported
        86  => 'Cannot sort: incompatible record formats',
        87  => 'Unsupported schema for sort', // URI or short name of schema given
        88  => 'Unsupported path for sort', // XPath
        89  => 'Path unsupported for schema', // XPath
        90  => 'Unsupported direction', // Value
        91  => 'Unsupported case', // Value
        92  => 'Unsupported missing value action', // Value
        93  => 'Sort ended due to missing value',
        94  => 'Sort spec included both in query and protocol: query prevails',
        95  => 'Sort spec included both in query and protocol: protocol prevails',
        96  => 'Sort spec included both in query and protocol: error',
        103 => 'Stylesheets not supported',
        104 => 'Unsupported stylesheet',
        110 => 'Stylesheets not supported',
        111 => 'Unsupported stylesheet', // URL of stylesheet
        120 => 'Response position out of range',
        121 => 'Too many terms requested', // Maximum number of terms
        235 => 'Database does not exist',
    ];

    public function appendToXmlNode(DOMNode $node, string $namespace): void {
        $d       = $node->ownerDocument->createElementNS($namespace, 'diag:diagnostic');
        $uri     = $node->ownerDocument->createElementNS($namespace, 'diag:uri', 'info:srw/diagnostic/1/' . $this->getCode());
        $d->appendChild($uri);
        $details = $this->getMessage();
        if (!empty($details)) {
            $details = $node->ownerDocument->createElementNS($namespace, 'diag:details', $details);
            $d->appendChild($details);
        }
        $message = $node->ownerDocument->createElementNS($namespace, 'diag:message', self::$exceptions[$this->getCode()]);
        $d->appendChild($message);
        $node->appendChild($d);
    }

}
