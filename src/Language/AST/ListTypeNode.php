<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class ListTypeNode extends BaseNode implements TypeNode
{
    /** @var Node */
    public $type;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::LIST_TYPE;

        parent::__construct($vars);
    }
}
