<?php

namespace Koffielyder\LaravelAiModel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Koffielyder\LaravelAiModel\Models\AiModel;

class DiscoverAiModelsCommand extends Command
{
    protected $signature = 'ai-model:discover 
                            {--path=app/Models : Path to scan for AiModel classes}
                            {--force : Overwrite existing models list}';

    protected $description = 'Scan and discover AiModel classes and update the models list in the ai-model config file.';

    public function handle()
    {
        $path = base_path($this->option('path'));


        if (!is_dir($path)) {
            $this->error("Path [$path] does not exist.");
            return 1;
        }

        $classes = $this->discoverAiModels($path);
        $this->warn(print_r($classes, 1));

        if (empty($classes)) {
            $this->warn('No AiModel classes found.');
            return 0;
        }

        $configPath = config_path('ai-model.php');

        if (!File::exists($configPath)) {
            $this->error('Config file [ai-model.php] not found. Please publish the config first.');
            return 1;
        }

        $currentConfig = File::getRequire($configPath);

        if (!is_array($currentConfig)) {
            $this->error('Invalid ai-model.php format.');
            return 1;
        }

        if (isset($currentConfig['models']) && !$this->option('force')) {
            $this->warn('Models list already exists. Use --force to overwrite.');
            return 1;
        }

        $currentConfig['models'] = $classes;


        $content = "<?php\n\nreturn [\n";

        foreach ($currentConfig as $key => $value) {
            if ($key === 'models' && is_array($value)) {
                $content .= "    'models' => [\n";
                foreach ($value as $modelClass) {
                    $content .= "        {$modelClass}::class,\n";
                }
                $content .= "    ],\n";
            } else {
                $exportedValue = var_export($value, true);
                $content .= "    '{$key}' => {$exportedValue},\n";
            }
        }

        $content .= "];\n";

        File::put($configPath, $content);

        $this->info('ai-model.php models list updated with ' . count($classes) . ' model(s).');

        return 0;
    }

    protected function discoverAiModels(string $path): array
    {
        $files = File::allFiles($path);

        $found = [];

        foreach ($files as $file) {
            require_once $file->getRealPath(); // 💥 Force load every model

            $relativePath = $file->getRelativePathname();
            $class = $this->classFromFile($relativePath);

            if (!$class || !class_exists($class)) {
                continue;
            }

            if (is_subclass_of($class, AiModel::class) && !(new ReflectionClass($class))->isAbstract()) {
                $found[] = '\\' . $class;
            }
        }

        return $found;
    }

    protected function classFromFile(string $relativePath): ?string
    {
        $class = str_replace(['/', '.php'], ['\\', ''], $relativePath);

        // Insert 'Models\' manually because we are scanning inside app/Models
        return app()->getNamespace() . 'Models\\' . $class;
    }
}
