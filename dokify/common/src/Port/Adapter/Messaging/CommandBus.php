<?php

namespace Dokify\Port\Adapter\Messaging;

interface CommandBus
{
    public function handle($command);
}