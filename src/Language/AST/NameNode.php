<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class NameNode extends BaseNode implements TypeNode
{
    /** @var string */
    public $value;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::NAME;

        parent::__construct($vars);
    }

    public function __toString() : string
    {
        return $this->value;
    }
}
