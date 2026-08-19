<?php declare(strict_types=1);

namespace GraphQL\Executor;

use Amp\Future;
use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Error\Warning;
use GraphQL\Executor\Promise\Adapter\AmpFutureAdapter;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Definition\AbstractType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\Utils;

use function Amp\async;

/**
 * Executes Amp Futures without creating a GraphQL promise continuation for each field.
 *
 * @phpstan-import-type ArgsMapper from Executor
 * @phpstan-import-type FieldResolver from Executor
 * @phpstan-import-type Fields from ReferenceExecutor
 *
 * @internal
 */
final class AmpFutureExecutor extends ReferenceExecutor
{
    /**
     * @param mixed $rootValue
     * @param mixed $contextValue
     * @param array<string, mixed> $variableValues
     *
     * @phpstan-param FieldResolver $fieldResolver
     * @phpstan-param ArgsMapper $argsMapper
     */
    public static function create(
        PromiseAdapter $promiseAdapter,
        Schema $schema,
        DocumentNode $documentNode,
        $rootValue,
        $contextValue,
        array $variableValues,
        ?string $operationName,
        callable $fieldResolver,
        ?callable $argsMapper = null
    ): ExecutorImplementation {
        if (! $promiseAdapter instanceof AmpFutureAdapter) {
            throw new InvariantViolation('AmpFutureExecutor requires AmpFutureAdapter.');
        }

        return parent::create(
            $promiseAdapter,
            $schema,
            $documentNode,
            $rootValue,
            $contextValue,
            $variableValues,
            $operationName,
            $fieldResolver,
            $argsMapper,
        );
    }

    public function doExecute(): Promise
    {
        $data = $this->executeOperation($this->exeContext->operation, $this->exeContext->rootValue);

        if (! $data instanceof AmpFutureValue) {
            return $this->exeContext->promiseAdapter->createFulfilled($this->createExecutionResult($data));
        }

        return $this->exeContext->promiseAdapter->convertThenable(async(
            fn (): ExecutionResult => $this->createExecutionResult($data->await()),
        ));
    }

    /** @return array<mixed>|\stdClass|AmpFutureValue|null */
    protected function executeOperation(OperationDefinitionNode $operation, $rootValue)
    {
        $type = $this->getOperationRootType($this->exeContext->schema, $operation);
        $fields = $this->collectFields($type, $operation->selectionSet, new \ArrayObject(), new \ArrayObject());
        $path = [];
        $unaliasedPath = [];

        try {
            $result = $operation->operation === 'mutation'
                ? $this->executeFieldsSerially($type, $rootValue, $path, $unaliasedPath, $fields, $this->exeContext->contextValue)
                : $this->executeFields($type, $rootValue, $path, $unaliasedPath, $fields, $this->exeContext->contextValue);

            if (! $result instanceof AmpFutureValue) {
                return $result;
            }

            return AmpFutureValue::defer(function () use ($result) {
                try {
                    return $result->await();
                } catch (Error $error) {
                    $this->exeContext->addError($error);

                    return null;
                }
            });
        } catch (Error $error) {
            $this->exeContext->addError($error);

            return null;
        }
    }

    /** @param mixed $data */
    private function createExecutionResult($data): ExecutionResult
    {
        return new ExecutionResult($data === null ? null : (array) $data, $this->exeContext->errors);
    }

    /**
     * @param mixed $rootValue
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $contextValue
     *
     * @phpstan-param Fields $fields
     *
     * @return array<mixed>|\stdClass|AmpFutureValue
     */
    protected function executeFields(ObjectType $parentType, $rootValue, array $path, array $unaliasedPath, \ArrayObject $fields, $contextValue)
    {
        $containsFuture = false;
        $results = [];
        foreach ($fields as $responseName => $fieldNodes) {
            $result = $this->resolveField(
                $parentType,
                $rootValue,
                $fieldNodes,
                $responseName,
                $path,
                $unaliasedPath,
                $this->maybeScopeContext($contextValue),
            );
            if ($result === static::$UNDEFINED) {
                continue;
            }

            $containsFuture = $containsFuture || $result instanceof AmpFutureValue;
            $results[$responseName] = $result;
        }

        if (! $containsFuture) {
            return static::fixResultsIfEmptyArray($results);
        }

        return AmpFutureValue::defer(function () use ($results) {
            $error = null;
            foreach ($results as $responseName => $result) {
                if ($result instanceof AmpFutureValue) {
                    try {
                        $results[$responseName] = $result->await();
                    } catch (\Throwable $exception) {
                        $error ??= $exception;
                    }
                }
            }

            if ($error !== null) {
                throw $error;
            }

            return static::fixResultsIfEmptyArray($results);
        });
    }

