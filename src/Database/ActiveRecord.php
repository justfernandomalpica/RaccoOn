<?php declare(strict_types=1);

namespace App\Database;

use ReflectionNamedType;
use ReflectionProperty;

/**
 * Lightweight Active Record base class for simple mysqli-backed models.
 *
 * Child models declare their table, columns and syncable fields, then inherit
 * common CRUD helpers for small applications.
 */
abstract class ActiveRecord
{
    private static ?Database $db = null;

    protected static string $table = '';
    protected static array $columns = [];
    protected static array $columnsToSync = [];

    protected ?int $id = null;
    protected array $errors = [];

    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * Set the database connection used by all ActiveRecord models.
     *
     * @param Database $database Database wrapper instance.
     * @return void
     */
    public static function setDB(Database $database): void
    {
        self::$db = $database;
    }

    /**
     * Insert a new model or update an existing one based on its ID.
     *
     * @return static|bool Saved model instance or false on failure.
     * @throws \RuntimeException When the model is not configured correctly.
     */
    public function save(): static|bool
    {
        if ($this->id === null) {
            $this->created_at = PROJECT_DATE_TIME;
            $this->updated_at = PROJECT_DATE_TIME;

            return $this->create();
        }

        $this->updated_at = PROJECT_DATE_TIME;
        return $this->update();
    }

    /**
     * Fetch a paginated list of records for the model table.
     *
     * @param int $page Page number starting at 1.
     * @param int $perPage Records returned per page.
     * @return array<int, static>
     * @throws \RuntimeException When the model is not configured correctly.
     */
    public static function all(int $page = 1, int $perPage = 10): array
    {
        self::initialValidation();

        $table = static::$table;
        $offset = ($page > 1) ? ($page - 1) * $perPage : 0;
        $query = "SELECT * FROM {$table} LIMIT ? OFFSET ?";

        return self::fetchAll($query, [$perPage, $offset]);
    }

    /**
     * Fetch all records without pagination.
     *
     * @return array<int, static>
     * @throws \RuntimeException When the model is not configured correctly.
     */
    public static function allNoPaginated(): array
    {
        self::initialValidation();

        $table = static::$table;
        $query = "SELECT * FROM {$table}";

        return self::fetchAll($query);
    }

    /**
     * Find one record by its numeric ID.
     *
     * @param int $id Primary key value.
     * @return static|null
     * @throws \RuntimeException When the model is not configured correctly.
     */
    public static function find(int $id): ?static
    {
        self::initialValidation();

        if ($id <= 0) {
            return null;
        }

        $table = static::$table;
        $query = "SELECT * FROM {$table} WHERE id = ? LIMIT 1";

        return self::fetch($query, [$id]);
    }

    /**
     * Fetch a limited list of records.
     *
     * @param int $limit Maximum number of records to return.
     * @return array<int, static>
     * @throws \RuntimeException When the model is not configured correctly.
     */
    public static function get(int $limit): array
    {
        self::initialValidation();

        if ($limit <= 0) {
            return [];
        }

        $table = static::$table;
        $query = "SELECT * FROM {$table} LIMIT ?";

        return self::fetchAll($query, [$limit]);
    }

    /**
     * Fetch records where a declared column equals the provided value.
     *
     * @param string $column Declared model column to filter by.
     * @param mixed $value Value compared with the column.
     * @param int $page Page number starting at 1.
     * @param int $perPage Records returned per page.
     * @return array<int, static>
     * @throws \RuntimeException When the model is not configured correctly.
     */
    public static function where(string $column, mixed $value, int $page = 1, int $perPage = 10): array
    {
        self::initialValidation();

        if (!in_array($column, static::$columns, true)) {
            return [];
        }

        $table = static::$table;
        $offset = ($page > 1) ? ($page - 1) * $perPage : 0;
        $query = "SELECT * FROM {$table} WHERE {$column} = ? LIMIT ? OFFSET ?";

        return self::fetchAll($query, [$value, $perPage, $offset]);
    }

