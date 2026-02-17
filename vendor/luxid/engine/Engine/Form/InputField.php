<?php

namespace Luxid\Form;

use Luxid\ORM\Entity;

class InputField extends BaseField
{
    public const TYPE_TEXT = 'text';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_NUMBER = 'number';

    public string $type;
    public Entity $entity;
    public string $attribute;

    public function __construct(Entity $entity, string $attribute)
    {
        $this->type = self::TYPE_TEXT;
        parent::__construct($entity, $attribute);
    }

    public function passwordField()
    {
        $this->type = self::TYPE_PASSWORD;

        return $this;
    }

    public function renderInput(): string
    {
        $hasError = $this->entity->hasError($this->attribute);

        return sprintf('<input type="%s" name="%s" value="%s" class="%s">',
            $this->type,
            $this->attribute,
            htmlspecialchars($this->entity->{$this->attribute} ?? ''),
            $hasError ? 'error' : '',
            $hasError ? 'block' : 'none',
        );
    }
}

