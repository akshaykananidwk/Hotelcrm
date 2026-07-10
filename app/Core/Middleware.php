<?php
namespace App\Core;

/** Base middleware contract. handle() returns false to halt the request. */
abstract class Middleware
{
    abstract public function handle(): bool;
}
