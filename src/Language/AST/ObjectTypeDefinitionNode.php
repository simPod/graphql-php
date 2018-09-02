<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class ObjectTypeDefinitionNode extends BaseNode implements TypeDefinitionNode
{
    /** @var NameNode */
    public $name;

    /** @var NamedTypeNode[] */
    public $interfaces = [];

    /** @var DirectiveNode[]|null */
    public $directives;

    /** @var FieldDefinitionNode[]|null */
    public $fields;

    /** @var StringValueNode|null */
    public $description;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::OBJECT_TYPE_DEFINITION;

        parent::__construct($vars);
    }
}
