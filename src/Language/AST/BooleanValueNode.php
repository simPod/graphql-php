<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class BooleanValueNode extends BaseNode implements ValueNode
{
    /** @var string */
    public $value;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::BOOLEAN;

        parent::__construct($vars);
    }
}
