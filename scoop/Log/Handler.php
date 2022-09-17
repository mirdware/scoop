<?php
namespace Scoop\Log;

interface Handler
{
    public const DEFAULT_FORMAT = '%timestamp% [%level%]: %message%';
    public function handle($log);
}
