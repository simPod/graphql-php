<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class InputObjectTypeExtensionNode extends BaseNode implements TypeExtensionNode
{
    /** @var NameNode */
    public $name;

    /** @var DirectiveNode[]|null */
    public $directives;

    /** @var InputValueDefinitionNode[]|null */
    public $fields;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::INPUT_OBJECT_TYPE_EXTENSION;

        parent::__construct($vars);
    }
}
