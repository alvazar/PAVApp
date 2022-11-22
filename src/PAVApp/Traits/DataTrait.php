<?php
namespace PAVApp\Traits;

trait DataTrait
{
    protected $data = [];

    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
    
    public function getData(): array
    {
        return $this->data;
    }
}
