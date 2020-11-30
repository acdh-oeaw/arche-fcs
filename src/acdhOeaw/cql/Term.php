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

namespace acdhOeaw\cql;

/**
 * Description of Term
 *
 * @author zozlak
 */
class Term {

    /**
     *
     * @var Term
     */
    public $parent;
    public $termLeft;
    public $termRight;
    public $operator;

    public function pushTerm($term) {
        if (is_object($term)) {
            $term->parent = $this;
        }
        if ($this->termLeft === null) {
            $this->termLeft = $term;
        } elseif ($this->termRight === null) {
            $this->termRight = $term;
        } else {
            throw new ParserException("Term already has both operands");
        }
        return $term;
    }

    public function popTerm() {
        if ($this->termRight !== null) {
            $ret             = $this->termRight;
            $this->termRight = null;
        } else if ($this->operator !== null) {
            throw new ParserException("Can't pop operator");
        } else {
            $ret            = $this->termLeft;
            $this->termLeft = null;
        }
        return $ret;
    }

    public function setOperator(string $operator): void {
        $this->operator = strtolower($operator);
    }

    public function __toString(): string {
        if (empty($this->termLeft)) {
            return '_';
        } elseif (empty($this->operator)) {
            return (string) $this->termLeft;
        } else {
            return '(' . (string) $this->termRight . ' ' . $this->operator . ' ' . (string) $this->termLeft . ')';
        }
    }

}
