<?php

namespace App\Support;

use App\Models\Niveau;

class AcademicCycleHelper
{
    /**
     * Post-fondamental: Cycle 5 (seeds), legacy POST_FONDAMENTAL, or type scolaire Post-Fondamental.
     */
    public static function isPostFondamental(?Niveau $niveau): bool
    {
        if (!$niveau) {
            return false;
        }

        $cycleNom = optional($niveau->relationLoaded('cycleScolaire')
            ? $niveau->getRelation('cycleScolaire')
            : $niveau->cycleScolaire)->nom;

        if (in_array($cycleNom, ['Cycle 5', 'Post_Fondamental'], true)) {
            return true;
        }

        $typeNom = optional($niveau->relationLoaded('typeScolaire')
            ? $niveau->getRelation('typeScolaire')
            : $niveau->typeScolaire)->nom;

        return $typeNom === 'Post-Fondamental';
    }

    public static function usesPostFondamentalBulletinLayout(?Niveau $niveau): bool
    {
        if (!$niveau) {
            return false;
        }

        $cycleNom = optional($niveau->relationLoaded('cycleScolaire')
            ? $niveau->getRelation('cycleScolaire')
            : $niveau->cycleScolaire)->nom;


        return in_array($cycleNom, ['Cycle 5', 'Post_Fondamental', 'Secondaire'], true);
    }
}