    /**
     * Fetch the first record where a declared column equals the provided value.
     *
     * @param string $column Declared model column to filter by.
     * @param mixed $value Value compared with the column.
     * @return static|null
     * @throws \RuntimeException When the model is not configured correctly.
     */
    public static function findBy(string $column, mixed $value): ?static
    {
        self::initialValidation();

        if (!in_array($column, static::$columns, true)) {
            return null;
        }

        $table = static::$table;
        $query = "SELECT * FROM {$table} WHERE {$column} = ? LIMIT 1";

        return self::fetch($query, [$value]);
    }

    /**
     * Count all records in the model table.
     *
     * @return int
     * @throws \RuntimeException When the model is not configured correctly.
     */
    public static function count(): int
    {
        self::initialValidation();

        $table = static::$table;
        $result = self::$db->query("SELECT COUNT(*) AS total FROM {$table}");

        if (!$result instanceof \mysqli_result) {
            return 0;
        }

        return (int) ($result->fetch_assoc()['total'] ?? 0);
    }

    /**
     * Copy allowed input fields into the model through matching setter methods.
     *
     * @param array $data Associative input data.
     * @return static
     * @throws \InvalidArgumentException When the input array is empty or not associative.
     * @throws \RuntimeException When syncable fields are not configured correctly.
     */
    public function sync(array $data): static
    {
        self::initialValidation();
        self::syncValidation();

        if ($data === []) {
            throw new \InvalidArgumentException('Cannot sync with empty data.');
        }

        if (array_is_list($data)) {
            throw new \InvalidArgumentException('Data array must be associative.');
        }

        foreach ($data as $key => $value) {
            if (!in_array($key, static::$columnsToSync, true)) {
                continue;
            }

            if ($key === 'id') {
                continue;
            }

            call_user_func([$this, $key], $value);
        }

        return $this;
    }

    /**
     * Update the current record in the database.
     *
     * @return static|bool Updated model instance or false on failure.
     * @throws \RuntimeException When the model is not configured correctly.
     */
    public function update(): static|bool
    {
        self::initialValidation();

        $table = static::$table;
        $attrs = $this->getAttributes();

        if ($this->id === null || $attrs === []) {
            return false;
        }

        $columns = array_keys($attrs);
        $params = array_values($attrs);
        $values = self::buildPlaceholderPairsChain($columns);
        $query = "UPDATE {$table} SET {$values} WHERE id = ? LIMIT 1";

        $params[] = $this->id;
        $result = self::$db->query($query, $params);

        return $result ? $this : false;
    }

    /**
     * Delete the current record from the database.
     *
     * @return bool
     * @throws \RuntimeException When the model is not configured correctly.
     */
    public function delete(): bool
    {
        self::initialValidation();

        if ($this->id === null) {
            return false;
        }

        $table = static::$table;
        $result = self::$db->query("DELETE FROM {$table} WHERE id = ? LIMIT 1", [$this->id]);

        if ($result === false) {
            return false;
        }

        $this->id = null;
        return true;
    }

    /**
     * Check whether validation or domain errors were registered on the model.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Return all registered model errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Return errors grouped under a specific key.
     *
     * @param string $head Error group name.
     * @return array
     * @throws \InvalidArgumentException When the group name is empty.
     */
    public function getErrorsByHead(string $head): array
    {
        $head = trim($head);

        if ($head === '') {
            throw new \InvalidArgumentException('Empty error head provided.');
        }

        return $this->errors[$head] ?? [];
    }

    /**
     * Return the model primary key value.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Register a validation or domain error on the model.
     *
     * @param string $head Error group name.
     * @param string $body Error message.
     * @return void
     * @throws \InvalidArgumentException When either value is empty.
     */
    protected function setError(string $head, string $body): void
    {
        if ($head === '' || $body === '') {
            throw new \InvalidArgumentException('A model error cannot be empty.');
        }

        $this->errors[$head][] = $body;
    }

    private function create(): static|bool
    {
        self::initialValidation();

        $table = static::$table;
        $attrs = $this->getAttributes();

        if ($attrs === []) {
            return false;
        }

        $columns = implode(', ', array_keys($attrs));
        $params = array_values($attrs);
        $values = self::buildPlaceholdersChain($params);
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";
        $result = self::$db->query($query, $params);

        if (!$result) {
            return false;
        }

        $this->id = (int) self::$db->lastInsertId();
        return $this;
    }

