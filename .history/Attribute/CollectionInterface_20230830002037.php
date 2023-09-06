<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Attribute;

interface CollectionInterface extends \IteratorAggregate, \Countable
{
    public function add(self $meta, ?string $key = null, int $priority = 0): self;
    public function get(string $type, ?string $key = null);
    public function remove(string $type, ?string $key = null): self;
}
