<?php

namespace Tests;

use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

#[WithMigration]
class BaseTestCase extends TestCase
{
    use RegistersPackage;
}
