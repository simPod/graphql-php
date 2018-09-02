<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class ListValueNode extends BaseNode implements ValueNode
{
    /** @var ValueNode[]|NodeList */
    public $values;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::LST;

        parent::__construct($vars);
    }
}
