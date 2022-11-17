<?php
namespace PAVApp\MVC;

/**
 * Интерфейс моделей хранящихся в БД.
 */
interface DBModelInterface
{

    public function save(array $params = []): bool;
    public function getList(array $params = []): array;
    public function getById(int $id): array;
    public function delete(int $id): bool;
    //public function getError(): string;
    public function init(): void;
    public function querySelect(array $params): DBModelResultInterface;
    public function getTable(): string;
    public function getWhereFields(): array;
    public function getSaveFields(): array;
    public function getInsertId(): int;

}
