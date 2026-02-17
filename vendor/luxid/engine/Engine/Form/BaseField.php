<?php

namespace Luxid\Form;

use Luxid\ORM\Entity;

abstract class BaseField
{
    public const TYPE_TEXT = 'text';
    public Entity $entity;
    public string $attribute;

    public string $type;
    abstract public function renderInput(): string;

    public function __construct(Entity $entity, string $attribute)
    {
        $this->type = self::TYPE_TEXT;
        $this->entity = $entity;
        $this->attribute = $attribute;
    }

    public function __toString()
    {
        return sprintf('
            <label>%s</label>
            %s
            <div class="invalid_feedback" style="display: %s;">
                %s
            </div>
        ',
            $this->entity->getLabel($this->attribute),
            $this->renderInput(),
            $this->entity->getFirstError($this->attribute)
        );
    }
}

