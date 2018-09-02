<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class OperationDefinitionNode extends BaseNode implements ExecutableDefinitionNode, HasSelectionSet
{
    /** @var NameNode|null */
    public $name;

    /** @var string (oneOf 'query', 'mutation')) */
    public $operation;

    /** @var VariableDefinitionNode[] */
    public $variableDefinitions;

    /** @var DirectiveNode[] */
    public $directives;

    /** @var SelectionSetNode */
    public $selectionSet;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::OPERATION_DEFINITION;

        parent::__construct($vars);
    }
}
