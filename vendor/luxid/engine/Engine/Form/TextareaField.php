<?php

namespace Luxid\Form;

use Luxid\ORM\Entity;

class TextareaField extends BaseField
{
    public function renderInput(): string
    {
        $hasError = $this->entity->hasError($this->attribute);
        
        return sprintf('<textarea name="%s" class="%s">%s</textarea>',
            $this->attribute,
            htmlspecialchars($this->entity->{$this->attribute} ?? ''),
            $hasError ? 'error' : '',
            $hasError ? 'block' : 'none',

        );
    }
}

