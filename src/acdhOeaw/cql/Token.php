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
 * Description of Token
 *
 * @author zozlak
 */
class Token {

    const OPERATOR        = 1;
    const SIMPLE_STRING   = 2;
    const QUOTED_STRING   = 3;
    const NMSP_ASSIGN     = '=';
    const MODIFIER        = '/';
    const OPENING_BRACKET = '(';
    const CLOSING_BRACKET = ')';
    const SORTBY          = 'sortby';

    static private $operators = ['=', '>', '<', '>=', '<=', '<>', '=='];
    static private $boolean   = ['and', 'or', 'not', 'prox'];
    static private $andOrNot  = ['and', 'or', 'not'];
    private $value;
    private $type;

    public function __construct(string $value, int $type) {
        $this->value = $value;
        $this->type  = $type;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function getType(): int {
        return $this->type;
    }

    public function isOperator(): bool {
        return $this->type === self::OPERATOR;
    }

    public function isRelation(): bool {
        return $this->type === self::OPERATOR && in_array($this->value, self::$operators);
    }

    public function isModifier(): bool {
        return $this->type === self::OPERATOR && $this->value === self::MODIFIER;
    }

    public function isSortBy(): bool {
        return $this->type === self::SIMPLE_STRING && strtolower($this->value) === self::SORTBY;
    }

    public function isNamespaceAssignment(): bool {
        return $this->type === self::OPERATOR && $this->value === self::NMSP_ASSIGN;
    }

    public function isSimpleString(): bool {
        return $this->type === self::SIMPLE_STRING;
    }

    public function isQuotedString(): bool {
        return $this->type === self::QUOTED_STRING;
    }

    public function isString(): bool {
        return $this->type === self::SIMPLE_STRING || $this->type === self::QUOTED_STRING;
    }

    public function isBoolean(): bool {
        return $this->type === self::SIMPLE_STRING && in_array(strtolower($this->value), self::$boolean);
    }

    public function isAndOrNot(): bool {
        return $this->type === self::SIMPLE_STRING && in_array(strtolower($this->value), self::$andOrNot);
    }

    public function isOpeningBracket(): bool {
        return $this->type === self::OPERATOR && $this->value === self::OPENING_BRACKET;
    }

    public function isClosingBracket(): bool {
        return $this->type === self::OPERATOR && $this->value === self::CLOSING_BRACKET;
    }

}
