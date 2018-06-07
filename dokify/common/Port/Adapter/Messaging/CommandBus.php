<?php

namespace Dokify\Common\Port\Adapter\Messaging;

interface CommandBus
{
    public function handle($command);
}