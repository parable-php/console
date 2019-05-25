<?php declare(strict_types=1);

namespace Parable\Console\Tests\Classes;

use Parable\Console\Command;

class TestCommand extends Command
{
    protected $name = 'test-command';
    protected $description = 'This command does nothing';
}
