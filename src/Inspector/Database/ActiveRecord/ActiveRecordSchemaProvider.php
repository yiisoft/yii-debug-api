<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\ActiveRecord;

use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\ActiveRecordFactory;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Schema\ColumnSchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Yii\Debug\Api\Inspector\Database\SchemaProviderInterface;

class ActiveRecordSchemaProvider implements SchemaProviderInterface
{
    public function __construct(
        private ConnectionInterface $connection,
        private ActiveRecordFactory $activeRecordFactory,
    ) {
    }

    public function getTables(): array
    {
        /** @var TableSchemaInterface[] $tableSchemas */
        $tableSchemas = $this->connection->getSchema()->getTableSchemas();

        $tables = [];
        foreach ($tableSchemas as $schema) {
            $activeQuery = $this->activeRecordFactory->createQueryTo(ActiveRecord::class, $schema->getName());

            /**
             * @var ActiveRecord[] $records
             */
            $records = $activeQuery->count();

            $tables[] = [
                'table' => $schema->getName(),
                'primaryKeys' => $schema->getPrimaryKey(),
                'columns' => $this->serializeARColumnsSchemas($schema->getColumns()),
                'records' => $records,
            ];
        }
        return $tables;
    }

    public function getTable(string $tableName): array
    {
        /** @var TableSchemaInterface[] $tableSchemas */
        $schema = $this->connection->getSchema()->getTableSchema($tableName);

        $activeQuery = $this->activeRecordFactory->createQueryTo(ActiveRecord::class, $tableName);

        /**
         * @var ActiveRecord[] $records
         */
        $records = $activeQuery->all();

        $data = [];
        // TODO: add pagination
        foreach ($records as $n => $record) {
            foreach ($record->attributes() as $attribute) {
                $data[$n][$attribute] = $record->{$attribute};
            }
        }

        return [
            'table' => $schema->getName(),
            'primaryKeys' => $schema->getPrimaryKey(),
            'columns' => $this->serializeARColumnsSchemas($schema->getColumns()),
            'records' => $data,
        ];
    }

    /**
     * @param ColumnSchemaInterface[] $columns
     */
    private function serializeARColumnsSchemas(array $columns): array
    {
        $result = [];
        foreach ($columns as $columnSchema) {
            $result[] = [
                'name' => $columnSchema->getName(),
                'size' => $columnSchema->getSize(),
                'type' => $columnSchema->getType(),
                'dbType' => $columnSchema->getDbType(),
                'defaultValue' => $columnSchema->getDefaultValue(),
                'comment' => $columnSchema->getComment(),
                'allowNull' => $columnSchema->isAllowNull(),
            ];
        }
        return $result;
    }
}
