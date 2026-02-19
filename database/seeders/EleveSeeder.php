<?php

namespace Database\Seeders;

use App\Models\AffectationClasse;
use App\Models\AnneeScolaire;
use App\Models\CampagneInscription;
use App\Models\Classe;
use App\Models\Colline;
use App\Models\Eleve;
use App\Models\Inscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EleveSeeder extends Seeder
{
    /**
     * Prénoms burundais courants
     */
    private array $prenoms = [
        'M' => [
            'Jean', 'Pierre', 'Emmanuel', 'Dieudonné', 'Innocent', 'Pacifique',
            'Fabrice', 'Eric', 'Patrick', 'Olivier', 'Alain', 'Christian',
            'Thierry', 'Janvier', 'Félicien', 'Gervais', 'Sylvestre', 'Léonidas',
            'Méthode', 'Désiré', 'Fidèle', 'Célestin', 'Boniface', 'Venant',
            'Arcade', 'Rénovate', 'Tharcisse', 'Audace', 'Espérance', 'Donatien',
        ],
        'F' => [
            'Marie', 'Jeanne', 'Claudine', 'Béatrice', 'Espérance', 'Pascaline',
            'Francine', 'Chantal', 'Yvette', 'Sandrine', 'Diane', 'Alice',
            'Joséphine', 'Généreuse', 'Consolate', 'Jeanine', 'Gorette', 'Médiatrice',
            'Nadia', 'Odette', 'Pélagie', 'Révocate', 'Salomé', 'Vestine',
            'Ange', 'Aline', 'Angélique', 'Annonciata', 'Clarisse', 'Daphrose',
        ],
    ];

    /**
     * Noms de famille burundais courants
     */
    private array $noms = [
        'Ndayisaba', 'Niyonzima', 'Ndikumana', 'Habimana', 'Niyongabo',
        'Ndayizeye', 'Niyomwungere', 'Hakizimana', 'Ntakirutimana', 'Nkurunziza',
        'Bigirimana', 'Nsengiyumva', 'Niyibizi', 'Nduwimana', 'Manirambona',
        'Ntahomvukiye', 'Havyarimana', 'Sindayigaya', 'Bucumi', 'Iradukunda',
        'Irankunda', 'Nshimirimana', 'Ngendakumana', 'Bizimana', 'Butoyi',
        'Gasana', 'Mugisha', 'Nkeshimana', 'Nsabimana', 'Nahimana',
        'Nzeyimana', 'Nyandwi', 'Ndabereye', 'Niyonkuru', 'Nimubona',
        'Nizeyimana', 'Niyoyitungira', 'Niyukuri', 'Ntirandekura', 'Barutwanayo',
    ];

    /**
     * Lieux de naissance
     */
    private array $lieuxNaissance = [
        'Bujumbura', 'Gitega', 'Ngozi', 'Rumonge', 'Bururi',
        'Makamba', 'Rutana', 'Ruyigi', 'Cankuzo', 'Bubanza',
        'Cibitoke', 'Kayanza', 'Kirundo', 'Muramvya', 'Mwaro',
        'Karuzi', 'Muyinga', 'Hôpital Prince Régent Charles',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anneeScolaire = AnneeScolaire::where('est_active', true)->first();

        if (! $anneeScolaire) {
            $this->command->warn('Aucune année scolaire active trouvée.');

            return;
        }

        $classes = Classe::with(['ecole', 'niveau'])
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->where('statut', 'ACTIVE')
            ->get();

        if ($classes->isEmpty()) {
            $this->command->warn('Aucune classe trouvée. Exécutez ClasseSeeder d\'abord.');

            return;
        }

        $collines = Colline::pluck('id')->toArray();
        $elevesCount = 0;
        $inscriptionsCount = 0;

        // Créer une campagne d'inscription par école si elle n'existe pas
        $this->createCampagnes($anneeScolaire, $classes);

        foreach ($classes as $classe) {
            // Vérifier que la classe a une école
            if (! $classe->school_id) {
                continue;
            }

            // Nombre d'élèves par classe (60-80% de la capacité)
            $capacite = $classe->capacite ?? 40;
            $nombreEleves = (int) ($capacite * (rand(60, 80) / 100));

            for ($i = 0; $i < $nombreEleves; $i++) {
                $sexe = rand(0, 1) ? 'M' : 'F';
                $age = $this->getAgeForNiveau($classe->niveau->code ?? '1P');

                $eleve = $this->createEleve($sexe, $age, $collines);
                $elevesCount++;

                // Créer l'inscription
                $inscription = $this->createInscription($eleve, $classe, $anneeScolaire);
                if ($inscription) {
                    $inscriptionsCount++;
                }
            }
        }

        $this->command->info("$elevesCount élèves créés avec succès!");
        $this->command->info("$inscriptionsCount inscriptions créées avec succès!");
    }

    /**
     * Crée les campagnes d'inscription pour chaque école.
     */
    private function createCampagnes(AnneeScolaire $anneeScolaire, $classes): void
    {
        $ecoleIds = $classes->pluck('school_id')->unique()->filter();

        foreach ($ecoleIds as $ecoleId) {
            CampagneInscription::firstOrCreate(
                [
                    'annee_scolaire_id' => $anneeScolaire->id,
                    'school_id' => $ecoleId,
                    'type' => 'nouvelle',
                ],
                [
                    'date_ouverture' => $anneeScolaire->date_debut->subMonths(2),
                    'date_cloture' => $anneeScolaire->date_debut->addMonths(1),
                    'statut' => 'ouverte',
                    'quota_max' => 500,
                    'created_by' => 1,
                ]
            );

            CampagneInscription::firstOrCreate(
                [
                    'annee_scolaire_id' => $anneeScolaire->id,
                    'school_id' => $ecoleId,
                    'type' => 'reinscription',
                ],
                [
                    'date_ouverture' => $anneeScolaire->date_debut->subMonths(2),
                    'date_cloture' => $anneeScolaire->date_debut->addMonths(1),
                    'statut' => 'ouverte',
                    'quota_max' => 800,
                    'created_by' => 1,
                ]
            );
        }
    }

    /**
     * Crée un élève avec des données réalistes.
     */
    private function createEleve(string $sexe, int $age, array $collines): Eleve
    {
        $prenom = $this->prenoms[$sexe][array_rand($this->prenoms[$sexe])];
        $nom = $this->noms[array_rand($this->noms)];

        $dateNaissance = Carbon::now()
            ->subYears($age)
            ->subDays(rand(0, 365));

        return Eleve::create([
            'matricule' => $this->generateMatricule(),
            'nom' => $nom,
            'prenom' => $prenom,
            'sexe' => $sexe,
            'date_naissance' => $dateNaissance,
            'lieu_naissance' => $this->lieuxNaissance[array_rand($this->lieuxNaissance)],
            'nationalite' => 'Burundaise',
            'colline_origine_id' => ! empty($collines) ? $collines[array_rand($collines)] : null,
            'adresse' => 'Quartier '.['Rohero', 'Kinama', 'Kamenge', 'Musaga', 'Bwiza', 'Ngagara', 'Cibitoke'][rand(0, 6)],
            'nom_pere' => $this->noms[array_rand($this->noms)].' '.$this->prenoms['M'][array_rand($this->prenoms['M'])],
            'nom_mere' => $this->noms[array_rand($this->noms)].' '.$this->prenoms['F'][array_rand($this->prenoms['F'])],
            'contact_tuteur' => '+257'.rand(61, 79).rand(100, 999).rand(100, 999),
            'nom_tuteur' => rand(0, 3) === 0 ? $this->noms[array_rand($this->noms)].' '.$this->prenoms[rand(0, 1) ? 'M' : 'F'][array_rand($this->prenoms['M'])] : null,
            'est_orphelin' => rand(0, 20) === 0, // 5% orphelins
            'a_handicap' => rand(0, 50) === 0, // 2% handicap
            'type_handicap' => rand(0, 50) === 0 ? ['Visuel', 'Auditif', 'Moteur', 'Mental'][rand(0, 3)] : null,
            'statut_global' => 'actif',
        ]);
    }

    /**
     * Crée une inscription pour un élève.
     */
    private function createInscription(Eleve $eleve, Classe $classe, AnneeScolaire $anneeScolaire): ?Inscription
    {
        $campagne = CampagneInscription::where('annee_scolaire_id', $anneeScolaire->id)
            ->where('school_id', $classe->school_id)
            ->first();

        if (! $campagne) {
            return null;
        }

        $inscription = Inscription::create([
            'numero_inscription' => $this->generateNumeroInscription(),
            'eleve_id' => $eleve->id,
            'school_id' => $classe->school_id,
            'annee_scolaire_id' => $anneeScolaire->id,
            'campagne_id' => $campagne->id,
            'niveau_demande_id' => $classe->niveau_id,
            'date_inscription' => $anneeScolaire->date_debut->subDays(rand(30, 60)),
            'type_inscription' => rand(0, 3) === 0 ? 'nouvelle' : 'reinscription',
            'statut' => 'valide',
            'est_redoublant' => rand(0, 10) === 0, // 10% redoublants
            'observations' => null,
            'date_soumission' => $anneeScolaire->date_debut->subDays(rand(20, 50)),
            'date_validation' => $anneeScolaire->date_debut->subDays(rand(10, 20)),
            'created_by' => 1,
            'valide_par' => 1,
        ]);

        if ($inscription) {
            AffectationClasse::create([
                'inscription_id' => $inscription->id,
                'classe_id' => $classe->id,
                'date_affectation' => $inscription->date_validation ?? now(),
                'est_active' => true,
                'numero_ordre' => $classe->inscriptions()->count(),
                'affecte_par' => 1,
            ]);
        }

        return $inscription;
    }

    /**
     * Génère un matricule unique.
     */
    private function generateMatricule(): string
    {
        $prefix = 'BDI';
        $year = date('Y');
        $sequence = Eleve::withTrashed()->count() + 1;

        return sprintf('%s%s%06d', $prefix, $year, $sequence);
    }

    /**
     * Génère un numéro d'inscription unique.
     */
    private function generateNumeroInscription(): string
    {
        $prefix = 'INS';
        $year = date('Y');
        $sequence = Inscription::count() + 1;

        return sprintf('%s%s%06d', $prefix, $year, $sequence);
    }

    /**
     * Retourne l'âge approprié selon le niveau.
     */
    private function getAgeForNiveau(string $niveauCode): int
    {
        $ages = [
            '1P' => [6, 8],
            '2P' => [7, 9],
            '3P' => [8, 10],
            '4P' => [9, 11],
            '5P' => [10, 12],
            '6P' => [11, 13],
            '7F' => [12, 14],
            '8F' => [13, 15],
            '9F' => [14, 16],
            '1PF' => [15, 17],
            '2PF' => [16, 18],
            '3PF' => [17, 19],
            '4PF' => [18, 20],
        ];

        $range = $ages[$niveauCode] ?? [10, 15];

        return rand($range[0], $range[1]);
    }
}
