<?php

namespace Dokify\Router\Domain;

interface RepositoryInterface
{
    public function store(array $dataRoute);

    public function find(string $pathinfo);
}
