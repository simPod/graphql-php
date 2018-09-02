<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class SchemaDefinitionNode extends BaseNode implements TypeSystemDefinitionNode
{
    /** @var DirectiveNode[] */
    public $directives;

    /** @var OperationTypeDefinitionNode[] */
    public $operationTypes;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::SCHEMA_DEFINITION;

        parent::__construct($vars);
    }
}
