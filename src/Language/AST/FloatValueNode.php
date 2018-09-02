<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class FloatValueNode extends BaseNode implements ValueNode
{
    /** @var string */
    public $value;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::FLOAT;

        parent::__construct($vars);
    }
}
