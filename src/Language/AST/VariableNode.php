<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class VariableNode extends BaseNode
{
    /** @var NameNode */
    public $name;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::VARIABLE;

        parent::__construct($vars);
    }
}
