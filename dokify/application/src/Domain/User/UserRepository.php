<?php

namespace Dokify\Domain\User;

interface UserRepository
{
    public function ofId(int $id): User;
}
