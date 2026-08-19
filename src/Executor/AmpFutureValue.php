<?php declare(strict_types=1);

namespace GraphQL\Executor;

use Amp\Future;

/**
 * @internal
 */
final class AmpFutureValue
{
    /** @var \Closure(): mixed */
    private \Closure $resolver;

    /** @var mixed */
    private $value;

    private ?\Throwable $error = null;

    private bool $resolved = false;

    /** @param \Closure(): mixed $resolver */
    private function __construct(\Closure $resolver)
    {
        $this->resolver = $resolver;
    }

    /** @param \Closure(): mixed $resolver */
    public static function defer(\Closure $resolver): self
    {
        return new self($resolver);
    }

    /** @param Future<mixed> $future */
    public static function fromFuture(Future $future): self
    {
        return new self(static fn () => $future->await());
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
