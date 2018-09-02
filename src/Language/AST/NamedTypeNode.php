<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class NamedTypeNode extends BaseNode implements TypeNode
{
    /** @var NameNode */
    public $name;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::NAMED_TYPE;

        parent::__construct($vars);
    }
}