    /**
     * @param mixed $rootValue
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $contextValue
     *
     * @phpstan-param Fields $fields
     *
     * @throws InvariantViolation
     *
     * @return AmpFutureValue
     */
    protected function executeFieldsSerially(ObjectType $parentType, $rootValue, array $path, array $unaliasedPath, \ArrayObject $fields, $contextValue)
    {
        return AmpFutureValue::defer(function () use ($parentType, $rootValue, $path, $unaliasedPath, $fields, $contextValue) {
            $results = [];
            foreach ($fields as $responseName => $fieldNodes) {
                $result = $this->resolveField(
                    $parentType,
                    $rootValue,
                    $fieldNodes,
                    $responseName,
                    $path,
                    $unaliasedPath,
                    $this->maybeScopeContext($contextValue),
                );
                if ($result === static::$UNDEFINED) {
                    continue;
                }

                $results[$responseName] = $result instanceof AmpFutureValue ? $result->await() : $result;
            }

            return static::fixResultsIfEmptyArray($results);
        });
    }

    /**
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $result
     * @param mixed $contextValue
     *
     * @throws Error
     * @throws InvariantViolation
     *
     * @return array<mixed>|\stdClass|AmpFutureValue|Promise|null
     */
    protected function completeValueCatchingError(
        Type $returnType,
        \ArrayObject $fieldNodes,
        ResolveInfo $info,
        array $path,
        array $unaliasedPath,
        $result,
        $contextValue
    ) {
        $futureValue = $this->asFutureValue($result);
        if ($futureValue !== null) {
            $result = $futureValue;
        }

        if ($result instanceof AmpFutureValue) {
            return AmpFutureValue::defer(function () use ($returnType, $fieldNodes, $info, $path, $unaliasedPath, $result, $contextValue) {
                try {
                    return $this->completeValue($returnType, $fieldNodes, $info, $path, $unaliasedPath, $result->await(), $contextValue);
                } catch (\Throwable $error) {
                    return $this->handleNativeFieldError($error, $fieldNodes, $path, $unaliasedPath, $returnType);
                }
            });
        }

        try {
            $completed = $this->completeValue($returnType, $fieldNodes, $info, $path, $unaliasedPath, $result, $contextValue);
            if (! $completed instanceof AmpFutureValue) {
                return $completed;
            }

            return AmpFutureValue::defer(function () use ($completed, $fieldNodes, $path, $unaliasedPath, $returnType) {
                try {
                    return $completed->await();
                } catch (\Throwable $error) {
                    return $this->handleNativeFieldError($error, $fieldNodes, $path, $unaliasedPath, $returnType);
                }
            });
        } catch (\Throwable $error) {
            return $this->handleNativeFieldError($error, $fieldNodes, $path, $unaliasedPath, $returnType);
        }
    }

    /**
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     *
     * @throws Error
     *
     * @return null
     */
    private function handleNativeFieldError(\Throwable $error, \ArrayObject $fieldNodes, array $path, array $unaliasedPath, Type $returnType)
    {
        $this->handleFieldError($error, $fieldNodes, $path, $unaliasedPath, $returnType);

        return null;
    }

