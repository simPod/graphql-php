<?php declare(strict_types=1);

namespace GraphQL\Tests\Executor;

use Amp\DeferredFuture;
use Amp\Future;
use GraphQL\Executor\AmpFutureExecutor;
use GraphQL\Executor\Promise\Adapter\AmpFutureAdapter;
use GraphQL\Executor\Promise\Promise;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

/**
 * @group AmpFuture
 */
final class AmpFutureExecutorTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(Future::class)) {
            self::markTestSkipped('amphp/amp ^3 is required for this test suite.');
        }
    }

    public function testCreatesAllSiblingFuturesBeforeAwaitingAndAvoidsGenericContinuationChains(): void
    {
        $adapter = new CountingAmpFutureAdapter();
        $deferred = [
            'a' => new DeferredFuture(),
            'b' => new DeferredFuture(),
            'c' => new DeferredFuture(),
        ];
        $started = [];
        $schema = $this->createSchema(static function (string $id) use (&$started, $deferred): Future {
            $started[] = $id;

            return $deferred[$id]->getFuture();
        });

        $promise = GraphQL::promiseToExecute(
            $adapter,
            $schema,
            '{ items { id detail } }',
            null,
            null,
            null,
            null,
            null,
            null,
            [AmpFutureExecutor::class, 'create'],
        );

        self::assertSame(['a', 'b', 'c'], $started);
        self::assertSame(0, $adapter->thenCalls);
        self::assertSame(0, $adapter->allCalls);

        $deferred['c']->complete('value-c');
        $deferred['b']->complete('value-b');
        $deferred['a']->complete('value-a');

        $result = $promise->adoptedPromise->await();

        self::assertSame([], $result->errors);
        self::assertSame([
            'items' => [
                ['id' => 'a', 'detail' => 'value-a'],
                ['id' => 'b', 'detail' => 'value-b'],
                ['id' => 'c', 'detail' => 'value-c'],
            ],
        ], $result->data);
    }

    public function testRejectedNullableFutureBecomesALocatedFieldError(): void
    {
        $adapter = new CountingAmpFutureAdapter();
        $schema = $this->createSchema(static fn (string $id): Future => $id === 'b'
                ? Future::error(new \RuntimeException('boom'))
                : Future::complete('value-' . $id));

        $promise = GraphQL::promiseToExecute(
            $adapter,
            $schema,
            '{ items { id detail } }',
            null,
            null,
            null,
            null,
            null,
            null,
            [AmpFutureExecutor::class, 'create'],
        );

        $result = $promise->adoptedPromise->await();

        self::assertSame(0, $adapter->thenCalls);
        self::assertSame(0, $adapter->allCalls);
        self::assertCount(1, $result->errors);
        self::assertSame('boom', $result->errors[0]->getMessage());
        self::assertSame(['items', 1, 'detail'], $result->errors[0]->getPath());
        self::assertSame([
            'items' => [
                ['id' => 'a', 'detail' => 'value-a'],
                ['id' => 'b', 'detail' => null],
                ['id' => 'c', 'detail' => 'value-c'],
            ],
        ], $result->data);
    }

    public function testWrappedGraphQLPromiseIsResolvedAsAnAmpFuture(): void
    {
        $adapter = new CountingAmpFutureAdapter();
        $schema = $this->createSchema(
            static fn (string $id) => $adapter->convertThenable(Future::complete('value-' . $id)),
        );

        $promise = GraphQL::promiseToExecute(
            $adapter,
            $schema,
            '{ items { id detail } }',
            null,
            null,
            null,
            null,
            null,
            null,
            [AmpFutureExecutor::class, 'create'],
        );

        $result = $promise->adoptedPromise->await();

        self::assertSame([], $result->errors);
        self::assertSame([
            'items' => [
                ['id' => 'a', 'detail' => 'value-a'],
                ['id' => 'b', 'detail' => 'value-b'],
                ['id' => 'c', 'detail' => 'value-c'],
            ],
        ], $result->data);
    }

    public function testRejectedNonNullFutureBubblesToTheNullableParent(): void
    {
        $adapter = new CountingAmpFutureAdapter();
        $item = new ObjectType([
            'name' => 'Item',
            'fields' => [
                'detail' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => static fn (): Future => Future::error(new \RuntimeException('boom')),
                ],
            ],
        ]);
        $schema = new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'item' => [
                        'type' => $item,
                        'resolve' => static fn (): array => [],
                    ],
                ],
            ]),
        ]);

        $promise = GraphQL::promiseToExecute(
            $adapter,
            $schema,
            '{ item { detail } }',
            null,
            null,
            null,
            null,
            null,
            null,
            [AmpFutureExecutor::class, 'create'],
        );

        $result = $promise->adoptedPromise->await();

        self::assertCount(1, $result->errors);
        self::assertSame(['item', 'detail'], $result->errors[0]->getPath());
        self::assertSame(['item' => null], $result->data);
    }

    public function testMutationsDoNotStartLaterResolversBeforeEarlierFuturesComplete(): void
    {
        $adapter = new CountingAmpFutureAdapter();
        $first = new DeferredFuture();
        $events = [];
        $schema = new Schema([
            'query' => new ObjectType(['name' => 'Query', 'fields' => ['unused' => Type::string()]]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => [
                    'first' => [
                        'type' => Type::string(),
                        'resolve' => static function () use (&$events, $first): Future {
                            $events[] = 'first';

                            return $first->getFuture();
                        },
                    ],
                    'second' => [
                        'type' => Type::string(),
                        'resolve' => static function () use (&$events): string {
                            $events[] = 'second';

                            return 'second';
                        },
                    ],
                ],
            ]),
        ]);
        $promise = GraphQL::promiseToExecute(
            $adapter,
            $schema,
            'mutation { first second }',
            null,
            null,
            null,
            null,
            null,
            null,
            [AmpFutureExecutor::class, 'create'],
        );

        EventLoop::queue(static function () use (&$events, $first): void {
            self::assertSame(['first'], $events);
            $first->complete('first');
        });

        $result = $promise->adoptedPromise->await();

        self::assertSame(['first', 'second'], $events);
        self::assertSame(['first' => 'first', 'second' => 'second'], $result->data);
    }

    /**
     * @param callable(string): mixed $detailResolver
     *
     * @throws \GraphQL\Error\InvariantViolation
     */
    private function createSchema(callable $detailResolver): Schema
    {
        $item = new ObjectType([
            'name' => 'Item',
            'fields' => [
                'detail' => [
                    'type' => Type::string(),
                    'resolve' => static fn (array $item) => $detailResolver($item['id']),
                ],
                'id' => Type::nonNull(Type::string()),
            ],
        ]);

        return new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'items' => [
                        'type' => Type::listOf($item),
                        'resolve' => static fn (): array => [
                            ['id' => 'a'],
                            ['id' => 'b'],
                            ['id' => 'c'],
                        ],
                    ],
                ],
            ]),
        ]);
    }
}

final class CountingAmpFutureAdapter extends AmpFutureAdapter
{
    public int $allCalls = 0;

    public int $thenCalls = 0;

    public function then(Promise $promise, ?callable $onFulfilled = null, ?callable $onRejected = null): Promise
    {
        ++$this->thenCalls;

        return parent::then($promise, $onFulfilled, $onRejected);
    }

    public function all(iterable $promisesOrValues): Promise
    {
        ++$this->allCalls;

        return parent::all($promisesOrValues);
    }
}
