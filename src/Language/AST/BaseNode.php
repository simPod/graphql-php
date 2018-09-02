<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

use GraphQL\Utils\Utils;
use function get_object_vars;
use function is_array;
use function is_scalar;
use function json_encode;

abstract class BaseNode implements Node
{
    /** @var string */
    public $kind;

    /** @var Location */
    public $loc;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        if (empty($vars)) {
            return;
        }

        Utils::assign($this, $vars);
    }

    public function getKind() : string
    {
        return $this->kind;
    }

    /**
     * @return string|NodeList|Location|Node
     */
    public function cloneDeep()
    {
        return $this->cloneValue($this);
    }

    /**
     * @param string|NodeList|Location|Node|(Node|NodeList|Location)[] $value
     * @return string|NodeList|Location|Node
     */
    private function cloneValue($value)
    {
        if (is_array($value)) {
            $cloned = [];
            foreach ($value as $key => $arrValue) {
                $cloned[$key] = $this->cloneValue($arrValue);
            }
        } elseif ($value instanceof self) {
            $cloned = clone $value;
            foreach (get_object_vars($cloned) as $prop => $propValue) {
                $cloned->{$prop} = $this->cloneValue($propValue);
            }
        } else {
            $cloned = $value;
        }

        return $cloned;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $tmp = $this->toArray(true);

        return (string) json_encode($tmp);
    }

    /**
     * @param bool $recursive
     * @return mixed[]
     */
    public function toArray($recursive = false) : array
    {
        if ($recursive) {
            return $this->recursiveToArray($this);
        }

        $tmp = (array) $this;

        if ($this->loc) {
            $tmp['loc'] = [
                'start' => $this->loc->start,
                'end'   => $this->loc->end,
            ];
        }

        return $tmp;
    }

    /**
     * @return mixed[]
     */
    private function recursiveToArray(Node $node) : array
    {
        $result = [
            'kind' => $node->getKind(),
        ];

        if ($node->loc) {
            $result['loc'] = [
                'start' => $node->loc->start,
                'end'   => $node->loc->end,
            ];
        }

        foreach (get_object_vars($node) as $prop => $propValue) {
            if (isset($result[$prop])) {
                continue;
            }

            if ($propValue === null) {
                continue;
            }

            if (is_array($propValue) || $propValue instanceof NodeList) {
                $tmp = [];
                foreach ($propValue as $tmp1) {
                    $tmp[] = $tmp1 instanceof Node ? $this->recursiveToArray($tmp1) : (array) $tmp1;
                }
            } elseif ($propValue instanceof Node) {
                $tmp = $this->recursiveToArray($propValue);
            } elseif (is_scalar($propValue) || $propValue === null) {
                $tmp = $propValue;
            } else {
                $tmp = null;
            }

            $result[$prop] = $tmp;
        }

        return $result;
    }
}
