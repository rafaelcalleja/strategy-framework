<?php

namespace Dokify\Domain\Legacy\User;

interface UserRepository
{
    public function ofId(int $id): \usuario;
}
