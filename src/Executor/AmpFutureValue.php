<?php declare(strict_types=1);

namespace GraphQL\Executor;

use Amp\Future;
use GraphQL\Error\InvariantViolation;
use GraphQL\Executor\Promise\Adapter\AmpFutureAdapter;
use GraphQL\Executor\Promise\Promise;

/**
 * @internal
 */
final class AmpFutureValue extends Promise
{
    /** @var \Closure(): mixed */
    private \Closure $resolver;

    /** @var mixed */
    private $value;

    private ?\Throwable $error = null;

    private bool $resolved = false;

    private AmpFutureAdapter $adapter;

    /**
     * @param \Closure(): mixed $resolver
     *
     * @throws InvariantViolation
     */
    private function __construct(\Closure $resolver, AmpFutureAdapter $adapter)
    {
        parent::__construct(null, $adapter);
        $this->resolver = $resolver;
        $this->adapter = $adapter;
    }

    /**
     * @param \Closure(): mixed $resolver
     *
     * @throws InvariantViolation
     */
    public static function defer(\Closure $resolver, AmpFutureAdapter $adapter): self
    {
        return new self($resolver, $adapter);
    }

    /**
     * @param Future<mixed> $future
     *
     * @throws InvariantViolation
     */
    public static function fromFuture(Future $future, AmpFutureAdapter $adapter): self
    {
        return new self(static fn () => $future->await(), $adapter);
    }

    /** @throws InvariantViolation */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): Promise
    {
        return self::defer(function () use ($onFulfilled, $onRejected) {
            try {
                $value = $this->await();

                return $onFulfilled === null ? $value : $onFulfilled($value);
            } catch (\Throwable $error) {
                if ($onRejected === null) {
                    throw $error;
                }

                return $onRejected($error);
            }
        }, $this->adapter);
    }

    /** @return mixed */
    public function await()
    {
        if ($this->resolved) {
            if ($this->error !== null) {
                throw $this->error;
            }

            return $this->value;
        }

        try {
            $value = ($this->resolver)();
            while ($value instanceof self) {
                $value = $value->await();
            }

            if ($value instanceof Future) {
                $value = $value->await();
            }

            $this->value = $value;
        } catch (\Throwable $error) {
            $this->error = $error;
        }

        $this->resolved = true;

        if ($this->error !== null) {
            throw $this->error;
        }

        return $this->value;
    }
}
