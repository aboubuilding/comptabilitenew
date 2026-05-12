<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateRepositories extends Command
{
    protected $signature = 'make:repositories {--model= : Générer pour un modèle spécifique}
                                              {--force : Écraser les fichiers existants}';
    protected $description = 'Génère les classes Repository concrètes (héritant de BaseRepository) pour tous les modèles ou un seul.';

    public function handle()
    {
        if ($modelName = $this->option('model')) {
            $this->generateForModel($modelName);
        } else {
            $this->generateForAllModels();
        }
    }

    protected function generateForAllModels(): void
    {
        $modelsPath = app_path('Models');
        if (!File::exists($modelsPath)) {
            $this->error("Le dossier app/Models n'existe pas.");
            return;
        }

        $modelFiles = File::files($modelsPath);
        $generated = 0;
        $skipped = 0;

        foreach ($modelFiles as $file) {
            $modelName = $file->getFilenameWithoutExtension();
            // Ignorer les classes de base ou non-modèles
            if (in_array($modelName, ['Model', 'BaseModel']) || str_contains($modelName, 'Interface')) {
                continue;
            }

            if ($this->createRepository($modelName)) {
                $generated++;
            } else {
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("✅ Générés : {$generated}");
        $this->info("⏩ Ignorés (existants) : {$skipped}");
    }

    protected function generateForModel(string $modelName): void
    {
        if ($this->createRepository($modelName)) {
            $this->info("✅ Repository généré pour {$modelName}");
        } else {
            $this->warn("⏩ Repository déjà existant ou modèle introuvable pour {$modelName}");
        }
    }

    protected function createRepository(string $modelName): bool
    {
        // Vérifier que le modèle existe
        $modelClass = "App\\Models\\{$modelName}";
        if (!class_exists($modelClass)) {
            return false;
        }

        $repositoryName = $modelName . 'Repository';
        $repositoryPath = app_path("Repositories/Eloquent/{$repositoryName}.php");

        // Ne pas écraser si option --force absente
        if (File::exists($repositoryPath) && !$this->option('force')) {
            return false;
        }

        // Créer le dossier si nécessaire
        $directory = dirname($repositoryPath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $stub = $this->getStub($modelName, $repositoryName);
        File::put($repositoryPath, $stub);

        return true;
    }

    protected function getStub(string $modelName, string $repositoryName): string
    {
        return <<<PHP
<?php

namespace App\Repositories\Eloquent;

use App\Models\\{$modelName};

class {$repositoryName} extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new {$modelName}());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}

PHP;
    }
}
