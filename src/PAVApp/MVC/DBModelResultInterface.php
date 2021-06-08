<?php
namespace PAVApp\MVC;

/**
 * Интерфейс для работы с результатом запроса объекта DBModel
 */
interface DBModelResultInterface
{
    public function __construct(object $result);

    public function result(): object;

    public function getList(): array;

    public function exists(): bool;

    public function count(): int;

    public function success(): bool;
}