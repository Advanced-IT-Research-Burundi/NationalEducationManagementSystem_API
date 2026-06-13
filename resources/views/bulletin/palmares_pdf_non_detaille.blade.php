<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Palmarès</title>
  <style>
    @page {
      margin: 20mm 15mm;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    body {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 11px;
      color: #333;
      padding: 40px 50px;
    }

    .titre {
      text-align: center;
      font-size: 16px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
      border-bottom: 2px solid #333;
      padding-bottom: 8px;
      margin-bottom: 20px;
    }

    .entete {
      display: table;
      width: 100%;
      margin-bottom: 20px;
      font-size: 12px;
    }

    .entete-row {
      display: table-row;
    }

    .entete-cell {
      display: table-cell;
      padding: 4px 6px;
    }

    .entete-label {
      font-weight: bold;
    }

    .entete-value {
      color: #1a3a8a;
      font-weight: bold;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      border: 1.4pt solid #000;
    }

    th,
    td {
      border: 0.8pt solid #000;
      text-align: center;
      padding: 6px;
      vertical-align: middle;
    }

    th {
      background: #e8e4dc;
      font-weight: bold;
      font-size: 11px;
    }

    .td-name {
      text-align: left;
      background: #fafaf6;
      font-weight: bold;
    }

    @media print {
      html,
      body {
        width: 100%;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      table {
        border: 1.4pt solid #000 !important;
      }

      th,
      td {
        border: 0.8pt solid #000 !important;
      }

      tr {
        break-inside: avoid;
        page-break-inside: avoid;
      }
    }
  </style>
</head>

<body>
  <div class="titre">PALMARÈS</div>

  <div class="entete">
    <div class="entete-row">
      <span class="entete-label">Ecole :</span>
      <span class="entete-value">{{ $data['classe']['school']['name'] ?? '—' }}</span>
      &nbsp;&nbsp;&nbsp;&nbsp;
      <span class="entete-label">Classe :</span>
      <span class="entete-value">{{ $data['classe']['nom'] }}</span>
    </div>
    <div class="entete-row">
      <span class="entete-label">Trimestre :</span>
      <span class="entete-value">{{ $data['trimestre'] ?? 'Tous' }}</span>
      &nbsp;&nbsp;&nbsp;&nbsp;
      <span class="entete-label">Année Scolaire :</span>
      <span class="entete-value">{{ $data['annee_scolaire']['libelle'] ?? '—' }}</span>
    </div>
  </div>

  @php
    $tauxReussite = $data['taux_reussite'] ?? null;
    $nonClasses = $data['non_classes'] ?? [];
    $nonClassesLabel = collect($nonClasses)->map(function ($entry) {
      return trim(($entry['eleve']['nom'] ?? '') . ' ' . ($entry['eleve']['prenom'] ?? ''));
    })->filter()->implode(', ');
  @endphp

  <table>
    <thead>
      <tr>
        <th>Rang</th>
        <th>Matricule</th>
        <th>Nom et Prénom</th>
        <th>Total</th>
        <th>Maximum</th>
        <th>Pourcentage</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data['classement'] as $entry)
        <tr>
          <td><strong>{{ $entry['rang'] }}</strong></td>
          <td>{{ $entry['eleve']['matricule'] }}</td>
          <td class="td-name">{{ $entry['eleve']['nom'] }} {{ $entry['eleve']['prenom'] }}</td>
          <td>{{ $entry['total_points'] }}</td>
          <td>{{ $entry['total_max'] }}</td>
          <td><strong>{{ $entry['pourcentage'] }}%</strong></td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @if(!is_null($tauxReussite))
    <div style="margin-top: 14px; font-weight: bold;">
      Le taux de réussite de la classe : {{ $tauxReussite }}%
    </div>
  @endif

  @if($nonClassesLabel !== '')
    <div style="margin-top: 8px;">
      <strong>Non classés :</strong> {{ $nonClassesLabel }}
    </div>
  @endif
</body>

</html>
