<?php
namespace PAVApp\MVC;

use PAVApp\Storage\DBStorage;

/**
 * Класс для работы с результатом запроса объекта DBModel
 */
class DBModelResult implements DBModelResultInterface
{
    protected $result;

    public function __construct(?object $result)
    {
        $this->result = $result;
    }

    public function getList(): array
    {
        if (!$this->success()) {
            return [];
        }

        $result = [];
        $queryResult = $this->result();

        while ($item = $queryResult->fetch()) {
            $result[] = $item;
        }
        
        return $result;
    }

    public function result(): object
    {
        return $this->result->stmt;
    }

    /**
     * Метод возвращает true, если существует 
     * хоть одна запись в БД для ранее отправленного запроса
     * или false, если нет.
     * @param array $params
     * 
     * @return bool
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function count(): int
    {
        $rowCount = 0;
        
        if (empty($this->result)) {
            return $rowCount;
        }
        
        $DB = DBStorage::getInstance();
        $prepared = $this->result->prepared;
        
        // remove columns list
        $qu = preg_replace(
            '/^SELECT (.+?) FROM/i',
            'SELECT 0 FROM',
            $prepared['prepareSQL']
        );

        // calculate count rows _after_ query exec (need if query has group by)
        $qu = sprintf(
            'SELECT COUNT(*) AS rowCount FROM (%s) AS t1',
            $qu
        );
        $stmt = $DB->prepare($qu);

        if ($stmt->execute($prepared['values'])) {
            $stmt->setFetchMode(\PDO::FETCH_ASSOC);
            $rowCount = (int) $stmt->fetch()['rowCount'];
        }
        
        return $rowCount;
    }

    public function success(): bool
    {
        return !empty($this->result);
    }
}
