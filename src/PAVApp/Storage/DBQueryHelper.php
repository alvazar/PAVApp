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
            'select' => 'SELECT {selectNames} FROM {table} {where} {groupBy} {orderBy} {limit}',
            'insert' => 'INSERT INTO {table} ({insertNames}) VALUES ({values})',
            'update' => 'UPDATE {table} SET {updates} {where} {limit}',
        ];
        // Части запроса.
        $queryData = [
            'table' => $params['table'] ?? '',
            'selectNames' => !empty($params['select']) ? implode(',', $params['select']) : '*',
            'insertNames' => '',
            'updates' => '',
            'values' => '',
            'where' => '',
            'groupBy' => '',
            'orderBy' => '',
            'limit' => ''
        ];

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
                    }
                }
            }

            if ($queryData['where'] !== '') {
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