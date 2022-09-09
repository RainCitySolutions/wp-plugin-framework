<?php
namespace RainCity\WPF\Documentation;

class ShortCodeDocumentation
{
    /** @var string */
    private $name;

    /** @var string */
    private $description;

    /** @var string */
    private $example;

    /** @var ShortCodeAttribute[] */
    private $attributes = array();

    public function __construct(string $name, string $desciption = '') {
        $this->name = $name;
        $this->description = $desciption;
    }

    public function addAttribute(ShortCodeAttribute $attribute) {
        $this->attributes[] = $attribute;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description ?? '';
    }

    public function getExample(): string {
        return $this->example ?? '';
    }

    public function getAttributes(): array {
        return $this->attributes;
    }
}

