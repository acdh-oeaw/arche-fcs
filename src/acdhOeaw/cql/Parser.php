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

namespace acdhOeaw\cql;

/**
 * OASIS-CQL Parser
 * 
 * Provides Level 1 variant 2.b) conformance.
 * 
 * http://docs.oasis-open.org/search-ws/searchRetrieve/v1.0/os/part5-cql/searchRetrieve-v1.0-os-part5-cql.html
 *
 * @author zozlak
 */
class Parser {

    const STATE_NOTERM = 1;
    const STATE_TERM   = 2;
    const TOKEN_REGEX  = '([()/<=>]|<=|>=|==)|([^"()/<=>\s]+)|"([\\\\]"|[^"])*"';

    /**
     *
     * @var Term
     */
    private $queryTree;

    public function __construct(string $query) {
        // start by splitting input into tokens being operators or strings
        $tokens  = [];
        $regex   = "`^" . self::TOKEN_REGEX . "`";
        $matches = null;
        while (preg_match($regex, $query, $matches)) {
            $type   = Token::OPERATOR;
            $offset = 0;
            if (isset($matches[3])) {
                $type       = Token::QUOTED_STRING;
                $matches[0] = substr($matches[0], 1, -1);
                $offset     = 2;
            } elseif (isset($matches[2])) {
                $type = Token::SIMPLE_STRING;
            }
            $tokens[] = new Token($matches[0], $type);
            $query    = ltrim(substr($query, strlen($matches[0]) + $offset));
        }

        if (count($tokens) === 0) {
            throw new ParserException('Empty query');
        }
        try {
            $this->queryTree = $this->parseSimple($tokens);
        } catch (ParserException $e) {
            $this->queryTree = $this->parseLogical($tokens);
        }
    }

    /**
     * 
     * @param Token[] $tokens
     * @return \acdhOeaw\cql\Term
     */
    private function parseLogical(array &$tokens): Term {
        $stack   = [];
        $term    = new Term();
        $stack[] = $term;
        while (count($tokens) > 0) {
            $token = array_pop($tokens);
            // brackets are "negated" because we are reading right to left
            if ($token->isClosingBracket()) {
                $stack[] = $term;
                $term    = $term->pushTerm(new Term());
            } elseif ($token->isOpeningBracket()) {
                if (count($stack) === 1) {
                    throw new ParserException("Opening and closing brackets count don't match");
                }
                $parentTerm  = array_pop($stack);
                $bracketTerm = $parentTerm->popTerm();
                $term        = $parentTerm->pushTerm(new Term());
                $term->pushTerm($bracketTerm);
            } elseif ($token->isAndOrNot()) {
                if ($term->termLeft === null) {
                    throw new ParserException("Boolean operator with no left term");
                }
                $term->setOperator($token->getValue());
                $term = $term->pushTerm(new Term());
            } elseif ($token->isString()) {
                $term->pushTerm($token->getValue());
            } else {
                throw new ParserException("Query not supported");
            }
        }
        if (count($stack) !== 1) {
            throw new ParserException("Opening and closing brackets count don't match");
        }
        return $stack[0];
    }

    private function parseSimple(array &$tokens): Term {
        foreach ($tokens as $i) {
            if (!$i->isString() || $i->isAndOrNot()) {
                throw new ParserException("Unsupported query");
            }
        }
        $term = $firstTerm = new Term();
        $term->pushTerm(array_shift($tokens)->getValue());
        foreach ($tokens as $i) {
            $term->setOperator('and');
            $newTerm = $term->pushTerm(new Term());
            $newTerm->pushTerm($i->getValue());
            $term = $newTerm;
        }
        return $firstTerm;
    }

    public function asTsquery(): string {
        return $this->queryTree->asTsquery();
    }

    public function __toString(): string {
        return (string) $this->queryTree;
    }

}
