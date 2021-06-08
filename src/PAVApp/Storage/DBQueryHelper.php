<?php
namespace PAVApp\Storage;

/**
 * Класс-хелпер для работы с SQL запросами.
 */
class DBQueryHelper
{
    /**
     * @var PAVApp\Core\Validator
     */
    protected $Validator;

    public function __construct()
    {
        $this->Validator = new \PAVApp\Core\Validator();
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
        $result = [
            'prepareSQL' => '',
            'values' => [],
            'types' => '',
            'errors' => []
        ];
        
        // Шаблоны sql запросов.
        $queryTempl = [
            'select' => 'SELECT {selectNames} FROM {table} {join} {where} {groupBy} {orderBy} {limit}',
            'insert' => 'INSERT INTO {table} ({insertNames}) VALUES ({values})',
            'update' => 'UPDATE {table} SET {updates} {where} {limit}',
        ];
        // Части запроса.
        $queryData = [
            'table' => $params['table'] ?? '',
            'join' => '',
            'selectNames' => !empty($data['select']) ? implode(',', $data['select']) : '*',
            'insertNames' => '',
            'updates' => '',
            'values' => '',
            'where' => '',
            'groupBy' => '',
            'orderBy' => '',
            'limit' => ''
        ];

        //
        $fnAddPrefix = function (array $data, string $prefix): array {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$prefix.$key] = $value;
            }
            return $result;
        };

        if (!empty($data['as'])) {
            $queryData['table'] .= sprintf(' %s', $data['as']);
            $params['where'] = $fnAddPrefix($params['where'], $data['as'].'.');
        }

        // joins
        if (!empty($data['join'])) {
            foreach ($data['join'] as $modelName => $joinData) {
                
                $ModelObj = new $modelName;
                $modelTable = $ModelObj->getTable();
                $modelWhereFields = $ModelObj->getWhereFields();

                $queryData['join'] .= sprintf("\n LEFT JOIN %s", $modelTable);

                if (!empty($joinData['as'])) {
                    $queryData['join'] .= ' '.$joinData['as'];
                    $modelWhereFields = $fnAddPrefix($modelWhereFields, $joinData['as'].'.');
                    $params['where'] = array_merge($params['where'], $modelWhereFields);
                }

                if (!empty($joinData['on'])) {
                    $queryData['join'] .= ' ON ';
                    foreach ($joinData['on'] as $joinOnData) {
                        $queryData['join'] .= sprintf(' %s = %s ', $joinOnData[0], $joinOnData[1]);
                    }
                }

                $queryData['join'] .= " \n";
            }
        }

        // Валидатор
        $Validator = $this->Validator;

        // Поля элемента для сохранения в БД
        if (!empty($params['fields'])) {
            $data['fields'] = $data['fields'] ?? [];
            // Проверка полей
            if (!$Validator->check($params['fields'], $data['fields'])->hasError()) {
                $type = 's';
                foreach ($data['fields'] as $name => $value) {
                    if (!empty($params['fields'][$name])) {
                        $result['values'][] = is_array($value) ? json_encode($value) : $value;
                        $result['types'] .= $type;
                        $queryData['insertNames'] .= sprintf('%s, ', $name);
                        $queryData['updates'] .= sprintf('%s = ?, ', $name);
                        $queryData['values'] .= '?, ';
                    }
                }
            }

            if ($queryData['insertNames'] !== '') {
                $queryData['insertNames'] = mb_substr($queryData['insertNames'], 0, -2);
                $queryData['updates'] = mb_substr($queryData['updates'], 0, -2);
                $queryData['values'] = mb_substr($queryData['values'], 0, -2);
            }
        }

        if ($Validator->hasError()) {
            $result['errors'] = $Validator->getErrors();
            return $result;
        }

        // Поля для фильтрации списка
        if (!empty($params['where'])) {
            $data['where'] = $data['where'] ?? [];
            if (!$Validator->check($params['where'], $data['where'])->hasError()) {
                $type = 's';
                foreach ($data['where'] as $name => $value) {
                    if (!empty($params['where'][$name])) {
                        if (is_array($value)) { // multiple value
                            $queryData['where'] .= sprintf('%s IN(', $name);
                            foreach ($value as $val) {
                                $result['values'][] = $val;
                                $result['types'] .= $type;
                                $queryData['where'] .= '?, ';
                            }
                            $queryData['where'] = mb_substr($queryData['where'], 0, -2).') AND ';
                        } else {
                            $result['values'][] = $value;
                            $result['types'] .= $type;
                            $queryData['where'] .= (
                                    is_string($value) && (
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
                            if (in_array($value, ['OR', ')'])) {
                                if (mb_substr($queryData['where'], -5) === ' AND ') {
                                    $queryData['where'] = mb_substr($queryData['where'], 0, -5);
                                }
                            }
                            $queryData['where'] .= sprintf(' %s ', $value);
                            if (in_array($value, [')'])) {
                                $queryData['where'] .= ' AND ';
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
                            if (!$Validator->check($params['where'], $checkData)->hasError()) {
                                if (is_array($fieldValue)) {
                                    $result['types'] .= str_repeat('s', count($fieldValue));
                                    $result['values'] = array_merge($result['values'], $fieldValue);
                                    $queryData['where'] .= sprintf(
                                        '%s %s (%s) AND ',
                                        $fieldName,
                                        $expression,
                                        str_repeat('?,', count($fieldValue) - 1).'?'
                                    );
                                } else {
                                    $result['types'] .= 's';
                                    $result['values'][] = $fieldValue;
                                    $queryData['where'] .= sprintf(
                                        '%s %s ? AND ',
                                        $fieldName,
                                        $expression
                                    );
                                }
                            }
                        }
                    }
                }
            }

            if (
                $queryData['where'] !== '' 
                && mb_substr($queryData['where'], -5) === ' AND '
            ) {
                $queryData['where'] = 'WHERE '.mb_substr($queryData['where'], 0, -5);
            }
        }

        if ($Validator->hasError()) {
            $result['errors'] = $Validator->getErrors();
            return $result;
        }

        // Сортировка
        if (!empty($data['orderBy'])) {
            foreach ($data['orderBy'] as $name => $value) {
                $Validator->check([$name => 'regexp/^(asc|desc)$/i'], $data['orderBy']);
                if (!$Validator->hasError()) {
                    $queryData['orderBy'] .= sprintf('%s %s, ', $name, $value);
                }
            }
            if ($queryData['orderBy'] !== '') {
                $queryData['orderBy'] = 'ORDER BY '.mb_substr($queryData['orderBy'], 0, -2);
            }
        }

        if ($Validator->hasError()) {
            $result['errors'] = $Validator->getErrors();
            return $result;
        }

        // Группировка по полям
        if (!empty($data['groupBy'])) {
            foreach ($data['groupBy'] as $name => $value) {
                if (is_numeric($name)) {
                    $queryData['groupBy'] .= sprintf('%s, ', $value);
                } else {
                    $Validator->check([$name => 'regexp/^(asc|desc)$/i'], $data['groupBy']);
                    if (!$Validator->hasError()) {
                        $queryData['groupBy'] .= sprintf('%s %s, ', $name, $value);
                    }
                }
            }
            if ($queryData['groupBy'] !== '') {
                $queryData['groupBy'] = 'GROUP BY '.mb_substr($queryData['groupBy'], 0, -2);
            }
        }

        if ($Validator->hasError()) {
            $result['errors'] = $Validator->getErrors();
            return $result;
        }

        // Лимит, либо интервал выборки
        if (!empty($data['limit'])) {
            if (!is_array($data['limit'])) {
                $data['limit'] = [$data['limit']];
            }
            foreach ($data['limit'] as $value) {
                $queryData['limit'] .= sprintf('%d, ', (int) $value);
            }
            $queryData['limit'] = 'LIMIT '.mb_substr($queryData['limit'], 0, -2);
        }

        // Формирование sql запроса
        if (!empty($params['type']) && isset($queryTempl[$params['type']])) {
            $result['prepareSQL'] = $queryTempl[$params['type']];
            foreach ($queryData as $key => $value) {
                $result['prepareSQL'] = str_replace('{'.$key.'}', $value, $result['prepareSQL']);
            }
            $result['prepareSQL'] = trim($result['prepareSQL']);
        }

        if ($Validator->hasError()) {
            $result['errors'] = $Validator->getErrors();
        }

        return $result;
    }

    /**
     * Проверяет данные полей и отправляет запрос.
     * @param array $params
     * @param array $data
     * 
     * @return bool|object
     */
    public function prepareAndQuery(array $params, array $data)
    {
        $prepared = $this->prepareToSQL($params, $data);
        if (count($prepared['errors']) > 0) {
            return false;
        }

        $result = false;
        $stmt = $this->DB->prepare($prepared['prepareSQL']);
        if ($stmt !== false) {
            if ($stmt->execute($prepared['values'])) {
                if ($params['type'] === 'select') {
                    $stmt->setFetchMode(\PDO::FETCH_ASSOC);
                    $result = $stmt;
                } else {
                    $result = true;
                }
            }
        }

        return $result;
    }
}