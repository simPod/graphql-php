<?php declare(strict_types=1);

namespace GraphQL\Executor\Promise\Adapter;

use Amp\DeferredFuture;
use Amp\Future;
use GraphQL\Error\InvariantViolation;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Executor\Promise\PromiseAdapter;

/**
 * Allows integration with amphp/amp v3 (fiber-based futures).
 *
 * @see https://amphp.org/amp
 *
 * @implements PromiseAdapter<Future<mixed>>
 */
class AmpFutureAdapter implements PromiseAdapter
{
    public function isThenable($value): bool
    {
        return $value instanceof Future;
    }

    /**
     * @throws InvariantViolation
     *
     * @phpstan-return Promise<Future<mixed>>
     */
    public function convertThenable($thenable): Promise
    {
        return new Promise($thenable, $this);
    }

    /**
     * @phpstan-param Promise<covariant Future<mixed>> $promise
     *
     * @throws InvariantViolation
     *
     * @phpstan-return Promise<Future<mixed>>
     */
    public function then(Promise $promise, ?callable $onFulfilled = null, ?callable $onRejected = null): Promise
    {
        $future = $promise->adoptedPromise;

        $deferred = new DeferredFuture();

        $future
            ->map(static function ($value) use ($deferred, $onFulfilled): void {
                try {
                    static::resolveDeferred($deferred, $onFulfilled === null ? $value : $onFulfilled($value));
                } catch (\Throwable $fulfillmentException) {
                    $deferred->error($fulfillmentException);
                }
            })
            ->catch(static function (\Throwable $exception) use ($deferred, $onRejected): void {
                if ($onRejected === null) {
                    $deferred->error($exception);

                    return;
                }

                try {
                    static::resolveDeferred($deferred, $onRejected($exception));
                } catch (\Throwable $rejectionException) {
                    $deferred->error($rejectionException);
                }
            })
            ->ignore();

        return new Promise($deferred->getFuture(), $this);
    }

    /** @throws InvariantViolation */
    public function create(callable $resolver): Promise
    {
        $deferred = new DeferredFuture();

        try {
            $resolver(
                static function ($value) use ($deferred): void {
                    static::resolveDeferred($deferred, $value);
                },
                static function (\Throwable $exception) use ($deferred): void {
                    $deferred->error($exception);
                }
            );
        } catch (\Throwable $exception) {
            $deferred->error($exception);
        }

        return new Promise($deferred->getFuture(), $this);
    }

    /**
     * @throws \Error
     * @throws InvariantViolation
     *
     * @phpstan-return Promise<Future<mixed>>
     */
    public function createFulfilled($value = null): Promise
    {
        if ($value instanceof Promise) {
            return $value;
        }

        if ($value instanceof Future) {
            return new Promise($value, $this);
        }

        return new Promise(Future::complete($value), $this);
    }

    /**
     * @throws InvariantViolation
     *
     * @phpstan-return Promise<Future<mixed>>
     */
    public function createRejected(\Throwable $reason): Promise
    {
        return new Promise(Future::error($reason), $this);
    }

    /**
     * @throws \Error
     * @throws InvariantViolation
     */
    public function all(iterable $promisesOrValues): Promise
    {
        $items = is_array($promisesOrValues)
            ? $promisesOrValues
            : iterator_to_array($promisesOrValues);

        $deferred = new DeferredFuture();
        $remaining = 0;
        $settled = false;

        foreach ($items as $key => $item) {
            if ($item instanceof Promise) {
                $item = $item->adoptedPromise;
            }

            if ($item instanceof Future) {
                ++$remaining;
                $item
                    ->map(static function ($value) use (&$items, $key, $deferred, &$remaining, &$settled): void {
                        if ($settled) {
                            return;
                        }

                        $items[$key] = $value;
                        --$remaining;
                        if ($remaining !== 0) {
                            return;
                        }

                        $settled = true;
                        $deferred->complete($items);
                    })
                    ->catch(static function (\Throwable $exception) use ($deferred, &$settled): void {
                        if ($settled) {
                            return;
                        }

                        $settled = true;
                        $deferred->error($exception);
                    })
                    ->ignore();
            }
        }

        if ($remaining === 0) {
            $settled = true;
            $deferred->complete($items);
        }

        return new Promise($deferred->getFuture(), $this);
    }

    /**
     * @param DeferredFuture<mixed> $deferred
     * @param mixed $value
     */
    protected static function resolveDeferred(DeferredFuture $deferred, $value): void
    {
        if ($value instanceof Promise) {
            $value = $value->adoptedPromise;
        }

        if ($value instanceof Future) {
            $value
                ->map(static function ($value) use ($deferred): void {
                    $deferred->complete($value);
                })
                ->catch(static function (\Throwable $exception) use ($deferred): void {
                    $deferred->error($exception);
                })
                ->ignore();

            return;
        }

        $deferred->complete($value);
    }
}
