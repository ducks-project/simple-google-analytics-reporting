<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Attribute;

class Collection implements CollectionInterface
{
    /**
     * @var array<string, MetaInterface>
     */
    protected array $items;

    /**
     * @var array<string, int>
     */
    private array $priorities;

    private function getMetaKey(MetaInterface $meta, ?string $key = null): string
    {
        $key = ($key) ?: $meta->getKey();
        return $meta->getIdentifier() . '__' . $key;
    }

    public function __construct(...$items)
    {
        $this->addMultiple($items);
        $this->priorities = [];
    }


    public function __clone()
    {
        foreach ($this->items as $name => $item) {
            $this->items[$name] = clone $item;
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
     * @return array<string, MetaInterface>
     */
    public function all(): array
    {
        if ($this->priorities) {
            $priorities = $this->priorities;
            $keysOrder = array_flip(array_keys($this->metas));
            uksort($this->metas, static function ($n1, $n2) use ($priorities, $keysOrder) {
                return (($priorities[$n2] ?? 0) <=> ($priorities[$n1] ?? 0)) ?: ($keysOrder[$n1] <=> $keysOrder[$n2]);
            });
        }

        return $this->metas;
    }

    public function has(string $type, ?string $key = null): bool
    {
        $key = $key ?? '0';
        return isset($this->metas[$type . '__' . $key]);
    }

    public function get(string $type, ?string $key = null): ?MetaInterface
    {
        $key = $key ?? '0';
        return $this->metas[$type . '__' . $key] ?? null;
    }

    public function add(MetaInterface $meta, ?string $key = null, int $priority = 0): self
    {
        $name = $this->getMetaKey($meta, $key);
        unset($this->metas[$name], $this->priorities[$name]);

        $this->metas[$name] = $meta;
        $this->notify(MetaCollectionInterface::EVENT_ADD, $meta);

        if ($priority) {
            $this->priorities[$name] = $priority;
        }

        return $this;
    }

    public function addMultiple(array $metas): self
    {
        foreach ($metas as $meta) {
            if ($meta instanceof MetaInterface) {
                $this->add($meta);
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

    public function remove(string $type, ?string $key = null): self
    {
        $key = $key ?? '0';
        if ($this->has($type, $key)) {
            $this->notify(MetaCollectionInterface::EVENT_REMOVE, $this->metas[$type . '__' . $key]);
            unset($this->metas[$type . '__' . $key]);
        }

        return $this;
    }
}
