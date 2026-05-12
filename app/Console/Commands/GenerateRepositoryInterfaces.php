<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateRepositoryInterfaces extends Command
{
    protected $signature = 'make:repository-interfaces';
    protected $description = 'Génère les interfaces de repository (héritant de BaseRepositoryInterface) pour tous les modèles.';

    public function handle()
    {
        $modelsPath = app_path('Models');
        if (!File::exists($modelsPath)) {
            $this->error("Le dossier app/Models n'existe pas.");
            return 1;
        }

        $modelFiles = File::files($modelsPath);
        $generated = 0;
        $skipped = 0;

        foreach ($modelFiles as $file) {
            $modelName = $file->getFilenameWithoutExtension();
            // Ignorer les classes abstraites ou non-modèles
            if (in_array($modelName, ['Model', 'BaseModel']) || str_contains($modelName, 'Interface')) {
                continue;
            }

            $interfaceName = $modelName . 'RepositoryInterface';
            $interfacePath = app_path("Repositories/Interfaces/{$interfaceName}.php");

            if (File::exists($interfacePath)) {
                $this->line("⏩ Déjà existant : {$interfaceName}");
                $skipped++;
                continue;
            }

            // Créer le dossier si nécessaire
            $dir = dirname($interfacePath);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $stub = $this->getStub($interfaceName);
            File::put($interfacePath, $stub);
            $this->info("✅ Généré : {$interfaceName}");
            $generated++;
        }

        $this->newLine();
        $this->info("Terminé : {$generated} générée(s), {$skipped} existante(s) ignorée(s).");
        return 0;
    }

    protected function getStub(string $interfaceName): string
    {
        return <<<PHP
<?php

namespace App\Repositories\Interfaces;

/**
 * Interface {$interfaceName}
 *
 * Hérite de BaseRepositoryInterface pour bénéficier des méthodes CRUD communes.
 */
interface {$interfaceName} extends BaseRepositoryInterface
{
    // Vous pouvez ajouter ici des méthodes spécifiques au repository.
}

PHP;
    }
}
