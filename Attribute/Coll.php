<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Attribute;

class Collection implements CollectionInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $items;

    /**
     * @var array<string, int>
     */
    private array $priorities;

    private function uksort(): self
    {
        if ($this->priorities) {
            $priorities = $this->priorities;
            $keysOrder = \array_flip(\array_keys($this->paths));
            \uksort($this->paths, static function ($n1, $n2) use ($priorities, $keysOrder) {
                return (($priorities[$n2] ?? 0) <=> ($priorities[$n1] ?? 0)) ?: ($keysOrder[$n1] <=> $keysOrder[$n2]);
            });
        }

        return $this;
    }

    public function __construct(...$items)
    {
        $this->addMultiple($items);
        $this->priorities = [];
    }


    public function __clone()
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index] = clone $item;
        }
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->all());
    }

    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * Returns all metas in this collection.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->uksort()->items;
    }

    public function has(string $name): bool
    {
        return isset($this->items[$name]);
    }

    public function get(string $name)
    {
        return $this->items[$name] ?? null;
    }

    public function add($item, string $name = null, int $priority = 0): self
    {
        $name ??= \spl_object_id($item);
        $this->items[$name] = $item;
        $this->priorities[$name] = $priority;

        return $this->uksort();
    }

    public function addCollection(CollectionInterface $collection): self
    {
        // we need to remove all items with the same names first
        // because just replacing them
        // would not place the new item at the end of the merged array
        foreach ($collection->all() as $name => $item) {
            unset($this->items[$name], $this->priorities[$name]);
            $this->items[$name] = $item;

            if (isset($collection->priorities[$name])) {
                $this->priorities[$name] = $collection->priorities[$name];
            }
        }

        return $this;
    }

    public function addCollection(self $collection): self
    {
        // we need to remove all routes with the same names first because just replacing them
        // would not place the new route at the end of the merged array
        foreach ($collection->all() as $name => $meta) {
            unset($this->metas[$name], $this->priorities[$name]);
            $this->metas[$name] = $meta;

            if (isset($collection->priorities[$name])) {
                $this->priorities[$name] = $collection->priorities[$name];
            }
        }

        return $this;
    }

    public function remove($name): self
    {
        foreach ((array) $name as $n) {
            unset($this->items[$n], $this->priorities[$n]);
        }

        return $this;
    }
}
