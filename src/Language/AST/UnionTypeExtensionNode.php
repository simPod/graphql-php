<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class UnionTypeExtensionNode extends BaseNode implements TypeExtensionNode
{
    /** @var NameNode */
    public $name;

    /** @var DirectiveNode[]|null */
    public $directives;

    /** @var NamedTypeNode[]|null */
    public $types;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::UNION_TYPE_EXTENSION;

        parent::__construct($vars);
    }
}
