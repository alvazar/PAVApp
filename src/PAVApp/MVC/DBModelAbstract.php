<?php
namespace PAVApp\MVC;

use Throwable;
use PAVApp\Storage\DBQueryHelper;
use PAVApp\Storage\DBStorage;

/**
 * Базовый класс моделей хранящихся в БД.
 */
abstract class DBModelAbstract implements DBModelInterface
{
    /**
     * @var \PAVApp\Storage\DBQueryHelper
     */
    protected $QueryHelper;

    /**
     * @var \PDO
     */
    protected $DB;

    /**
     * @var string Таблица модели. Задаётся в классе потомке.
     */
    protected $table = '';

    /**
     * @var string Название поля id в таблице, например category_id. 
     * Задаётся в классе потомке.
     */
    protected $idName = 'ID';

    /**
     * @var array Список полей для фильтрации списка.
     * Используются в стандартных реализациях методов getList и save.
     */
    protected $whereFields = [];

    /**
     * @var array Список полей для сохранения данных модели.
     * Используется в стандартной реализации метода save.
     */
    protected $saveFields = [];

    public function __construct()
    {
        $this->QueryHelper = new DBQueryHelper();
        $this->DB = DBStorage::getInstance();
        $this->init();
    }

    /** Инициализирует свойства модели
     * @return void
     */
    public function init(): void
    {
    }

    /**
     * Сохраняет данные модели.
     * @param array $params
     * 
     * @return bool
     */
    public function save(array $params = []): bool
    {
        $queryData = [
            'fields' => $params['fields'] ?? [],
            'where' => $params['where'] ?? []
        ];
        
        if (!empty($queryData['where'])) {
            $queryType = 'update';
            if (!empty($queryData['where'][$this->idName])) {
                $queryData['limit'] = 1;
            }
        } else {
            $queryType = 'insert';
        }

        $queryMap = [
            'type' => $queryType,
            'table' => $this->table,
            'fields' => $this->saveFields,
            'where' => $this->whereFields
        ];

        try {
            $prepared = $this->QueryHelper->prepareToSQL(
                $queryMap,
                $queryData
            );
            
            if (count($prepared['errors']) > 0) {
                return false;
            }
            
            $stmt = $this->DB->prepare($prepared['prepareSQL']);
            $result = false;
            if ($stmt !== false) {
                $result = $stmt->execute($prepared['values']);
            }
        } catch (Throwable $err) {
            return false;
        }

        return $result;
    }

    /**
     * Возвращает список элементов.
     * @param array $params
     * 
     * @return array
     */
    public function getList(array $params = []): array
    {
        $result = [];
        try {

            $resultQuery = $this->querySelect($params);
            if ($resultQuery->success()) {
                $resultQuery = $resultQuery->result();
                while ($item = $resultQuery->fetch()) {
                    $result[] = $item;
                }
            }

        } catch (Throwable $err) {}

        return $result;
    }

    /**
     * Возвращает данные модели по ID.
     * @return array
     */
    public function getByID(int $ID): array
    {
        if (!empty($ID) > 0) {
            return $this->getList([
                'where' => [
                    $this->idName => $ID
                ]
            ])[0] ?? [];
        }
        return [];
    }

    /**
     * Удаляет элемент по ID.
     * @param int $ID
     * 
     * @return bool
     */
    public function delete(int $ID): bool
    {
        if (empty($ID)) {
            return false;
        }
        $qu = sprintf(
            'DELETE FROM %s WHERE %s = %d LIMIT 1',
            $this->table,
            $this->idName,
            $ID
        );
        return $this->db->query($qu);
    }

    /**
     * Делает запрос select и возвращает объект DBModelResultInterface
     * @param array $params
     * 
     * @return DBModelResultInterface
     */
    public function querySelect(array $params = []): DBModelResultInterface
    {
        $queryMap = [
            'type' => 'select',
            'table' => $this->table,
            'where' => $this->whereFields
        ];

        $result = null;

        $queryData = $params ?? [];
        $queryData['where'] = $queryData['where'] ?? [];

        try {
            $prepared = $this->QueryHelper->prepareToSQL(
                $queryMap,
                $queryData
            );

            if (count($prepared['errors']) === 0) {
                $stmt = $this->DB->prepare($prepared['prepareSQL']);
                if ($stmt !== false) {
                    if ($stmt->execute($prepared['values'])) {
                        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
                        $result = $stmt;
                    }
                }
            }
        } catch (Throwable $err) {}

        return new DBModelResult($result);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getWhereFields(): array
    {
        return $this->whereFields;
    }

    public function getSaveFields(): array
    {
        return $this->saveFields;
    }
}