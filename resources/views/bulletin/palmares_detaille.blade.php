<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Palmarès</title>
  <style>
    @page {
      margin: 20mm 15mm;
      size: A4 landscape;
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
      max-width: 100%;
      table-layout: fixed;
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
      white-space: normal;
      word-break: break-word;
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
      min-width: 70px;
    }

    .th-small {
      font-size: 8px;
      padding: 3px 2px;
      width: 28px;
      max-width: 28px;
    }

    .td-small {
      font-size: 8px;
      padding: 3px 2px;
      width: 28px;
      max-width: 28px;
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
  <div class="titre">PALMARES</div>

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
    $cours = $data['cours'] ?? [];
    $classement = $data['classement'] ?? [];
    $tauxReussite = $data['taux_reussite'] ?? null;
    $nonClasses = $data['non_classes'] ?? [];
  @endphp

  <table>
    <colgroup>
      <col style="width: 32px;">  {{-- Place --}}
      <col style="width: 80px;">  {{-- Nom --}}
      <col style="width: 80px;">  {{-- Prénom --}}
      <col style="width: 24px;">  {{-- Sexe --}}
      <col style="width: 42px;">  {{-- Total points --}}
      <col style="width: 32px;">  {{-- % --}}
      @foreach($cours as $c)
        <col style="width: 28px;">
      @endforeach
      <col style="width: 30px;">  {{-- Échecs --}}
      <col style="width: 40px;">  {{-- Décision jury --}}
    </colgroup>
    <thead>
      <tr>
        <th>Place</th>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Sexe</th>
        <th>Total<br />points<br />obtenus</th>
        <th>%</th>
        @foreach($cours as $c)
          <th class="th-small">{{ $c['code'] }}</th>
        @endforeach
        <th>Échecs</th>
        <th>Décision<br />du jury</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data['classement'] as $entry)
        <tr>
          <td><strong>{{ $entry['rang'] }}</strong></td>
          <td class="td-name">{{ $entry['eleve']['nom'] }}</td>
          <td class="td-name">{{ $entry['eleve']['prenom'] }}</td>
          <td>{{ $entry['eleve']['sexe'] ?? '—' }}</td>
          <td><strong>{{ $entry['total_points'] }}</strong></td>
          <td><strong>{{ $entry['pourcentage'] }}</strong></td>

          @foreach($cours as $c)
            @php $code = $c['code'];
            $def = $entry['echecs'][$code] ?? null; @endphp
            <td class="td-small">
              @if(!is_null($def) && $def !== '')
                -{{ $def }}
              @endif
            </td>
          @endforeach

          <td><strong>{{ $entry['nombre_echecs'] ?? '' }}</strong></td>
          <td><strong>{{ $entry['decision_jury'] ?? '' }}</strong></td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @if(!is_null($tauxReussite))
    <div style="margin-top: 14px; font-weight: bold;">
      Le taux de réussite de la classe : {{ $tauxReussite }}%
    </div>
  @endif

  @if(!is_null($data['moyenne_classe'] ?? null))
    <div style="margin-top: 6px; font-weight: bold;">
      La moyenne de la classe : {{ $data['moyenne_classe'] }}%
    </div>
  @endif

  @if(!empty($nonClasses))
    <div style="margin-top: 8px;">
      <strong>Non classés :</strong>
      <ol style="margin: 6px 0 0 18px; padding-left: 18px;">
        @foreach($nonClasses as $entry)
          @php
            $nomComplet = trim(($entry['eleve']['nom'] ?? '') . ' ' . ($entry['eleve']['prenom'] ?? ''));
          @endphp
          @if($nomComplet !== '')
            <li style="margin-bottom: 2px;">{{ $nomComplet }}</li>
          @endif
        @endforeach
      </ol>
    </div>
  @endif
</body>

</html>
