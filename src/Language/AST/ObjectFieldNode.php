<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class ObjectFieldNode extends BaseNode
{
    /** @var NameNode */
    public $name;

    /** @var ValueNode */
    public $value;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::OBJECT_FIELD;

        parent::__construct($vars);
    }
}
