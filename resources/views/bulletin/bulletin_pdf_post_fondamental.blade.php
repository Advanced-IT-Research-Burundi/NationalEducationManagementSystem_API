<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bulletin Scolaire</title>
<style>
  @page { margin: 30mm 15mm; size: A4 landscape; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Times New Roman', serif; font-size: 10px; color: #000; }
  .page { page-break-after: always; padding: 40px 50px; }
  .page:last-child { page-break-after: avoid; }

  .header-titles { text-align: center; font-weight: bold; margin-bottom: 15px; }
  .header-titles h1 { font-size: 14px; text-transform: uppercase; margin-bottom: 10px; }
  .header-titles h2 { font-size: 13px; text-transform: uppercase; }

  table { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 9px; border: 3px solid #000; }
  th, td { border: 1px solid #000; text-align: center; padding: 3px 2px; vertical-align: middle; }
  th { font-weight: bold; }
  .td-name { text-align: left; padding-left: 4px; }
  
  .total-row td { font-weight: bold; }
  .sig-cell { text-align: left; padding-left: 5px; font-weight: bold; height: 35px; vertical-align: top; padding-top: 5px; border-bottom: none; border-top: none; }
  .sig-cell-first { border-top: 1px solid #000; }
  .sig-cell-last { border-bottom: 3px solid #000; }
</style>
</head>
<body>
@foreach($data['bulletins'] as $bulletin)
<div class="page">
  <div class="header-titles">
    <h1>BULLETIN SCOLAIRE DE L'ENSEIGNEMENT POST-FONDAMENTAL</h1>
    <h2>{{ $data['classe']['school']['name'] ?? "—" }}</h2>
  </div>

  <table>
    <tr>
      <td colspan="24" style="text-align: left; font-weight: bold; border-bottom: 1px solid #000; border-top: 3px solid #000; padding: 4px;">
        Nom et prénom : {{ $bulletin['eleve']['prenom'] }} {{ $bulletin['eleve']['nom'] }}
      </td>
    </tr>
    <tr>
      <td colspan="4" style="text-align: left; font-weight: bold; padding: 4px;">
        Classe de : {{ $data['classe']['nom'] }}<br>
        Nombre d'élèves : {{ $data['nombre_eleves'] }}<br>
        Année scolaire : {{ $data['annee_scolaire']['libelle'] ?? ($data['annee_scolaire']['code'] ?? '—') }}
      </td>
      <th colspan="4">MAXIMA</th>
      <th colspan="4">Premier Trimestre</th>
      <th colspan="4">Deuxième Trimestre</th>
      <th colspan="4">Troisième Trimestre</th>
      <th colspan="4">Résultats annuels</th>
    </tr>
    <tr>
      <th style="width: 20px;">N°</th>
      <th colspan="2">Domaines/Disciplines</th>
      <th style="width: 20px;">H/S</th>
      <th>TJ</th><th>COM.</th><th>RES.</th><th>TOT</th>
      <th>TJ</th><th>COM.</th><th>RES.</th><th>TOT</th>
      <th>TJ</th><th>COM.</th><th>RES.</th><th>TOT</th>
      <th>TJ</th><th>COM.</th><th>RES.</th><th>TOT</th>
      <th>MAX</th><th>TOT.</th><th>%</th><th>A.P</th>
    </tr>

    @php
      $trimestreLabels = ['1er Trimestre', '2e Trimestre', '3e Trimestre'];
      $catCounts = [];
      $catTotals = [];

      foreach ($bulletin['cours'] as $cours) {
          $cat = $cours['categorie'] ?: 'Autres';
          $catCounts[$cat] = ($catCounts[$cat] ?? 0) + 1;

          if (!isset($catTotals[$cat])) {
              $catTotals[$cat] = [
                  'max_tj' => 0,
                  'max_res' => 0,
                  'max_tot' => 0,
                  'annuel' => ['max_tot' => 0, 'tot' => 0, 'has_tot' => false, 'is_complete' => true],
                  'trimestres' => [],
              ];

              foreach ($trimestreLabels as $label) {
                  $catTotals[$cat]['trimestres'][$label] = [
                      'tj' => 0,
                      'res' => 0,
                      'tot' => 0,
                      'has_tj' => false,
                      'has_res' => false,
                      'has_tot' => false,
                      'tj_complete' => true,
                      'res_complete' => true,
                      'tot_complete' => true,
                  ];
              }
          }

          $catTotals[$cat]['max_tj'] += $cours['max_tj'] ?? 0;
          $catTotals[$cat]['max_res'] += $cours['max_examen'] ?? 0;
          $catTotals[$cat]['max_tot'] += $cours['max_total'] ?? 0;
          $catTotals[$cat]['annuel']['max_tot'] += $cours['annuel']['max_total'] ?? 0;
          if (($cours['annuel']['note_total'] ?? null) !== null) {
              $catTotals[$cat]['annuel']['tot'] += $cours['annuel']['note_total'];
              $catTotals[$cat]['annuel']['has_tot'] = true;
          } elseif (($cours['annuel']['max_total'] ?? 0) > 0) {
              $catTotals[$cat]['annuel']['is_complete'] = false;
          }

          foreach ($trimestreLabels as $label) {
              $summary = $cours['trimestres'][$label] ?? null;
              if (($summary['note_tj'] ?? null) !== null) {
                  $catTotals[$cat]['trimestres'][$label]['tj'] += $summary['note_tj'];
                  $catTotals[$cat]['trimestres'][$label]['has_tj'] = true;
              } elseif (($summary['max_tj'] ?? 0) > 0) {
                  $catTotals[$cat]['trimestres'][$label]['tj_complete'] = false;
              }

              if (($summary['note_examen'] ?? null) !== null) {
                  $catTotals[$cat]['trimestres'][$label]['res'] += $summary['note_examen'];
                  $catTotals[$cat]['trimestres'][$label]['has_res'] = true;
              } elseif (($summary['max_examen'] ?? 0) > 0) {
                  $catTotals[$cat]['trimestres'][$label]['res_complete'] = false;
              }

              if (($summary['note_total'] ?? null) !== null) {
                  $catTotals[$cat]['trimestres'][$label]['tot'] += $summary['note_total'];
                  $catTotals[$cat]['trimestres'][$label]['has_tot'] = true;
              } elseif (($summary['max_total'] ?? 0) > 0) {
                  $catTotals[$cat]['trimestres'][$label]['tot_complete'] = false;
              }
          }
      }

      $currentCat = null;
      $catIndex = 1;

      $fmt = function ($value) {
          return $value !== null && $value !== '' ? round($value) : '';
      };
    @endphp

    @foreach($bulletin['cours'] as $cours)
      @php
        $cat = $cours['categorie'] ?: 'Autres';
        $t1 = $cours['trimestres']['1er Trimestre'] ?? null;
        $t2 = $cours['trimestres']['2e Trimestre'] ?? null;
        $t3 = $cours['trimestres']['3e Trimestre'] ?? null;
        $annuel = $cours['annuel'] ?? null;
      @endphp
      <tr>
        @if($currentCat !== $cat)
          <td rowspan="{{ $catCounts[$cat] + 1 }}">{{ $catIndex++ }}</td>
          <td rowspan="{{ $catCounts[$cat] + 1 }}">{{ $cat }}</td>
          @php $currentCat = $cat; @endphp
        @endif

        <td class="td-name">{{ $cours['nom'] }}</td>
        <td>1</td>
        <td>{{ $fmt($cours['max_tj'] ?? null) }}</td>
        <td></td>
        <td>{{ $fmt($cours['max_examen'] ?? null) }}</td>
        <td><strong>{{ $fmt($cours['max_total'] ?? null) }}</strong></td>

        <td>{{ $fmt($t1['note_tj'] ?? null) }}</td>
        <td></td>
        <td>{{ $fmt($t1['note_examen'] ?? null) }}</td>
        <td><strong>{{ $fmt($t1['note_total'] ?? null) }}</strong></td>

        <td>{{ $fmt($t2['note_tj'] ?? null) }}</td>
        <td></td>
        <td>{{ $fmt($t2['note_examen'] ?? null) }}</td>
        <td><strong>{{ $fmt($t2['note_total'] ?? null) }}</strong></td>

        <td>{{ $fmt($t3['note_tj'] ?? null) }}</td>
        <td></td>
        <td>{{ $fmt($t3['note_examen'] ?? null) }}</td>
        <td><strong>{{ $fmt($t3['note_total'] ?? null) }}</strong></td>

        <td>{{ $fmt($annuel['max_total'] ?? null) }}</td>
        <td>{{ $fmt($annuel['note_total'] ?? null) }}</td>
        <td></td>
        <td></td>
      </tr>
      @if (array_search($cours, $bulletin['cours']) === array_sum(array_slice($catCounts, 0, array_search($cat, array_keys($catCounts)) + 1)) - 1)
      <tr style="font-weight: bold; background-color: #f5f5f5;">
        <td style="text-align: left; padding-left: 5px;">Total</td>
        <td>-</td>
        <td>{{ $fmt($catTotals[$cat]['max_tj']) }}</td>
        <td></td>
        <td>{{ $fmt($catTotals[$cat]['max_res']) }}</td>
        <td>{{ $fmt($catTotals[$cat]['max_tot']) }}</td>

        <td>{{ $catTotals[$cat]['trimestres']['1er Trimestre']['has_tj'] && $catTotals[$cat]['trimestres']['1er Trimestre']['tj_complete'] ? $fmt($catTotals[$cat]['trimestres']['1er Trimestre']['tj']) : '' }}</td>
        <td></td>
        <td>{{ $catTotals[$cat]['trimestres']['1er Trimestre']['has_res'] && $catTotals[$cat]['trimestres']['1er Trimestre']['res_complete'] ? $fmt($catTotals[$cat]['trimestres']['1er Trimestre']['res']) : '' }}</td>
        <td>{{ $catTotals[$cat]['trimestres']['1er Trimestre']['has_tot'] && $catTotals[$cat]['trimestres']['1er Trimestre']['tot_complete'] ? $fmt($catTotals[$cat]['trimestres']['1er Trimestre']['tot']) : '' }}</td>

        <td>{{ $catTotals[$cat]['trimestres']['2e Trimestre']['has_tj'] && $catTotals[$cat]['trimestres']['2e Trimestre']['tj_complete'] ? $fmt($catTotals[$cat]['trimestres']['2e Trimestre']['tj']) : '' }}</td>
        <td></td>
        <td>{{ $catTotals[$cat]['trimestres']['2e Trimestre']['has_res'] && $catTotals[$cat]['trimestres']['2e Trimestre']['res_complete'] ? $fmt($catTotals[$cat]['trimestres']['2e Trimestre']['res']) : '' }}</td>
        <td>{{ $catTotals[$cat]['trimestres']['2e Trimestre']['has_tot'] && $catTotals[$cat]['trimestres']['2e Trimestre']['tot_complete'] ? $fmt($catTotals[$cat]['trimestres']['2e Trimestre']['tot']) : '' }}</td>

        <td>{{ $catTotals[$cat]['trimestres']['3e Trimestre']['has_tj'] && $catTotals[$cat]['trimestres']['3e Trimestre']['tj_complete'] ? $fmt($catTotals[$cat]['trimestres']['3e Trimestre']['tj']) : '' }}</td>
        <td></td>
        <td>{{ $catTotals[$cat]['trimestres']['3e Trimestre']['has_res'] && $catTotals[$cat]['trimestres']['3e Trimestre']['res_complete'] ? $fmt($catTotals[$cat]['trimestres']['3e Trimestre']['res']) : '' }}</td>
        <td>{{ $catTotals[$cat]['trimestres']['3e Trimestre']['has_tot'] && $catTotals[$cat]['trimestres']['3e Trimestre']['tot_complete'] ? $fmt($catTotals[$cat]['trimestres']['3e Trimestre']['tot']) : '' }}</td>

        <td>{{ $fmt($catTotals[$cat]['annuel']['max_tot']) }}</td>
        <td>{{ $catTotals[$cat]['annuel']['has_tot'] && $catTotals[$cat]['annuel']['is_complete'] ? $fmt($catTotals[$cat]['annuel']['tot']) : '' }}</td>
        <td></td>
        <td></td>
      </tr>
      @endif
    @endforeach

    @php
      $conduiteT1 = $bulletin['trimestres']['1er Trimestre']['conduite'] ?? null;
      $conduiteT2 = $bulletin['trimestres']['2e Trimestre']['conduite'] ?? null;
      $conduiteT3 = $bulletin['trimestres']['3e Trimestre']['conduite'] ?? null;
      $conduiteAnnuel = $bulletin['annuel']['conduite'] ?? $bulletin['conduite'];

      $grandT1Points = isset($bulletin['trimestres']['1er Trimestre'])
          && $bulletin['trimestres']['1er Trimestre']['total_points'] !== null
          ? $bulletin['trimestres']['1er Trimestre']['total_points'] + ($conduiteT1['note'] ?? 0)
          : null;
      $grandT2Points = isset($bulletin['trimestres']['2e Trimestre'])
          && $bulletin['trimestres']['2e Trimestre']['total_points'] !== null
          ? $bulletin['trimestres']['2e Trimestre']['total_points'] + ($conduiteT2['note'] ?? 0)
          : null;
      $grandT3Points = isset($bulletin['trimestres']['3e Trimestre'])
          && $bulletin['trimestres']['3e Trimestre']['total_points'] !== null
          ? $bulletin['trimestres']['3e Trimestre']['total_points'] + ($conduiteT3['note'] ?? 0)
          : null;
      $grandAnnuelPoints = $bulletin['annuel']['total_points'] !== null
          ? $bulletin['annuel']['total_points'] + ($conduiteAnnuel['note'] ?? 0)
          : null;

      $grandT1Max = isset($bulletin['trimestres']['1er Trimestre'])
          ? ($bulletin['trimestres']['1er Trimestre']['total_max'] ?? 0) + ($conduiteT1['max'] ?? 0)
          : null;
      $grandT2Max = isset($bulletin['trimestres']['2e Trimestre'])
          ? ($bulletin['trimestres']['2e Trimestre']['total_max'] ?? 0) + ($conduiteT2['max'] ?? 0)
          : null;
      $grandT3Max = isset($bulletin['trimestres']['3e Trimestre'])
          ? ($bulletin['trimestres']['3e Trimestre']['total_max'] ?? 0) + ($conduiteT3['max'] ?? 0)
          : null;
      $grandAnnuelMax = ($bulletin['annuel']['total_max'] ?? 0) + ($conduiteAnnuel['max'] ?? 0);
      $annualPercentage = $grandAnnuelPoints !== null && $grandAnnuelMax > 0
          ? round(($grandAnnuelPoints / $grandAnnuelMax) * 100, 1)
          : null;
    @endphp
    <tr>
      <td colspan="3" style="text-align: left; padding-left: 5px; font-weight: bold;">CONDUITE / DISCIPLINE</td>
      <td>-</td>
      <td>{{ $fmt($conduiteT1['max'] ?? ($bulletin['conduite']['max'] ?? 60)) }}</td>
      <td></td>
      <td></td>
      <td><strong>{{ $fmt($conduiteT1['max'] ?? ($bulletin['conduite']['max'] ?? 60)) }}</strong></td>

      <td>{{ $fmt($conduiteT1['note'] ?? null) }}</td>
      <td></td>
      <td></td>
      <td><strong>{{ $fmt($conduiteT1['note'] ?? null) }}</strong></td>

      <td>{{ $fmt($conduiteT2['note'] ?? null) }}</td>
      <td></td>
      <td></td>
      <td><strong>{{ $fmt($conduiteT2['note'] ?? null) }}</strong></td>

      <td>{{ $fmt($conduiteT3['note'] ?? null) }}</td>
      <td></td>
      <td></td>
      <td><strong>{{ $fmt($conduiteT3['note'] ?? null) }}</strong></td>

      <td>{{ $fmt($conduiteAnnuel['max'] ?? null) }}</td>
      <td>{{ $fmt($conduiteAnnuel['note'] ?? null) }}</td>
      <td></td>
      <td></td>
    </tr>

    <tr class="total-row" style="border-top: 3px solid #000;">
      <td colspan="3" style="text-align: left; padding-left: 5px;">TOTAL GLOBAL</td>
      <td></td>
      <td colspan="3"></td>
      <td>{{ $fmt($grandT1Max) }}</td>

      <td colspan="3"></td>
      <td>{{ $fmt($grandT1Points) }}</td>

      <td colspan="3"></td>
      <td>{{ $fmt($grandT2Points) }}</td>

      <td colspan="3"></td>
      <td>{{ $fmt($grandT3Points) }}</td>

      <td>{{ $fmt($grandAnnuelMax) }}</td>
      <td>{{ $fmt($grandAnnuelPoints) }}</td>
      <td></td>
      <td></td>
    </tr>

    <tr class="total-row">
      <td colspan="3" style="text-align: left; padding-left: 5px;">Pourcentage</td>
      <td colspan="5"></td>

      <td colspan="3"></td>
      <td>{{ isset($bulletin['trimestres']['1er Trimestre']) && $grandT1Points !== null && $grandT1Max > 0 ? round(($grandT1Points / $grandT1Max) * 100, 1) . '%' : '' }}</td>

      <td colspan="3"></td>
      <td>{{ isset($bulletin['trimestres']['2e Trimestre']) && $grandT2Points !== null && $grandT2Max > 0 ? round(($grandT2Points / $grandT2Max) * 100, 1) . '%' : '' }}</td>

      <td colspan="3"></td>
      <td>{{ isset($bulletin['trimestres']['3e Trimestre']) && $grandT3Points !== null && $grandT3Max > 0 ? round(($grandT3Points / $grandT3Max) * 100, 1) . '%' : '' }}</td>

      <td colspan="2"></td>
      <td>{{ $annualPercentage !== null ? $annualPercentage . '%' : '' }}</td>
      <td></td>
    </tr>

    <tr class="total-row" style="border-bottom: 2px solid #000;">
      <td colspan="3" style="text-align: left; padding-left: 5px;">Place</td>
      <td colspan="5"></td>

      <td colspan="3"></td>
      <td>{{ $bulletin['trimestres']['1er Trimestre']['rang'] ?? '' }}</td>

      <td colspan="3"></td>
      <td>{{ $bulletin['trimestres']['2e Trimestre']['rang'] ?? '' }}</td>

      <td colspan="3"></td>
      <td>{{ $bulletin['trimestres']['3e Trimestre']['rang'] ?? '' }}</td>

      <td colspan="2"></td>
      <td>{{ $bulletin['annuel']['rang'] ?? '' }}</td>
      <td></td>
    </tr>

    <!-- Signatures à l'intérieur du tableau -->
    <tr class="total-row" style="border-bottom: 2px solid #000;">
      <td colspan="3" rowspan="2" class="sig-cell sig-cell-first">Signatures</td>
      <td colspan="5" class="sig-cell">Titulaire</td>
      <td colspan="4" class="sig-cell"></td>
      <td colspan="4" class="sig-cell"></td>
      <td colspan="4" class="sig-cell"></td>
      <td colspan="4" class="sig-cell sig-cell-last"></td>
    </tr>
    <tr>
      <td colspan="5" class="sig-cell">Parent</td>
      <td colspan="4" class="sig-cell"></td>
      <td colspan="4" class="sig-cell"></td>
      <td colspan="4" class="sig-cell "></td>
      <td colspan="4" class="sig-cell sig-cell-last"></td>
    </tr>

  </table>

</div>
@endforeach
</body>
</html>
