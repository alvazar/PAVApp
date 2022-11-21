<?php
namespace PAVApp\Storage;

use PAVApp\Core\Validator;

/**
 * Класс-хелпер для работы с SQL запросами.
 */
class DBQueryHelper
{
    /**
     * @var PAVApp\Core\Validator
     */
    protected Validator $validator;

    /**
     * @var array
     */
    protected array $queryData;

    /**
     * @var array
     */
    protected array $params;

    /**
     * @var array
     */
    protected array $result;

    /**
     */
    public function __construct()
    {
        $this->validator = new Validator();

        $this->reset();
    }

    /**
     * @return self
     */
    public function reset(): self
    {
        // Части запроса.
        $this->queryData = [
            'table' => '',
            'join' => '',
            'selectNames' => '',
            'insertNames' => '',
            'updates' => '',
            'values' => '',
            'where' => '',
            'groupBy' => '',
            'orderBy' => '',
            'limit' => ''
        ];

        $this->params = [];

        $this->result = [
            'prepareSQL' => '',
            'values' => [],
            'types' => '',
            'errors' => []
        ];

        return $this;
    }

    /**
     * Проверяет значения полей и формирует sql запрос.
     * @param array $params
     * @param array $data
     * 
     * @return array
     */
    public function prepareToSQL(array $params, array $data): array
    {
        // Шаблоны sql запросов.
        $queryTempl = [
            'select' => 'SELECT {selectNames} FROM {table} {join} {where} {groupBy} {orderBy} {limit}',
            'insert' => 'INSERT INTO {table} ({insertNames}) VALUES ({values})',
            'update' => 'UPDATE {table} SET {updates} {where} {limit}',
        ];

        $this->reset();
        $this->params = $params;

        $this->makeTable($this->params['table'] ?? '');

        $this->makeSelect($data['select'] ?? []);

        if (!empty($data['as'])) {
            $this->queryData['table'] .= sprintf(' %s', $data['as']);
            $this->params['where'] = $this->addPrefix($this->params['where'], $data['as'] . '.');
        }

        // joins
        if (!empty($data['join'])) {
            $this->makeJoin($data['join']);
        }

        // Валидатор
        $validator = $this->validator;

        // Поля элемента для сохранения в БД
        if (!empty($data['fields'])) {
            $this->makeFields($data['fields']);
        }

        if ($validator->hasError()) {
            $this->result['errors'] = $validator->getErrors();

            return $this->result;
        }

        // Поля для фильтрации списка
        if (!empty($data['where'])) {
            $this->makeWhere($data['where']);
        }

        if ($validator->hasError()) {
            $this->result['errors'] = $validator->getErrors();

            return $this->result;
        }

        // Сортировка
        if (!empty($data['orderBy'])) {
            $this->makeOrderBy($data['orderBy']);
        }

        if ($validator->hasError()) {
            $this->result['errors'] = $validator->getErrors();

            return $this->result;
        }

        // Группировка по полям
        if (!empty($data['groupBy'])) {
            $this->makeGroupBy($data['groupBy']);
        }

        if ($validator->hasError()) {
            $this->result['errors'] = $validator->getErrors();

            return $this->result;
        }

        // Лимит, либо интервал выборки
        if (!empty($data['limit'])) {
            $this->makeLimit($data['limit']);
        }

        // Формирование sql запроса
        if (!empty($this->params['type']) && isset($queryTempl[$this->params['type']])) {
            $this->result['prepareSQL'] = $queryTempl[$this->params['type']];

            foreach ($this->queryData as $key => $value) {
                $this->result['prepareSQL'] = str_replace('{'.$key.'}', $value, $this->result['prepareSQL']);
            }

            $this->result['prepareSQL'] = trim($this->result['prepareSQL']);
        }

        if ($validator->hasError()) {
            $this->result['errors'] = $validator->getErrors();
        }

        return $this->result;
    }