    /**
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $result
     * @param mixed $contextValue
     *
     * @return array<mixed>|mixed|AmpFutureValue|null
     */
    protected function completeValue(
        Type $returnType,
        \ArrayObject $fieldNodes,
        ResolveInfo $info,
        array $path,
        array $unaliasedPath,
        $result,
        $contextValue
    ) {
        $futureValue = $this->asFutureValue($result);
        if ($futureValue !== null) {
            $result = $futureValue;
        }

        if ($result instanceof AmpFutureValue) {
            return AmpFutureValue::defer(fn () => $this->completeValue(
                $returnType,
                $fieldNodes,
                $info,
                $path,
                $unaliasedPath,
                $result->await(),
                $contextValue,
            ));
        }

        return parent::completeValue($returnType, $fieldNodes, $info, $path, $unaliasedPath, $result, $contextValue);
    }

    /**
     * @param ListOfType<Type> $returnType
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param iterable<mixed> $results
     * @param mixed $contextValue
     *
     * @throws Error
     * @throws InvariantViolation
     *
     * @return array<mixed>|AmpFutureValue
     */
    protected function completeListValue(
        ListOfType $returnType,
        \ArrayObject $fieldNodes,
        ResolveInfo $info,
        array $path,
        array $unaliasedPath,
        iterable $results,
        $contextValue
    ) {
        $itemType = $returnType->getWrappedType();
        $containsFuture = false;
        $completedItems = [];
        foreach ($results as $item) {
            $index = count($completedItems);
            $itemPath = [...$path, $index];
            $info->path = $itemPath;
            $info->unaliasedPath = [...$unaliasedPath, $index];
            $completedItem = $this->completeValueCatchingError($itemType, $fieldNodes, $info, $itemPath, $info->unaliasedPath, $item, $contextValue);
            $containsFuture = $containsFuture || $completedItem instanceof AmpFutureValue;
            $completedItems[] = $completedItem;
        }

        if (! $containsFuture) {
            return $completedItems;
        }

        return AmpFutureValue::defer(function () use ($completedItems) {
            $error = null;
            foreach ($completedItems as $index => $completedItem) {
                if ($completedItem instanceof AmpFutureValue) {
                    try {
                        $completedItems[$index] = $completedItem->await();
                    } catch (\Throwable $exception) {
                        $error ??= $exception;
                    }
                }
            }

            if ($error !== null) {
                throw $error;
            }

            return $completedItems;
        });
    }

    /**
     * @param AbstractType&Type $returnType
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $result
     * @param mixed $contextValue
     *
     * @return array<mixed>|\stdClass|AmpFutureValue
     */
    protected function completeAbstractValue(
        AbstractType $returnType,
        \ArrayObject $fieldNodes,
        ResolveInfo $info,
        array $path,
        array $unaliasedPath,
        $result,
        $contextValue
    ) {
        $result = $returnType->resolveValue($result, $contextValue, $info);
        $runtimeType = $returnType->resolveType($result, $contextValue, $info);
        if ($runtimeType === null) {
            $runtimeType = $this->defaultTypeResolver($result, $contextValue, $info, $returnType);
        } elseif (! is_string($runtimeType) && is_callable($runtimeType)) {
            $runtimeType = $runtimeType();
        }

        $futureValue = $this->asFutureValue($runtimeType);
        if ($futureValue !== null) {
            $runtimeType = $futureValue;
        }

        if ($runtimeType instanceof AmpFutureValue) {
            return AmpFutureValue::defer(fn () => $this->completeObjectValue(
                $this->ensureValidRuntimeType($runtimeType->await(), $returnType, $info, $result),
                $fieldNodes,
                $info,
                $path,
                $unaliasedPath,
                $result,
                $contextValue,
            ));
        }

        return $this->completeObjectValue(
            $this->ensureValidRuntimeType($runtimeType, $returnType, $info, $result),
            $fieldNodes,
            $info,
            $path,
            $unaliasedPath,
            $result,
            $contextValue,
        );
    }

