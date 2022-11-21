<?php
namespace PAVApp\Traits;

trait ErrorTrait
{
    protected string $error = '';

    public function setError(string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getError(): string
    {
        return $this->error ?? '';
    }

    public function hasError(): bool
    {
        return $this->error !== '';
    }
}
