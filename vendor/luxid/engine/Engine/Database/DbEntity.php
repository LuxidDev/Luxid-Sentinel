<?php

namespace Luxid\Database;

use Luxid\ORM\Entity;
use Luxid\Foundation\Application;

/**
 * Base Active Record Class (extends Luxid\ORM\Entity)
 * This would be an entity which would be like an ORM
 * and would map the User's Entity into the Database Table.
 *
 * @property int $id The primary key
 * @method bool save() Save the entity
 * @method bool update() Update the entity
 * @method bool delete() Delete the entity
 * @method static static|null findOne(array $where) Find one entity
 * @method static array findAll(array $where = [], string $orderBy = '') Find all entities
 * @method static static|null find(int $id) Find by ID
 */
abstract class DbEntity extends Entity
{
    abstract public static function tableName(): string;
    abstract public function attributes(): array; // -> all db column names
    abstract public static function primaryKey(): string;

    public function save(): bool
    {
        $tableName = $this->tableName();
        $attributes = $this->attributes();

        $params = array_map(fn($attr) => ":$attr", $attributes);

        $statement = self::prepare("
            INSERT INTO $tableName (".implode(',', $attributes).")
            VALUES(".implode(',', $params) . ")
        ");

        foreach ($attributes as $attribute) {
            $value = $this->{$attribute} ?? null;

            // Normalize booleans for MySQL
            if (is_bool($value)) {
                $value = $value ? 1 : 0;
            }

            $statement->bindValue(":$attribute", $value);
        }

        $statement->execute();

        // set the ID if this is a new record
        if ($this->{static::primaryKey()} === 0) {
            $this->{static::primaryKey()} = self::lastInsertId();
        }

        return true;
    }

    public function update(): bool
    {
        $tableName = $this->tableName();
        $primaryKey = static::primaryKey();
        $attributes = $this->attributes();

        // Remove primary key from update attributes
        $updateAttributes = array_filter($attributes, fn($attr) => $attr !== $primaryKey);

        $setClause = implode(', ', array_map(fn($attr) => "$attr = :$attr", $updateAttributes));

        $statement = self::prepare("
            UPDATE $tableName
            SET $setClause
            WHERE $primaryKey = :id
        ");

        foreach ($updateAttributes as $attribute) {
            $statement->bindValue(":$attribute", $this->{$attribute});
        }
        $statement->bindValue(":id", $this->{$primaryKey});

        return $statement->execute();
    }

    public function delete(): bool
    {
        $tableName = $this->tableName();
        $primaryKey = static::primaryKey();

        $statement = self::prepare("
            DELETE FROM $tableName
            WHERE $primaryKey = :id
        ");

        $statement->bindValue(":id", $this->{$primaryKey});

        return $statement->execute();
    }

    public static function findOne($where): bool|object|null  // -> [email => jhay@gmail.com, firstname => jhay]
    {
        $tableName = static::tableName();
        $attributes = array_keys($where);
        $sql = implode(" AND ", array_map(fn($attr) => "$attr = :$attr", $attributes));

        // SELECT * FROM $tableName WHERE email = :email AND firstname = :firstname
        $statement = static::prepare("SELECT * FROM $tableName WHERE $sql");
        foreach ($where as $key => $item) {
            $statement->bindValue(":$key", $item);
        }

        $statement->execute();
        return $statement->fetchObject(static::class) ?: null;  // gives me an instance of the user class
    }

    public static function findAll(array $where = [], string $orderBy = ''): array
    {
        $tableName = static::tableName();
        $sql = "SELECT * FROM $tableName";

        if (!empty($where)) {
            $attributes = array_keys($where);
            $whereClause = implode(" AND ", array_map(fn($attr) => "$attr = :$attr", $attributes));
            $sql .= " WHERE $whereClause";
        }

        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }

        $statement = static::prepare($sql);

        foreach ($where as $key => $item) {
            $statement->bindValue(":$key", $item);
        }

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_CLASS, static::class);
    }

    public static function find(int $id): ?static
    {
        return static::findOne([static::primaryKey() => $id]);
    }

    public static function prepare($sqlStatement)
    {
        return Application::$app->db->pdo->prepare($sqlStatement);
    }

    public static function lastInsertId(): int
    {
        return (int) Application::$app->db->pdo->lastInsertId();
    }
}
