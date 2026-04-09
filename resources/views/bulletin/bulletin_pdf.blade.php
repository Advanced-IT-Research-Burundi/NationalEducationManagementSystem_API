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
    <h2>{{ $data['classe']['school']['nom'] ?? "LYCEE D'EXCELLENCE NGAGARA" }}</h2>
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
        Nombre d'élèves : {{ $data['nombre_eleves'] }}
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
      <th>COM.</th><th>RES.</th><th>TOT</th><th>TJ</th>
      <th>COM.</th><th>RES.</th><th>TOT</th><th>TJ</th>
      <th>COM.</th><th>RES.</th><th>TOT</th><th>TJ</th>
      <th>COM.</th><th>RES.</th><th>TOT</th><th>TJ</th>
      <th>COM.</th><th>RES.</th><th>TOT</th>
    </tr>

    @php
      $catCounts = [];
      $catTotals = [];
      foreach ($bulletin['cours'] as $cours) {
          $cat = $cours['categorie'] ?: 'Autres';
          $catCounts[$cat] = ($catCounts[$cat] ?? 0) + 1;
          
          if (!isset($catTotals[$cat])) {
              $catTotals[$cat] = ['max_tj' => 0, 'max_res' => 0, 'max_tot' => 0, 'tj' => 0, 'res' => 0, 'tot' => 0];
          }
          $catTotals[$cat]['max_tj'] += $cours['max_tj'];
          $catTotals[$cat]['max_res'] += $cours['max_examen'];
          $catTotals[$cat]['max_tot'] += $cours['max_total'];
          $catTotals[$cat]['tj'] += (float)$cours['note_tj'];
          $catTotals[$cat]['res'] += (float)$cours['note_examen'];
          $catTotals[$cat]['tot'] += (float)$cours['note_total'];
      }
      $currentCat = null;
      $catIndex = 1;
      
      $isT1 = $data['trimestre'] === '1er Trimestre';
      $isT2 = $data['trimestre'] === '2e Trimestre';
      $isT3 = $data['trimestre'] === '3e Trimestre';
      $isAll = empty($data['trimestre']) || $data['trimestre'] === 'Tous les trimestres';
    @endphp

    @foreach($bulletin['cours'] as $cours)
      @php 
        $cat = $cours['categorie'] ?: 'Autres'; 
      @endphp
      <tr>
        @if($currentCat !== $cat)
          <td rowspan="{{ $catCounts[$cat] + 1 }}">{{ $catIndex++ }}</td>
          <td rowspan="{{ $catCounts[$cat] + 1 }}">{{ $cat }}</td>
          @php $currentCat = $cat; @endphp
        @endif
        
        <td class="td-name">{{ $cours['nom'] }}</td>
        <td>1</td> <!-- Default H/S -->
        
        <!-- Maxima -->
        <td>{{ $cours['max_tj'] ? round($cours['max_tj']) : '' }}</td>
        <td></td> <!-- COM -->
        <td>{{ $cours['max_examen'] ? round($cours['max_examen']) : '' }}</td>
        <td><strong>{{ $cours['max_total'] ? round($cours['max_total']) : '' }}</strong></td>
        
        <!-- Trimestre 1 -->
        <td>{{ ($isT1 || $isAll) && $cours['note_tj'] !== '' && $cours['note_tj'] !== null ? round($cours['note_tj']) : '' }}</td>
        <td></td> <!-- COM -->
        <td>{{ ($isT1 || $isAll) && $cours['note_examen'] !== '' && $cours['note_examen'] !== null ? round($cours['note_examen']) : '' }}</td>
        <td><strong>{{ ($isT1 || $isAll) && $cours['note_total'] !== '' && $cours['note_total'] !== null ? round($cours['note_total']) : '' }}</strong></td>
        
        <!-- Trimestre 2 -->
        <td>{{ $isT2 && $cours['note_tj'] !== '' && $cours['note_tj'] !== null ? round($cours['note_tj']) : '' }}</td>
        <td></td> <!-- COM -->
        <td>{{ $isT2 && $cours['note_examen'] !== '' && $cours['note_examen'] !== null ? round($cours['note_examen']) : '' }}</td>
        <td><strong>{{ $isT2 && $cours['note_total'] !== '' && $cours['note_total'] !== null ? round($cours['note_total']) : '' }}</strong></td>
        
        <!-- Trimestre 3 -->
        <td>{{ $isT3 && $cours['note_tj'] !== '' && $cours['note_tj'] !== null ? round($cours['note_tj']) : '' }}</td>
        <td></td> <!-- COM -->
        <td>{{ $isT3 && $cours['note_examen'] !== '' && $cours['note_examen'] !== null ? round($cours['note_examen']) : '' }}</td>
        <td><strong>{{ $isT3 && $cours['note_total'] !== '' && $cours['note_total'] !== null ? round($cours['note_total']) : '' }}</strong></td>
        
        <!-- Annuel -->
        <td>{{ $cours['max_total'] ? round($cours['max_total'] * ($isAll ? 3 : 1)) : '' }}</td>
        <td>{{ $cours['note_total'] !== '' && $cours['note_total'] !== null ? round($cours['note_total']) : '' }}</td>
        <td></td>
        <td></td>
      </tr>
      @if (array_search($cours, $bulletin['cours']) === array_sum(array_slice($catCounts, 0, array_search($cat, array_keys($catCounts)) + 1)) - 1)
      <!-- Sous-total de la catégorie -->
      <tr style="font-weight: bold; background-color: #f5f5f5;">
        <td style="text-align: left; padding-left: 5px;">Total</td>
        <td>-</td>
        <td>{{ round($catTotals[$cat]['max_tj']) }}</td>
        <td></td>
        <td>{{ round($catTotals[$cat]['max_res']) }}</td>
        <td>{{ round($catTotals[$cat]['max_tot']) }}</td>
        
        <!-- T1 -->
        <td>{{ $isT1 || $isAll ? round($catTotals[$cat]['tj']) : '' }}</td>
        <td></td>
        <td>{{ $isT1 || $isAll ? round($catTotals[$cat]['res']) : '' }}</td>
        <td>{{ $isT1 || $isAll ? round($catTotals[$cat]['tot']) : '' }}</td>
        
        <!-- T2 -->
        <td>{{ $isT2 ? round($catTotals[$cat]['tj']) : '' }}</td>
        <td></td>
        <td>{{ $isT2 ? round($catTotals[$cat]['res']) : '' }}</td>
        <td>{{ $isT2 ? round($catTotals[$cat]['tot']) : '' }}</td>
        
        <!-- T3 -->
        <td>{{ $isT3 ? round($catTotals[$cat]['tj']) : '' }}</td>
        <td></td>
        <td>{{ $isT3 ? round($catTotals[$cat]['res']) : '' }}</td>
        <td>{{ $isT3 ? round($catTotals[$cat]['tot']) : '' }}</td>
        
        <!-- Annuel -->
        <td>{{ round($catTotals[$cat]['max_tot'] * ($isAll ? 3 : 1)) }}</td>
        <td>{{ round($catTotals[$cat]['tot']) }}</td>
        <td></td>
        <td></td>
      </tr>
      @endif
    @endforeach

    <!-- Grand Total -->
    <tr class="total-row" style="border-top: 3px solid #000;">
      <td colspan="3" style="text-align: left; padding-left: 5px;">TOTAL</td>
      <td></td>
      <td colspan="3"></td>
      <td>{{ round($bulletin['total_max']) }}</td>
      
      <!-- T1 -->
      <td colspan="3"></td>
      <td>{{ $isT1 || $isAll ? round($bulletin['total_points']) : '' }}</td>
      
      <!-- T2 -->
      <td colspan="3"></td>
      <td>{{ $isT2 ? round($bulletin['total_points']) : '' }}</td>
      
      <!-- T3 -->
      <td colspan="3"></td>
      <td>{{ $isT3 ? round($bulletin['total_points']) : '' }}</td>
      
      <!-- Annuel -->
      <td>{{ round($bulletin['total_max'] * ($isAll ? 3 : 1)) }}</td>
      <td>{{ round($bulletin['total_points']) }}</td>
      <td></td>
      <td></td>
    </tr>
    
    <tr class="total-row">
      <td colspan="3" style="text-align: left; padding-left: 5px;">Pourcentage</td>
      <td colspan="5"></td>
      
      <!-- T1 -->
      <td colspan="3"></td>
      <td>{{ $isT1 || $isAll ? round($bulletin['pourcentage'], 1) . '%' : '' }}</td>
      
      <!-- T2 -->
      <td colspan="3"></td>
      <td>{{ $isT2 ? round($bulletin['pourcentage'], 1) . '%' : '' }}</td>
      
      <!-- T3 -->
      <td colspan="3"></td>
      <td>{{ $isT3 ? round($bulletin['pourcentage'], 1) . '%' : '' }}</td>
      
      <!-- Annuel -->
      <td colspan="2"></td>
      <td>{{ round($bulletin['pourcentage'], 1) }}%</td>
      <td></td>
    </tr>
    
    <tr class="total-row" style="border-bottom: 2px solid #000;">
      <td colspan="3" style="text-align: left; padding-left: 5px;">Place</td>
      <td colspan="5"></td>
      
      <!-- T1 -->
      <td colspan="3"></td>
      <td>{{ $isT1 || $isAll ? $bulletin['rang'] : '' }}</td>
      
      <!-- T2 -->
      <td colspan="3"></td>
      <td>{{ $isT2 ? $bulletin['rang'] : '' }}</td>
      
      <!-- T3 -->
      <td colspan="3"></td>
      <td>{{ $isT3 ? $bulletin['rang'] : '' }}</td>
      
      <!-- Annuel -->
      <td colspan="2"></td>
      <td>{{ $bulletin['rang'] }}</td>
      <td></td>
    </tr>

    <!-- Signatures à l'intérieur du tableau -->
    <tr>
      <td colspan="3" class="sig-cell sig-cell-first">Signatures</td>
      <td colspan="10" class="sig-cell">Titulaire</td>
      <td colspan="10" class="sig-cell sig-cell-last">Parent</td>
    </tr>

  </table>

</div>
@endforeach
</body>
</html>
