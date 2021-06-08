<?php
namespace PAVApp\MVC;

/**
 * Интерфейс моделей хранящихся в БД.
 */
interface DBModelInterface
{

    public function save(array $params = []): bool;
    public function getList(array $params = []): array;
    public function getByID(int $ID): array;
    public function delete(int $ID): bool;
    //public function getError(): string;
    public function init(): void;
    public function querySelect(array $params): DBModelResultInterface;
    public function getTable(): string;
    public function getWhereFields(): array;
    public function getSaveFields(): array;

}