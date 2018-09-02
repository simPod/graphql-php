<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class NullValueNode extends BaseNode implements ValueNode
{
    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::NULL;

        parent::__construct($vars);
    }
}
