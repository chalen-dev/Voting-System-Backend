<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeScaffold extends Command
{
    protected $signature = 'make:scaffold {name : The name of the model (e.g., User, Admin/User)}
                                          {--r|resource : Generate a resource controller}
                                          {--s|seed : Generate a seeder}
                                          {--no-migration : Do not create a migration file}
                                          {--force : Overwrite existing files}';
    protected $description = 'Create a model, controller, migration, and optionally a seeder';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $resource = $this->option('resource');
        $seed = $this->option('seed');
        $noMigration = $this->option('no-migration');
        $force = $this->option('force');

        // Parse the model name
        $baseName = class_basename($name);
        $path = str_replace('\\', '/', $name);
        $subDir = dirname($path);
        if ($subDir === '.') {
            $subDir = '';
        }

        // Model namespace and path
        $modelNamespace = 'App\\Models' . ($subDir ? '\\' . str_replace('/', '\\', $subDir) : '');
        $modelPath = app_path('Models/' . ($subDir ? $subDir . '/' : '') . $baseName . '.php');

        // Controller namespace and path
        $controllerNamespace = 'App\\Http\\Controllers' . ($subDir ? '\\' . str_replace('/', '\\', $subDir) : '');
        $controllerPath = app_path('Http/Controllers/' . ($subDir ? $subDir . '/' : '') . $baseName . 'Controller.php');

        // Seeder path (always in database/seeders, no subdirectory)
        $seederClass = $baseName . 'Seeder';
        $seederPath = database_path('seeders/' . $seederClass . '.php');

        // Migration path
        $tableName = Str::plural(Str::snake($baseName));
        $migrationFileName = date('Y_m_d_His') . '_create_' . $tableName . '_table.php';
        $migrationPath = database_path('migrations/' . $migrationFileName);

        // Ensure directories exist
        $this->makeDirectory(dirname($modelPath));
        $this->makeDirectory(dirname($controllerPath));
        $this->makeDirectory(dirname($seederPath));
        $this->makeDirectory(dirname($migrationPath));

        // Generate model
        if ($this->createFile($modelPath, $this->getModelStub($modelNamespace, $baseName), $force)) {
            $this->info("Model created: {$modelPath}");
        } else {
            $this->error("Model already exists: {$modelPath}");
            return 1;
        }

        // Generate controller
        if ($this->createFile($controllerPath, $this->getControllerStub($controllerNamespace, $baseName, $resource, $modelNamespace), $force)) {
            $this->info("Controller created: {$controllerPath}");
        } else {
            $this->error("Controller already exists: {$controllerPath}");
            return 1;
        }

        // Generate migration (if not disabled)
        if (!$noMigration) {
            // Migration doesn't need existence check because timestamps are unique
            $this->createFile($migrationPath, $this->getMigrationStub($tableName, $baseName), true);
            $this->info("Migration created: {$migrationPath}");
        } else {
            $this->info("Skipped migration creation.");
        }

        // Generate seeder (if requested)
        if ($seed) {
            if ($this->createFile($seederPath, $this->getSeederStub($seederClass), $force)) {
                $this->info("Seeder created: {$seederPath}");
            } else {
                $this->error("Seeder already exists: {$seederPath}");
                return 1;
            }
        }

        return 0;
    }

    protected function createFile(string $path, string $content, bool $force): bool
    {
        if (!$force && $this->files->exists($path)) {
            return false;
        }
        $this->files->put($path, $content);
        return true;
    }

    protected function makeDirectory(string $path): void
    {
        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    protected function getModelStub(string $namespace, string $class): string
    {
        return <<<STUB
<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Model;

class {$class} extends Model
{
    protected \$fillable = [
        // Add your fillable attributes here
    ];

    protected function casts(): array
    {
        return [
            // Add your casts here
        ];
    }
}
STUB;
    }

    protected function getControllerStub(string $namespace, string $class, bool $resource, string $modelNamespace): string
    {
        $modelClass = $class;
        $modelVariable = Str::camel($class);

        $baseControllerImport = "use App\\Http\\Controllers\\Controller;";

        if ($resource) {
            return <<<STUB
<?php

namespace {$namespace};

{$baseControllerImport}
use {$modelNamespace}\\{$modelClass};
use Illuminate\Http\Request;

class {$class}Controller extends Controller
{
    public function index()
    {
        // Return a view or JSON response
        return {$modelClass}::all();
    }

    public function create()
    {
        //
    }

    public function store(Request \$request)
    {
        // Validate and store the new resource
        \$data = \$request->validate([
            // validation rules
        ]);

        {$modelClass}::create(\$data);

        return redirect()->route('{$modelVariable}s.index');
    }

    public function show({$modelClass} \${$modelVariable})
    {
        return \${$modelVariable};
    }

    public function edit({$modelClass} \${$modelVariable})
    {
        return view('{$modelVariable}s.edit', compact('{$modelVariable}'));
    }

    public function update(Request \$request, {$modelClass} \${$modelVariable})
    {
        // Validate and update
        \$data = \$request->validate([
            // validation rules
        ]);

        \${$modelVariable}->update(\$data);

        return redirect()->route('{$modelVariable}s.index');
    }

    public function destroy({$modelClass} \${$modelVariable})
    {
        \${$modelVariable}->delete();

        return redirect()->route('{$modelVariable}s.index');
    }
}
STUB;
        }

        // Simple controller stub
        return <<<STUB
<?php

namespace {$namespace};

{$baseControllerImport}
use {$modelNamespace}\\{$modelClass};
use Illuminate\Http\Request;

class {$class}Controller extends Controller
{
    public function index()
    {
        return {$modelClass}::all();
    }

    public function store(Request \$request)
    {
        // Validate and store
        \$data = \$request->validate([
            // validation rules
        ]);

        return {$modelClass}::create(\$data);
    }

    public function show({$modelClass} \${$modelVariable})
    {
        return \${$modelVariable};
    }

    public function update(Request \$request, {$modelClass} \${$modelVariable})
    {
        \$data = \$request->validate([
            // validation rules
        ]);

        \${$modelVariable}->update(\$data);

        return \${$modelVariable};
    }

    public function destroy({$modelClass} \${$modelVariable})
    {
        \${$modelVariable}->delete();

        return response()->noContent();
    }
}
STUB;
    }

    protected function getMigrationStub(string $tableName, string $modelClass): string
    {
        return <<<STUB
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
            // Add your columns here
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
STUB;
    }

    protected function getSeederStub(string $class): string
    {
        return <<<STUB
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class {$class} extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add your seed data here
    }
}
STUB;
    }
}


/*
 *
 *
 *
# Create a User model, controller, migration, and optionally seeder
php artisan make:scaffold User
add -s for seeder

# With resource controller and seeder (short options combined)
php artisan make:scaffold User -rs

# Skip migration creation
php artisan make:scaffold User --no-migration

# Force overwrite existing files
php artisan make:scaffold User --force

 *
 */
