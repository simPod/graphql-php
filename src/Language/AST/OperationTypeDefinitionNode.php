<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

class OperationTypeDefinitionNode extends BaseNode
{
    /**
     * One of 'query' | 'mutation' | 'subscription'
     *
     * @var string
     */
    public $operation;

    /** @var NamedTypeNode */
    public $type;

    /**
     * @param (string|NameNode|NodeList|SelectionSetNode|Location|null)[] $vars
     */
    public function __construct(array $vars)
    {
        $this->kind = NodeKind::OPERATION_TYPE_DEFINITION;

        parent::__construct($vars);
    }
}