    private function getAttributes(): array
    {
        $attrs = [];

        foreach (static::$columns as $column) {
            if (!property_exists($this, $column)) {
                continue;
            }

            if ($column === 'id') {
                continue;
            }

            $attrs[$column] = $this->{$column};
        }

        return $attrs;
    }

    private static function buildPlaceholdersChain(array $attrs): string
    {
        return implode(', ', array_fill(0, count($attrs), '?'));
    }

    private static function buildPlaceholderPairsChain(array $columns): string
    {
        return implode(', ', array_map(fn (string $column): string => "{$column} = ?", $columns));
    }

    private static function objectify(array $data): static
    {
        $object = new static();

        foreach ($data as $key => $value) {
            if (!property_exists($object, $key)) {
                continue;
            }

            self::setObjectProperty($object, $key, $value);
        }

        return $object;
    }

    private static function setObjectProperty(object $object, string $key, mixed $value): void
    {
        $property = new ReflectionProperty($object, $key);
        $property->setAccessible(true);

        if ($value === null) {
            $property->setValue($object, null);
            return;
        }

        $type = $property->getType();

        if ($type instanceof ReflectionNamedType) {
            $value = match ($type->getName()) {
                'int' => (int) $value,
                'float' => (float) $value,
                'bool' => (bool) $value,
                'string' => (string) $value,
                default => $value,
            };
        }

        $property->setValue($object, $value);
    }

    /**
     * Fetch one row and convert it into the called model class.
     *
     * @param string $query SQL query.
     * @param array $params Values bound to the query.
     * @return static|null
     */
    protected static function fetch(string $query, array $params = []): ?static
    {
        $result = self::$db->query($query, $params);

        if (!$result instanceof \mysqli_result || $result->num_rows === 0) {
            return null;
        }

        return self::objectify($result->fetch_assoc());
    }

    /**
     * Fetch multiple rows and convert them into the called model class.
     *
     * @param string $query SQL query.
     * @param array $params Values bound to the query.
     * @return array<int, static>
     */
    protected static function fetchAll(string $query, array $params = []): array
    {
        $result = self::$db->query($query, $params);

        if (!$result instanceof \mysqli_result) {
            return [];
        }

        $items = [];

        while ($row = $result->fetch_assoc()) {
            $items[] = self::objectify($row);
        }

        return $items;
    }

    /**
     * Fetch one raw associative row for custom model queries.
     *
     * @param string $query SQL query.
     * @param array $params Values bound to the query.
     * @return array|null
     */
    protected static function query(string $query, array $params = []): ?array
    {
        $result = self::$db->query($query, $params);

        if (!$result instanceof \mysqli_result) {
            return null;
        }

        return $result->fetch_assoc() ?: null;
    }

    /**
     * Validate the minimum model configuration before database operations.
     *
     * @return void
     * @throws \RuntimeException When the database, table or columns are invalid.
     */
    protected static function initialValidation(): void
    {
        $baseErrorMsg = 'Active Record error: ';

        if (self::$db === null) {
            throw new \RuntimeException($baseErrorMsg . 'A database instance must be set before any operation.');
        }

        if (trim(static::$table) === '') {
            throw new \RuntimeException($baseErrorMsg . 'Table name cannot be empty.');
        }

        if (static::$columns === []) {
            throw new \RuntimeException($baseErrorMsg . 'At least one column must be declared.');
        }

        foreach (static::$columns as $column) {
            if (!property_exists(static::class, $column)) {
                throw new \RuntimeException($baseErrorMsg . "Declared column '{$column}' does not have a matching model property.");
            }
        }
    }

    private static function syncValidation(): void
    {
        $baseErrorMsg = 'Active Record error: ';

        if (static::$columnsToSync === []) {
            throw new \RuntimeException($baseErrorMsg . 'At least one column must be syncable.');
        }

        foreach (static::$columnsToSync as $columnToSync) {
            if (!in_array($columnToSync, static::$columns, true)) {
                throw new \RuntimeException($baseErrorMsg . "Only declared columns with matching attributes can be synced. [{$columnToSync}]");
            }

            if (!method_exists(static::class, $columnToSync)) {
                throw new \RuntimeException($baseErrorMsg . "Declared syncable column '{$columnToSync}' does not have a matching setter.");
            }
        }
    }
}