    /**
     * @param array $data
     * @param string $prefix
     * 
     * @return array
     */
    protected function addPrefix (array $data, string $prefix): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[$prefix . $key] = $value;
        }

        return $result;
    }

    /**
     * @param string $table
     * 
     * @return self
     */
    protected function makeTable(string $table): self
    {
        $this->queryData['table'] = $table;

        return $this;
    }

    /**
     * @param array $selectNames
     * 
     * @return self
     */
    protected function makeSelect(array $selectNames): self
    {
        $this->queryData['selectNames'] = !empty($selectNames)
            ? implode(',', $selectNames)
            : '*';

        return $this;
    }

    /**
     * @param array $data
     * 
     * @return self
     */
    protected function makeJoin(array $data): self
    {
        foreach ($data as $modelName => $joinData) {
                
            $modelObj = new $modelName;
            $modelTable = $modelObj->getTable();
            $modelWhereFields = $modelObj->getWhereFields();

            $this->queryData['join'] .= sprintf("\n LEFT JOIN %s", $modelTable);

            if (!empty($joinData['as'])) {
                $this->queryData['join'] .= ' ' . $joinData['as'];
                $modelWhereFields = $this->addPrefix($modelWhereFields, $joinData['as'] . '.');
                $this->params['where'] = array_merge($this->params['where'], $modelWhereFields);
            }

            if (!empty($joinData['on'])) {
                $this->queryData['join'] .= ' ON ';

                foreach ($joinData['on'] as $joinOnData) {
                    $this->queryData['join'] .= sprintf(' %s = %s AND ', $joinOnData[0], $joinOnData[1]);
                }

                $this->queryData['join'] = mb_substr($this->queryData['join'], 0, -5);
            }

            $this->queryData['join'] .= " \n";
        }

        return $this;
    }

    /**
     * @param array $data
     * 
     * @return self
     */
    protected function makeFields(array $data): self
    {
        // Проверка полей
        if ($this->validator->check($this->params['fields'], $data)->hasError()) {
            return $this;
        }

        $type = 's';

        foreach ($data as $name => $value) {

            if (!empty($this->params['fields'][$name])) {
                $this->result['values'][] = is_array($value) ? json_encode($value) : $value;
                $this->result['types'] .= $type;
                $this->queryData['insertNames'] .= sprintf('%s, ', $name);
                $this->queryData['updates'] .= sprintf('%s = ?, ', $name);
                $this->queryData['values'] .= '?, ';
            }
        }

        if ($this->queryData['insertNames'] !== '') {
            $this->queryData['insertNames'] = mb_substr($this->queryData['insertNames'], 0, -2);
            $this->queryData['updates'] = mb_substr($this->queryData['updates'], 0, -2);
            $this->queryData['values'] = mb_substr($this->queryData['values'], 0, -2);
        }

        return $this;
    }

    /**
     * @param array $data
     * 
     * @return self
     */
    protected function makeWhere(array $data): self
    {       
        if ($this->validator->check($this->params['where'], $data)->hasError()) {
            return $this;
        }

        $type = 's';

        foreach ($data as $name => $value) {

            if (!empty($this->params['where'][$name])) {

                if (is_array($value)) { // multiple value
                    $this->queryData['where'] .= sprintf('%s IN(', $name);

                    foreach ($value as $val) {
                        $this->result['values'][] = $val;
                        $this->result['types'] .= $type;
                        $this->queryData['where'] .= '?, ';
                    }

                    $this->queryData['where'] = mb_substr($this->queryData['where'], 0, -2) . ') AND ';
                } else {
                    $this->result['values'][] = $value;
                    $this->result['types'] .= $type;
                    $this->queryData['where'] .= (
                            (is_string($value) && mb_strlen($value) > 1) 
                            && (
                                $value[0] === '%' 
                                || $value[-1] === '%'
                            )
                        )
                        ? sprintf('%s LIKE ? AND ', $name)
                        : sprintf('%s = ? AND ', $name);
                }

            } elseif (is_int($name)) {
                // sql разметка
                if (
                    is_string($value)
                    // проверяем на допустимые символы
                    && in_array($value = mb_strtoupper($value), [
                        '(', ')', 'OR', 'AND'
                    ])
                ) {
                    if (
                        in_array($value, ['OR', ')'])
                        && mb_substr($this->queryData['where'], -5) === ' AND '
                    ) {
                        $this->queryData['where'] = mb_substr($this->queryData['where'], 0, -5);
                    }
                    
                    $this->queryData['where'] .= sprintf(' %s ', $value);

                    if (in_array($value, [')'])) {
                        $this->queryData['where'] .= ' AND ';
                    }
                
                // сравнение полей
                } elseif (is_array($value)) {
                    $countParams = count($value);

                    if ($countParams === 2) {
                        [$fieldName, $fieldValue] = $value;
                        $expression = '=';
                    } elseif ($countParams === 3) {
                        [$fieldName, $expression, $fieldValue] = $value;
                    }
                    
                    $checkData = [$fieldName => $fieldValue];

                    if ($this->validator->check($this->params['where'], $checkData)->hasError()) {
                        continue;
                    }

                    if (is_array($fieldValue)) {
                        $this->result['types'] .= str_repeat('s', count($fieldValue));
                        $this->result['values'] = array_merge($this->result['values'], $fieldValue);
                        $this->queryData['where'] .= sprintf(
                            '%s %s (%s) AND ',
                            $fieldName,
                            $expression,
                            str_repeat('?,', count($fieldValue) - 1) . '?'
                        );
                    } elseif (is_null($fieldValue)) {
                        $this->queryData['where'] .= sprintf(
                            '%s %s NULL AND ',
                            $fieldName,
                            $expression
                        );
                    } else {
                        $this->result['types'] .= 's';
                        $this->result['values'][] = $fieldValue;
                        $this->queryData['where'] .= sprintf(
                            '%s %s ? AND ',
                            $fieldName,
                            $expression
                        );
                    }
                }
            }
        }

        if (
            $this->queryData['where'] !== '' 
            && mb_substr($this->queryData['where'], -5) === ' AND '
        ) {
            $this->queryData['where'] = 'WHERE ' . mb_substr($this->queryData['where'], 0, -5);
        }

        return $this;
    }

    /**
     * @param array $data
     * 
     * @return self
     */
    protected function makeOrderBy(array $data): self
    {
        foreach ($data as $name => $value) {
            $this->validator->check([$name => 'regexp/^(asc|desc)$/i'], $data);

            if (!$this->validator->hasError()) {
                $this->queryData['orderBy'] .= sprintf('%s %s, ', $name, $value);
            }
        }

        if ($this->queryData['orderBy'] !== '') {
            $this->queryData['orderBy'] = 'ORDER BY ' . mb_substr($this->queryData['orderBy'], 0, -2);
        }

        return $this;
    }

    /**
     * @param array $data
     * 
     * @return self
     */
    protected function makeGroupBy(array $data): self
    {
        foreach ($data as $name => $value) {

            if (is_numeric($name)) {
                $this->queryData['groupBy'] .= sprintf('%s, ', $value);
            } else {
                $this->validator->check([$name => 'regexp/^(asc|desc)$/i'], $data);
                
                if (!$this->validator->hasError()) {
                    $this->queryData['groupBy'] .= sprintf('%s %s, ', $name, $value);
                }
            }
        }

        if ($this->queryData['groupBy'] !== '') {
            $this->queryData['groupBy'] = 'GROUP BY ' . mb_substr($this->queryData['groupBy'], 0, -2);
        }

        return $this;
    }

    /**
     * @param mixed $data
     * 
     * @return self
     */
    protected function makeLimit($data): self
    {
        if (!is_array($data)) {
            $data = [$data];
        }

        foreach ($data as $value) {
            $this->queryData['limit'] .= sprintf('%d, ', (int) $value);
        }

        $this->queryData['limit'] = 'LIMIT ' . mb_substr($this->queryData['limit'], 0, -2);

        return $this;
    }

    /**
     * Проверяет данные полей и отправляет запрос.
     * @param array $params
     * @param array $data
     * 
     * @return bool|object
     */
    public function prepareAndQuery(array $params, array $data): bool|object
    {
        $prepared = $this->prepareToSQL($params, $data);

        if (count($prepared['errors']) > 0) {
            return false;
        }

        $stmt = $this->DB->prepare($prepared['prepareSQL']);

        if ($stmt === false) {
            return false;
        }

        if (!$stmt->execute($prepared['values'])) {
            return false;
        }

        if ($params['type'] === 'select') {
            $stmt->setFetchMode(\PDO::FETCH_ASSOC);
            $result = $stmt;
        } else {
            $result = true;
        }

        return $result;
    }
}
