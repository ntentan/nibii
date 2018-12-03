<?php

/*
 * The MIT License
 *
 * Copyright 2014-2018 James Ekow Abaka Ainooson
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

namespace ntentan\nibii;

use ntentan\nibii\exceptions\FilterCompilerException;

/**
 * Safely compiles SQL conditions to ensure that a portable interface is provided
 * through which conditions can be specified accross database platforms. Also
 * the FilterCompiler ensures that raw data is never passed through queries.
 * This is done in order to minimize injection errors.
 */
class FilterCompiler
{
    private $lookahead;
    private $token;
    private $filter;
    private $tokens = [
        'equals'              => '\=',
        'number'              => '[0-9]+',
        'cast'                => 'cast\b',
        'as'                  => 'as\b',
        'between'             => 'between\b',
        'in'                  => 'in\b',
        'like'                => 'like\b',
        'is'                  => 'is\b',
        'and'                 => 'and\b',
        'not'                 => 'not\b',
        'or'                  => 'or\b',
        'greater_or_equal'    => '\>\=',
        'less_or_equal'       => '\<\=',
        'not_equal'           => '\<\>',
        'greater'             => '\>',
        'less'                => '\<',
        'add'                 => '\+',
        'subtract'            => '\-',
        'multiply'            => '\*',
        'function'            => '[a-zA-Z][a-zA-Z0-9\_]*\s*\(',
        'identifier'          => '[a-zA-Z][a-zA-Z0-9\.\_\:]*\b',
        'named_bind_param'    => '\:[a-z_][a-z0-9\_]+',
        'position_bind_param' => '\\?',
        'obracket'            => '\(',
        'cbracket'            => '\)',
        'comma'               => ',',
    ];
    private $operators = [
        ['between', 'or', 'like'],
        ['and'],
        ['not'],
        ['equals', 'greater', 'less', 'greater_or_equal', 'less_or_equal', 'not_equal', 'is'],
        ['add', 'subtract'],
        ['in'],
        ['multiply'],
    ];
    private $numPositions = 0;

    public function compile($filter)
    {
        $this->filter = $filter;
        $this->getToken();
        $expression = $this->parseExpression();
        if ($this->token !== false) {
            throw new FilterCompilerException("Unexpected '".$this->token."' in filter [$filter]");
        }
        $parsed = $this->renderExpression($expression);

        return $parsed;
    }

    private function renderExpression($expression)
    {
        if (is_array($expression)) {
            $expression = $this->renderExpression($expression['left'])." {$expression['opr']} ".$this->renderExpression($expression['right']);
        }

        return $expression;
    }

    private function match($tokens)
    {
        if (is_string($tokens)) {
            $tokens = [$tokens];
        }
        if (array_search($this->lookahead, $tokens) === false) {
            throw new FilterCompilerException('Expected '.implode(' or ', $tokens).' but found '.$this->lookahead);
        }
    }

    private function parseBetween()
    {
        $this->match(['named_bind_param', 'number', 'position_bind_param']);
        $left = $this->token;
        $this->getToken();
        $this->match('and');
        $this->getToken();
        $this->match(['named_bind_param', 'number', 'position_bind_param']);
        $right = $this->token;
        $this->getToken();

        return "$left AND $right";
    }

    private function parseIn()
    {
        $expression = '(';
        $this->match('obracket');
        $this->getToken();

        do {
            $expression .= $this->parseExpression();
            if ($this->lookahead === 'comma') {
                $expression .= ',';
                $this->getToken();
                continue;
            } else {
                break;
            }
        } while (true);

        $this->match('cbracket');

        $this->getToken();

        $expression .= ')';

        return $expression;
    }

    private function parseFunctionParams()
    {
        $parameters = '';
        $size = 0;
        do {
            $size++;
            $parameters .= $this->renderExpression($this->parseExpression());
            if ($this->lookahead == 'comma') {
                $this->getToken();
                $parameters .= ', ';
            } elseif ($this->lookahead == 'cbracket') {
                break;
            }
        } while ($size < 100);

        return $parameters;
    }

    private function parseCast()
    {
        $return = 'cast(';
        $this->getToken();
        $this->match('obracket');
        $this->getToken();
        $return .= $this->renderExpression($this->parseExpression());
        $this->match('as');
        $return .= ' as ';
        $this->getToken();
        $this->match('identifier');
        $return .= $this->token;
        $this->getToken();
        $this->match('cbracket');
        $return .= ')';

        return $return;
    }

    private function parseFunction()
    {
        $name = $this->token;
        $this->getToken();
        $parameters = $this->parseFunctionParams();

        return "$name$parameters)";
    }

    private function returnToken()
    {
        return $this->token;
    }

    private function returnPositionTag()
    {
        return ':filter_bind_'.(++$this->numPositions);
    }

    private function parseObracket()
    {
        $this->getToken();
        $expression = $this->parseExpression();

        return $this->renderExpression($expression);
    }

    private function parseFactor()
    {
        $return = null;
        $methods = [
            'cast'                => 'parseCast',
            'function'            => 'parseFunction',
            'identifier'          => 'returnToken',
            'named_bind_param'    => 'returnToken',
            'number'              => 'returnToken',
            'position_bind_param' => 'returnPositionTag',
            'obracket'            => 'parseObracket',
        ];

        if (isset($methods[$this->lookahead])) {
            $method = $methods[$this->lookahead];
            $return = $this->$method();
        }

        $this->getToken();

        return $return;
    }

    private function parseRightExpression($level, $opr)
    {
        switch ($opr) {
            case 'between':
                return $this->parseBetween();
            case 'in':
                return $this->parseIn();
            default:
                return $this->parseExpression($level);
        }
    }

    private function parseExpression($level = 0)
    {
        if ($level === count($this->operators)) {
            return $this->parseFactor();
        } else {
            $expression = $this->parseExpression($level + 1);
        }

        while ($this->token != false) {
            if (array_search($this->lookahead, $this->operators[$level]) !== false) {
                $left = $expression;
                $opr = $this->token;
                $this->getToken();
                $right = $this->parseRightExpression($level + 1, strtolower($opr));
                $expression = [
                    'left'  => $left,
                    'opr'   => $opr,
                    'right' => $right,
                ];
            } else {
                break;
            }
        }

        return $expression;
    }

    private function getToken()
    {
        $this->eatWhite();
        $this->token = false;
        foreach ($this->tokens as $token => $regex) {
            if (preg_match("/^$regex/i", $this->filter, $matches)) {
                $this->filter = substr($this->filter, strlen($matches[0]));
                $this->lookahead = $token;
                $this->token = $matches[0];
                break;
            }
        }

        if ($this->token === false && strlen($this->filter) > 0) {
            throw new FilterCompilerException('Unexpected character ['.$this->filter[0].'] begining '.$this->filter.'.');
        }
    }

    private function eatWhite()
    {
        if (preg_match("/^\s*/", $this->filter, $matches)) {
            $this->filter = substr($this->filter, strlen($matches[0]));
        }
    }

    public function rewriteBoundData($data)
    {
        $rewritten = [];
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $rewritten['filter_bind_'.($key + 1)] = $value;
            } else {
                $rewritten[$key] = $value;
            }
        }

        return $rewritten;
    }
}
