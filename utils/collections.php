<?php

namespace TodoListGold\Utils\Collections;

use ArrayAccess;
use Countable;
use Iterator;
use JsonSerializable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;


interface ISortedCollection
{
    public function sort(bool $inverse = false): void;
}


interface ICollection
{
    public function toArray(): array;
    public function toDoctrineColletion(): Collection;
    public function count(): int;
    public function isEmpty(): bool;
    public function isNotEmpty(): bool;
}


/**
 * OrderedSet is a Collection that maintains the order of elements
 * and ensures that all elements are unique.
 * It implements ICollection, ArrayAccess, Countable, Iterator,
 */
class OrderedSet implements ICollection, ArrayAccess, Countable, Iterator, JsonSerializable, ISortedCollection
{
    private array $arr = [];
    private int $position = 0;

    public function __construct(array $arr = [])
    {
        $this->arr = array_values(array_unique($arr));
        $this->position = 0;
    }

    public function inArray(mixed $element): bool
    {
        return in_array($element, $this->arr, true);
    }

    /** @return bool True if Unique, False if already in the Set */
    public function add(mixed $element): bool
    {
        $unique = !$this->inArray($element);
        if ($unique) {
            $this->arr[] = $element;
        }

        return $unique;
    }

    /** @return int 0 if all elements are unique else the number of elements that are already in the Set */
    public function addAll(array $elements): int
    {
        $inArray = 0;
        foreach ($elements as $element) {
            $this->add($element) ? : ++$inArray;
        }

        return $inArray;
    }

    public function first(): mixed
    {
        return $this->arr[0] ?? null;
    }

    public function last(): mixed
    {
        return end($this->arr) ?: null;
    }

    #region ICollection

    public function toArray(): array
    {
        return $this->arr;
    }

    public function toDoctrineColletion(): Collection
    {
        return new ArrayCollection($this->arr);
    }

    public function isEmpty(): bool
    {
        return empty($this->arr);
    }

    public function isNotEmpty(): bool
    {
        return !empty($this->arr);
    }

    #endregion
    #region ArrayAccess

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->arr[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->arr[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->arr[] = $value;
        } else {
            $this->arr[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->arr[$offset]);
    }

    #endregion
    #region Countable

    public function count(): int
    {
        return count($this->arr);
    }

    #endregion
    #region Iterator
    public function current(): mixed
    {
        return $this->arr[$this->position] ?? null;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->arr[$this->position]);
    }

    #endregion
    #region JsonSerializable

    public function jsonSerialize(): array
    {
        return $this->arr;
    }

    #endregion
    #region ISortedCollection

    public function sort(bool $inverse = false): void
    {
        if ($inverse) {
            rsort($this->arr);
        } else {
            sort($this->arr);
        }
    }

    #endregion
}
