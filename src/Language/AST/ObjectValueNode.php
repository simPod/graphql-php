<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class ObjectValueNode extends BaseNode implements ValueNode
{
    /** @var ObjectFieldNode[]|NodeList */
    public $fields;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::OBJECT;

        parent::__construct($vars);
    }
}
