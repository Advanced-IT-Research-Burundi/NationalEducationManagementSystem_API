<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Bulletin Scolaire</title>
  <style>
    @page {
      margin: 30mm 15mm;
      size: A4 landscape;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Times New Roman', serif;
      font-size: 10px;
      color: #000;
    }

    .page {
      page-break-after: always;
      padding: 40px 50px;
    }

    .page:last-child {
      page-break-after: avoid;
    }

    .header-titles {
      text-align: center;
      font-weight: bold;
      margin-bottom: 15px;
    }

    .header-titles h1 {
      font-size: 14px;
      text-transform: uppercase;
      margin-bottom: 10px;
    }

    .header-titles h2 {
      font-size: 13px;
      text-transform: uppercase;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 5px;
      font-size: 9px;
      border: 3px solid #000;
    }

    th,
    td {
      border: 1px solid #000;
      text-align: center;
      padding: 3px 2px;
      vertical-align: middle;
    }

    th {
      font-weight: bold;
    }

    .td-name {
      text-align: left;
      padding-left: 4px;
    }

    .total-row td {
      font-weight: bold;
    }

    .sig-cell {
      text-align: left;
      padding-left: 5px;
      font-weight: bold;
      height: 35px;
      vertical-align: top;
      padding-top: 5px;
      border-bottom: none;
      border-top: none;
    }

    .sig-cell-first {
      border-top: 1px solid #000;
    }

    .sig-cell-last {
      border-bottom: 3px solid #000;
    }
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
        <td colspan="24"
          style="text-align: left; font-weight: bold; border-bottom: 1px solid #000; border-top: 3px solid #000; padding: 4px;">
          Nom et prénom : {{ $bulletin['eleve']['nom'] }} {{ $bulletin['eleve']['prenom'] }}
        </td>
      </tr>
      <tr>
        <td colspan="4" style="text-align: left; font-weight: bold; padding: 4px;">
          Classe : {{ $data['classe']['nom'] }}<br>
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
        <th>TJ</th>
        <th>COM.</th>
        <th>RES.</th>
        <th>TOT</th>
        <th>TJ</th>
        <th>COM.</th>
        <th>RES.</th>
        <th>TOT</th>
        <th>TJ</th>
        <th>COM.</th>
        <th>RES.</th>
        <th>TOT</th>
        <th>TJ</th>
        <th>COM.</th>
        <th>RES.</th>
        <th>TOT</th>
        <th>MAX</th>
        <th>TOT.</th>
        <th>%</th>
        <th>A.P</th>
      </tr>

      @include('bulletin.partials.courses_post_fondamental', ['bulletin' => $bulletin])

      @php
        $fmt = fn($value) => \App\Support\BulletinCourseLayout::formatNote($value);
        $conduiteT1 = ($bulletin['trimestres']['1er Trimestre'] ?? [])['conduite'] ?? null;
        $conduiteT2 = ($bulletin['trimestres']['2e Trimestre'] ?? [])['conduite'] ?? null;
        $conduiteT3 = ($bulletin['trimestres']['3e Trimestre'] ?? [])['conduite'] ?? null;
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
        $annualIsComplete = (bool) ($bulletin['annuel']['is_complete'] ?? false);
        $annualPercentage = $annualIsComplete && $grandAnnuelPoints !== null && $grandAnnuelMax > 0
          ? round(($grandAnnuelPoints / $grandAnnuelMax) * 100, 1)
          : null;

        $hasT1 = isset($bulletin['trimestres']['1er Trimestre']);
        $hasT2 = isset($bulletin['trimestres']['2e Trimestre']);
        $hasT3 = isset($bulletin['trimestres']['3e Trimestre']);
        $t1Complete = (bool) (($bulletin['trimestres']['1er Trimestre'] ?? [])['is_complete'] ?? false);
        $t2Complete = (bool) (($bulletin['trimestres']['2e Trimestre'] ?? [])['is_complete'] ?? false);
        $t3Complete = (bool) (($bulletin['trimestres']['3e Trimestre'] ?? [])['is_complete'] ?? false);
        $isAnnualBulletin = empty($data['trimestre']);
      @endphp
      <tr>
        <td colspan="3" style="text-align: left; padding-left: 5px; font-weight: bold;">Conduite</td>
        <td>-</td>
        <td>{{ $fmt(($conduiteT1 ?? [])['max'] ?? ($bulletin['conduite']['max'] ?? 60)) }}</td>
        <td></td>
        <td></td>
        <td><strong>{{ $fmt(($conduiteT1 ?? [])['max'] ?? ($bulletin['conduite']['max'] ?? 60)) }}</strong></td>

        <td>{{ $fmt(($conduiteT1 ?? [])['note'] ?? null) }}</td>
        <td></td>
        <td></td>
        <td><strong>{{ $fmt(($conduiteT1 ?? [])['note'] ?? null) }}</strong></td>

        <td>{{ $fmt(($conduiteT2 ?? [])['note'] ?? null) }}</td>
        <td></td>
        <td></td>
        <td><strong>{{ $fmt(($conduiteT2 ?? [])['note'] ?? null) }}</strong></td>

        <td>{{ $fmt(($conduiteT3 ?? [])['note'] ?? null) }}</td>
        <td></td>
        <td></td>
        <td><strong>{{ $fmt(($conduiteT3 ?? [])['note'] ?? null) }}</strong></td>

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
        <td>
          {{ $t1Complete && $grandT1Points !== null && $grandT1Max > 0 ? round(($grandT1Points / $grandT1Max) * 100, 1) : '' }}
        </td>

        <td colspan="3"></td>
        <td>
          {{ $t2Complete && $grandT2Points !== null && $grandT2Max > 0 ? round(($grandT2Points / $grandT2Max) * 100, 1) : '' }}
        </td>

        <td colspan="3"></td>
        <td>
          {{ $t3Complete && $grandT3Points !== null && $grandT3Max > 0 ? round(($grandT3Points / $grandT3Max) * 100, 1) : '' }}
        </td>

        <td colspan="2"></td>
        <td>{{ $annualPercentage !== null ? $annualPercentage . '%' : '' }}</td>
        <td></td>
      </tr>

      <tr class="total-row" style="border-bottom: 2px solid #000;">
        <td colspan="3" style="text-align: left; padding-left: 5px;">Place</td>
        <td colspan="5"></td>

        <td colspan="3"></td>
        <td>
          @php($rT1 = ($bulletin['trimestres']['1er Trimestre'] ?? [])['rang'] ?? null){{ $hasT1 ? \App\Support\BulletinCourseLayout::formatPlace($rT1, $t1Complete) : '' }}
        </td>

        <td colspan="3"></td>
        <td>
          @php($rT2 = ($bulletin['trimestres']['2e Trimestre'] ?? [])['rang'] ?? null){{ $hasT2 ? \App\Support\BulletinCourseLayout::formatPlace($rT2, $t2Complete) : '' }}
        </td>

        <td colspan="3"></td>
        <td>
          @php($rT3 = ($bulletin['trimestres']['3e Trimestre'] ?? [])['rang'] ?? null){{ $hasT3 ? \App\Support\BulletinCourseLayout::formatPlace($rT3, $t3Complete) : '' }}
        </td>

        <td colspan="2"></td>
        <td>
          @php($rAn = $bulletin['annuel']['rang'] ?? null){{ $isAnnualBulletin ? \App\Support\BulletinCourseLayout::formatPlace($rAn, $annualIsComplete) : '' }}
        </td>
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
