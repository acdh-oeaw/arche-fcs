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
 * Description of ClarinException
 *
 * @author zozlak
 */
class ClarinException extends SruException {

    const NAMESPACE                  = 'http://clarin.eu/fcs/diagnostic/';

    static private $exceptions = [
        '1'  => 'Persistent identifier passed by the Client for restricting the search is invalid.',
        '2'  => 'Resource set too large. Query context automatically adjusted.',
        '3'  => 'Resource set too large. Cannot perform Query.',
        '4'  => 'Requested Data View not valid for this resource.',
        '10' => 'General query syntax error.',
        '11' => 'Query too complex. Cannot perform Query.',
        '12' => 'Query was rewritten.',
        '14' => 'General processing hint.',
    ];

}
