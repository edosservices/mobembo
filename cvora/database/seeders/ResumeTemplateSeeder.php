<?php

namespace Database\Seeders;

use App\Models\ResumeTemplate;
use Illuminate\Database\Seeder;

class ResumeTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['moderne', 'Moderne', 'Colonne latérale et contraste marqué pour un CV lisible dès le premier écran.', 'Tous profils', false],
            ['classique', 'Classique', 'Une colonne, des filets et une hiérarchie traditionnelle, adaptée aux dossiers formels.', 'Tous profils', false],
            ['minimaliste', 'Minimaliste', 'Beaucoup d’air, peu d’ornement, le texte porte seul la candidature.', 'Profils sobres', true],
            ['etudiant', 'Étudiant', 'La formation et les projets passent avant l’expérience.', 'Étudiants et jeunes diplômés', true],
            ['experimente', 'Professionnel expérimenté', 'Le parcours professionnel occupe le devant, sous un bandeau d’identité.', 'Cadres et profils confirmés', true],
            ['technique', 'Technique et informatique', 'Les compétences et les projets sont placés avant le récit des postes.', 'Profils techniques', true],
            ['administratif', 'Administratif', 'En-tête structuré et sections numérotées, pour les métiers de bureau et du public.', 'Administratif', true],
            ['international', 'International', 'Libellés courts, dates alignées, ordre stable pour une lecture hors du pays.', 'Candidatures internationales', true],
        ];

        foreach ($templates as $index => [$slug, $name, $description, $audience, $premium]) {
            ResumeTemplate::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'audience' => $audience,
                'is_premium' => $premium,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
