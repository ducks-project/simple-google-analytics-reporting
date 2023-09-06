<?php

namespace DucksProject\Component\SimpleGoogleAnalyticsReporting\Attribute;

interface CollectionInterface extends \IteratorAggregate, \Countable
{
    public function add($path, string $name = null, int $priority = 0): self;
    public function get(string $name);
    public function remove($name): self;
}
