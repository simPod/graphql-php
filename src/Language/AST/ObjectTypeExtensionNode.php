<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class ObjectTypeExtensionNode extends BaseNode implements TypeExtensionNode
{
    /** @var NameNode */
    public $name;

    /** @var NamedTypeNode[] */
    public $interfaces = [];

    /** @var DirectiveNode[] */
    public $directives;

    /** @var FieldDefinitionNode[] */
    public $fields;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::OBJECT_TYPE_EXTENSION;

        parent::__construct($vars);
    }
}
