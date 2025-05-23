<?php
namespace RainCity\WPF\Documentation;

class ShortCodeAttribute
{
    /** @var string */
    private string $name;

    /** @var bool */
    private bool $required;

    /** @var string */
    private ?string $default;

    /** @var string */
    private string $description;

    public function __construct(string $name, string $description, bool $required = false, ?string $default = null) {
        if ($required && isset($default)) {
            throw new \InvalidArgumentException('Attribute cannot be required and have a default value');
        }

        if (!$required && !isset($default)) {
            throw new \InvalidArgumentException('Attribute cannot be optional and not have a default value');
        }

        $this->name = $name;
        $this->description = $description;
        $this->required = $required;
        $this->default = $default;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function isRequired(): bool {
        return $this->required;
    }

    public function getDefault(): string {
        return $this->default ?? '';
    }
}
