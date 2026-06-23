<?php

namespace App\Support;

final class BulletinCourseLayout
{
    public const TRIMESTRE_LABELS = ['1er Trimestre', '2e Trimestre', '3e Trimestre'];

    public static function isStandaloneCategory(?string $categorie): bool
    {
        if ($categorie === null || $categorie === '') {
            return true;
        }

        return in_array(mb_strtolower(trim($categorie)), ['autre', 'autres'], true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cours
     * @return array{groups: list<array{name: string, ordre: int, items: array<int, array<string, mixed>>}>, standalone: array<int, array<string, mixed>>}
     */
    public static function partitionForPdf(array $cours): array
    {
        $groups = [];
        $standalone = [];

        foreach ($cours as $item) {
            $categorie = $item['categorie'] ?? null;
            if (self::isStandaloneCategory($categorie)) {
                $standalone[] = $item;

                continue;
            }

            $name = (string) $categorie;
            if (!isset($groups[$name])) {
                $groups[$name] = [
                    'name' => $name,
                    'ordre' => (int) ($item['categorie_ordre'] ?? 99),
                    'items' => [],
                ];
            }

            $groups[$name]['items'][] = $item;
        }

        $groupList = array_values($groups);
        usort($groupList, static function (array $a, array $b): int {
            if ($a['ordre'] !== $b['ordre']) {
                return $a['ordre'] <=> $b['ordre'];
            }

            return strcasecmp($a['name'], $b['name']);
        });
        foreach ($groupList as &$group) {
            $group['items'] = self::sortCoursesByOrder($group['items']);
        }
        unset($group);

        $standalone = self::sortCoursesByOrder($standalone);

        return [
            'groups' => $groupList,
            'standalone' => $standalone,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private static function sortCoursesByOrder(array $items): array
    {
        usort($items, static function (array $a, array $b): int {
            $orderA = (int) ($a['ordre'] ?? 99);
            $orderB = (int) ($b['ordre'] ?? 99);

            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcasecmp((string) ($a['nom'] ?? ''), (string) ($b['nom'] ?? ''));
        });

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public static function computeGroupTotals(array $items, bool $postFondamental = false): array
    {
        $totals = [
            'max_tj' => 0,
            'max_com' => 0,
            'max_res' => 0,
            'max_tot' => 0,
            'annuel' => [
                'max_tot' => 0,
                'tot' => 0,
                'pourcentage' => null,
                'appreciation' => '',
                'has_tot' => false,
                'is_complete' => true,
            ],
            'trimestres' => [],
        ];

        foreach (self::TRIMESTRE_LABELS as $label) {
            $totals['trimestres'][$label] = [
                'tj' => 0,
                'com' => 0,
                'res' => 0,
                'tot' => 0,
                'has_tj' => false,
                'has_com' => false,
                'has_res' => false,
                'has_tot' => false,
                'tj_complete' => true,
                'com_complete' => true,
                'res_complete' => true,
                'tot_complete' => true,
            ];
        }

        foreach ($items as $cours) {
            $totals['max_tj'] += $cours['max_tj'] ?? 0;
            if ($postFondamental) {
                $totals['max_com'] += (($cours['has_competence_track'] ?? false) && ($cours['max_competence'] ?? 0) > 0)
                    ? ($cours['max_competence'] ?? 0)
                    : 0;
            }
            $totals['max_res'] += $cours['max_examen'] ?? 0;
            $totals['max_tot'] += $cours['max_total'] ?? 0;
            $totals['annuel']['max_tot'] += $cours['annuel']['max_total'] ?? 0;

            $courseAnnualComplete = self::isCourseAnnualComplete($cours);
            if ($courseAnnualComplete) {
                $totals['annuel']['tot'] += $cours['annuel']['note_total'];
                $totals['annuel']['has_tot'] = true;
            } elseif (($cours['annuel']['max_total'] ?? 0) > 0) {
                $totals['annuel']['is_complete'] = false;
            }

            foreach (self::TRIMESTRE_LABELS as $label) {
                $summary = $cours['trimestres'][$label] ?? null;
                if (($summary['note_tj'] ?? null) !== null) {
                    $totals['trimestres'][$label]['tj'] += $summary['note_tj'];
                    $totals['trimestres'][$label]['has_tj'] = true;
                } elseif (($summary['max_tj'] ?? 0) > 0) {
                    $totals['trimestres'][$label]['tj_complete'] = false;
                }

                if ($postFondamental) {
                    if (($summary['note_competence'] ?? null) !== null) {
                        $totals['trimestres'][$label]['com'] += $summary['note_competence'];
                        $totals['trimestres'][$label]['has_com'] = true;
                    } elseif (($summary['max_competence'] ?? 0) > 0) {
                        $totals['trimestres'][$label]['com_complete'] = false;
                    }
                }

                if (($summary['note_examen'] ?? null) !== null) {
                    $totals['trimestres'][$label]['res'] += $summary['note_examen'];
                    $totals['trimestres'][$label]['has_res'] = true;
                } elseif (($summary['max_examen'] ?? 0) > 0) {
                    $totals['trimestres'][$label]['res_complete'] = false;
                }

                if (($summary['note_total'] ?? null) !== null) {
                    $totals['trimestres'][$label]['tot'] += $summary['note_total'];
                    $totals['trimestres'][$label]['has_tot'] = true;
                } elseif (($summary['max_total'] ?? 0) > 0) {
                    $totals['trimestres'][$label]['tot_complete'] = false;
                }
            }
        }

        if (
            $totals['annuel']['is_complete']
            && $totals['annuel']['has_tot']
            && ($totals['annuel']['max_tot'] ?? 0) > 0
        ) {
            $totals['annuel']['pourcentage'] = round(($totals['annuel']['tot'] / $totals['annuel']['max_tot']) * 100, 1);
            $totals['annuel']['appreciation'] = self::buildAppreciationFromPercentage($totals['annuel']['pourcentage']);
        }

        return $totals;
    }

    /**
     * @param array<string, mixed> $cours
     */
    public static function isCourseAnnualComplete(array $cours): bool
    {
        foreach (self::TRIMESTRE_LABELS as $label) {
            $summary = $cours['trimestres'][$label] ?? null;
            if (!$summary || !($summary['has_expected_notes'] ?? false) || !($summary['is_complete'] ?? false)) {
                return false;
            }
        }

        return ($cours['annuel']['is_complete'] ?? false) && ($cours['annuel']['note_total'] ?? null) !== null;
    }

    public static function formatNote(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return (string) round((float) $value);
    }

    public static function formatRang(?int $rank): string
    {
        if ($rank === null) {
            return '';
        }

        return $rank === 1 ? '1ère' : $rank . 'ème';
    }

    public static function formatPlace(?int $rank, bool $isComplete): string
    {
        if ($rank !== null) {
            return self::formatRang($rank);
        }

        return $isComplete ? '' : 'Non classé';
    }

    public static function formatPercentage(?float $points, ?float $max, bool $isComplete): string
    {
        if (!$isComplete || $points === null || $max === null || $max <= 0) {
            return '';
        }

        return (string) round(($points / $max) * 100, 1);
    }

    public static function buildAppreciationFromPercentage(?float $percentage): string
    {
        if ($percentage === null) {
            return '';
        }

        if ($percentage >= 83.4) {
            return 'Excellent';
        }

        if ($percentage >= 66.7) {
            return 'Bon';
        }

        if ($percentage >= 50) {
            return 'Passable';
        }

        return 'Mauvais';
    }
}
