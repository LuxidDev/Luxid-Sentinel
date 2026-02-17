<?php

namespace Luxid\ORM;

use Luxid\Database\DbEntity;

abstract class UserEntity extends DbEntity
{
    abstract public function getDisplayName(): string;
}
