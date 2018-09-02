<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class ScalarTypeExtensionNode extends BaseNode implements TypeExtensionNode
{
    /** @var NameNode */
    public $name;

    /** @var DirectiveNode[]|null */
    public $directives;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::SCALAR_TYPE_EXTENSION;

        parent::__construct($vars);
    }
}
