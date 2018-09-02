<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class NonNullTypeNode extends BaseNode implements TypeNode
{
    /** @var NameNode | ListTypeNode */
    public $type;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::NON_NULL_TYPE;

        parent::__construct($vars);
    }
}
