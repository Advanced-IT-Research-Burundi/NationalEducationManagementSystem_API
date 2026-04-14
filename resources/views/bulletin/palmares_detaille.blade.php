<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Palmarès</title>
<style>
  @page { margin: 20mm 15mm; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #333;padding: 40px 50px; }

  .titre { text-align: center; font-size: 16px; font-weight: bold; text-transform: uppercase;
    letter-spacing: 1px; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 20px; }

  .entete { display: table; width: 100%; margin-bottom: 20px; font-size: 12px; }
  .entete-row { display: table-row; }
  .entete-cell { display: table-cell; padding: 4px 6px; }
  .entete-label { font-weight: bold; }
  .entete-value { color: #1a3a8a; font-weight: bold; }

  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th, td { border: 1px solid #333; text-align: center; padding: 6px; vertical-align: middle; }
  th { background: #e8e4dc; font-weight: bold; font-size: 11px; }
  .td-name { text-align: left; background: #fafaf6; font-weight: bold; }
  .th-small { font-size: 9px; padding: 4px; }
  .td-small { font-size: 9px; padding: 4px; }
</style>
</head>
<body>
  <div class="titre">PALMARÈS</div>

  <div class="entete">
    <div class="entete-row">
      <span class="entete-label">École :</span>
      <span class="entete-value">{{ $data['classe']['school']['nom'] ?? '—' }}</span>
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
    $totalEleves = is_array($classement) ? count($classement) : 0;
    $admisCount = 0;
    if (is_array($classement)) {
      foreach ($classement as $e) {
        $dec = strtolower((string)($e['decision_jury'] ?? ''));
        if (str_starts_with($dec, 'admis')) $admisCount++;
      }
    }
    $tauxReussite = $totalEleves > 0 ? round(($admisCount / $totalEleves) * 100, 1) : null;
  @endphp

  <table>
    <thead>
      <tr>
        <th>Place</th>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Sexe</th>
        <th>Total<br/>points<br/>obtenus</th>
        <th>%</th>
        @foreach($cours as $c)
          <th class="th-small">{{ $c['code'] }}</th>
        @endforeach
        <th>Échecs</th>
        <th>Décision<br/>du jury</th>
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
        <td><strong>{{ $entry['pourcentage'] }}%</strong></td>

        @foreach($cours as $c)
          @php $code = $c['code']; $def = $entry['echecs'][$code] ?? null; @endphp
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
</body>
</html>
