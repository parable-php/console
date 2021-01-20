<?php declare(strict_types=1);

namespace Parable\Console\Tests\Classes;

use Parable\Console\Command;

class TestCommand extends Command
{
    public function __construct() {
        $this->setName('test-command');
        $this->setDescription('This is a test command.');
    }
}
