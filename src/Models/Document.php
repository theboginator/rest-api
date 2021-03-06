<?php declare(strict_types=1);

namespace Reconmap\Models;

class Document
{
    public int $user_id;
    public string $visibility;
    public ?int $parent_id;
    public string $parent_type;
    public string $content;
    public ?string $title;

    static public function fromObject(object $object): static
    {
        $self = new static();
        $other = new \ReflectionObject($object);
        $props = array_filter($other->getProperties(), fn($prop) => property_exists($self, $prop->getName()));
        array_walk($props, fn($prop) => $self->{$prop->getName()} = $prop->getValue($object));
        return $self;
    }
}
