<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeEnum extends Command
{
    protected $signature = 'make:enum {name : The name of the enum}
                            {--s|string : Create a string-backed enum}
                            {--i|int : Create an integer-backed enum}';
    protected $description = 'Creates a new enum class';

    public function handle()
    {
        $name = $this->argument('name');

        // Determine the backed type based on the flags
        if ($this->option('string')) {
            $backed = 'string';
        } elseif ($this->option('int')) {
            $backed = 'int';
        } else {
            $backed = null;
        }

        $className = basename(str_replace('\\', '/', $name));
        $namespace = 'App\\Enums';
        $subDir = dirname(str_replace('\\', '/', $name));
        if ($subDir !== '.') {
            $namespace .= '\\' . str_replace('/', '\\', $subDir);
        }

        $path = app_path("Enums/{$subDir}/{$className}.php");
        $path = str_replace('\\', '/', $path);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $stub = $this->getStub($backed);
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub
        );

        if (file_exists($path)) {
            $this->error("Enum {$name} already exists!");
            return 1;
        }

        file_put_contents($path, $content);
        $this->info("Enum created: {$path}");
    }

    protected function getStub($backed = null)
    {
        if ($backed === 'int') {
            return <<<STUB
<?php

namespace {{ namespace }};

enum {{ class }}: int
{
    // Add your cases here

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
STUB;
        }

        if ($backed === 'string') {
            return <<<STUB
<?php

namespace {{ namespace }};

enum {{ class }}: string
{
    // Add your cases here

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
STUB;
        }

        // Pure enum (no backing)
        return <<<STUB
<?php

namespace {{ namespace }};

enum {{ class }}
{
    //
}
STUB;
    }
}