    /**
     * @param mixed $value
     * @param mixed $contextValue
     * @param AbstractType&Type $abstractType
     *
     * @return ObjectType|string|AmpFutureValue|null
     */
    protected function defaultTypeResolver($value, $contextValue, ResolveInfo $info, AbstractType $abstractType)
    {
        $typename = Utils::extractKey($value, '__typename');
        if (is_string($typename)) {
            return $typename;
        }

        if ($abstractType instanceof InterfaceType && isset($info->schema->getConfig()->typeLoader)) {
            $safeValue = Utils::printSafe($value);
            Warning::warnOnce(
                "GraphQL Interface Type `{$abstractType->name}` returned `null` from its resolveType function for value: {$safeValue}. Switching to slow resolution method using isTypeOf of all possible implementations. It requires full schema scan and degrades query performance significantly. Make sure your resolveType function always returns a valid implementation or throws.",
                Warning::WARNING_FULL_SCHEMA_SCAN,
            );
        }

        $possibleTypes = $info->schema->getPossibleTypes($abstractType);
        $isTypeOfResults = [];
        foreach ($possibleTypes as $index => $type) {
            $isTypeOf = $type->isTypeOf($value, $contextValue, $info);
            if ($isTypeOf === null) {
                continue;
            }

            $futureValue = $this->asFutureValue($isTypeOf);
            if ($futureValue !== null) {
                $isTypeOfResults[$index] = $futureValue;
            } elseif ($isTypeOf === true) {
                return $type;
            }
        }

        if ($isTypeOfResults === []) {
            return null;
        }

        return AmpFutureValue::defer(function () use ($isTypeOfResults, $possibleTypes): ?ObjectType {
            $error = null;
            $matchingType = null;
            foreach ($isTypeOfResults as $index => $isTypeOf) {
                try {
                    if ($isTypeOf->await() === true && $matchingType === null) {
                        $matchingType = $possibleTypes[$index];
                    }
                } catch (\Throwable $exception) {
                    $error ??= $exception;
                }
            }

            if ($error !== null) {
                throw $error;
            }

            return $matchingType;
        });
    }

    /**
     * @param \ArrayObject<int, FieldNode> $fieldNodes
     * @param list<string|int> $path
     * @param list<string|int> $unaliasedPath
     * @param mixed $result
     * @param mixed $contextValue
     *
     * @return array<mixed>|\stdClass|AmpFutureValue
     */
    protected function completeObjectValue(
        ObjectType $returnType,
        \ArrayObject $fieldNodes,
        ResolveInfo $info,
        array $path,
        array $unaliasedPath,
        $result,
        $contextValue
    ) {
        $isTypeOf = $returnType->isTypeOf($result, $contextValue, $info);
        $futureValue = $this->asFutureValue($isTypeOf);
        if ($futureValue !== null) {
            $isTypeOf = $futureValue;
        }

        if ($isTypeOf instanceof AmpFutureValue) {
            return AmpFutureValue::defer(function () use ($isTypeOf, $returnType, $result, $fieldNodes, $path, $unaliasedPath, $contextValue) {
                if ($isTypeOf->await() !== true) {
                    throw $this->invalidReturnTypeError($returnType, $result, $fieldNodes);
                }

                return $this->collectAndExecuteSubfields($returnType, $fieldNodes, $path, $unaliasedPath, $result, $contextValue);
            });
        }

        assert($isTypeOf === null || is_bool($isTypeOf), 'AmpFutureExecutor requires bool or Future from isTypeOf.');
        if ($isTypeOf === false) {
            throw $this->invalidReturnTypeError($returnType, $result, $fieldNodes);
        }

        /** @var array<mixed>|\stdClass|AmpFutureValue $completed */
        $completed = $this->collectAndExecuteSubfields($returnType, $fieldNodes, $path, $unaliasedPath, $result, $contextValue);

        return $completed;
    }

    /**
     * @param mixed $contextValue
     *
     * @return mixed
     */
    private function maybeScopeContext($contextValue)
    {
        return $contextValue instanceof ScopedContext ? $contextValue->clone() : $contextValue;
    }

    /** @param mixed $value */
    private function asFutureValue($value): ?AmpFutureValue
    {
        if ($value instanceof AmpFutureValue) {
            return $value;
        }

        if ($value instanceof Promise) {
            $value = $value->adoptedPromise;
        }

        return $value instanceof Future ? AmpFutureValue::fromFuture($value) : null;
    }
}
