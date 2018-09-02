<?php

declare(strict_types=1);

namespace GraphQL\Language\AST;

/**
 * type Node = NameNode
 * | DocumentNode
 * | OperationDefinitionNode
 * | VariableDefinitionNode
 * | VariableNode
 * | SelectionSetNode
 * | FieldNode
 * | ArgumentNode
 * | FragmentSpreadNode
 * | InlineFragmentNode
 * | FragmentDefinitionNode
 * | IntValueNode
 * | FloatValueNode
 * | StringValueNode
 * | BooleanValueNode
 * | EnumValueNode
 * | ListValueNode
 * | ObjectValueNode
 * | ObjectFieldNode
 * | DirectiveNode
 * | ListTypeNode
 * | NonNullTypeNode
 */
interface Node
{
    public function getKind() : string;

    /**
     * @return string|NodeList|Location|Node
     */
    public function cloneDeep();

    /**
     * @param bool $recursive
     * @return mixed[]
     */
    public function toArray($recursive = false) : array;
}
