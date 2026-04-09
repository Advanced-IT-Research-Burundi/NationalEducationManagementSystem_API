<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>le Bulletin Scolaire</title>
<style>
  @page { margin: 20mm 15mm; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #333; }
  .page { page-break-after: always; padding: 10px 0; }
  .page:last-child { page-break-after: avoid; }

  .titre { text-align: center; font-size: 14px; font-weight: bold; text-transform: uppercase;
    letter-spacing: 1px; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 12px; }

  .entete { display: table; width: 100%; margin-bottom: 12px; font-size: 10px; }
  .entete-row { display: table-row; }
  .entete-cell { display: table-cell; padding: 2px 6px; }
  .entete-label { font-weight: bold; white-space: nowrap; }
  .entete-value { color: #1a3a8a; font-weight: bold; }

  table { width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 4px; }
  th, td { border: 1px solid #333; text-align: center; padding: 3px 4px; vertical-align: middle; }
  th { background: #e8e4dc; font-weight: bold; font-size: 8.5px; }
  .th-discipline { text-align: left; width: 140px; padding-left: 6px; }
  .td-name { text-align: left; padding-left: 6px; background: #fafaf6; }
  .cat-header { background: #d4cfc5; text-align: left; font-weight: bold; font-size: 9px; padding-left: 6px; }
  .total-row td { background: #e8e4dc; font-weight: bold; }
  .note-red { color: #c0281c; font-weight: bold; }

  .signatures { margin-top: 20px; width: 100%; display: table; }
  .sig-bloc { display: table-cell; width: 33.33%; text-align: center; padding: 0 10px; }
  .sig-label { font-size: 10px; font-weight: bold; margin-bottom: 4px; }
  .sig-line { border-bottom: 1px solid #555; height: 30px; margin-bottom: 4px; }
  .sig-sub { font-size: 9px; color: #555; }
</style>
</head>
<body>
@foreach($data['bulletins'] as $bulletin)
<div class="page">
  <div class="titre">Bulletin Scolaire</div>

  <div class="entete">
    <div class="entete-row">
      <span class="entete-label">Nom et prénom :</span>
      <span class="entete-value">{{ $bulletin['eleve']['prenom'] }} {{ $bulletin['eleve']['nom'] }}</span>
      &nbsp;&nbsp;&nbsp;
      <span class="entete-label">Matricule :</span>
      <span class="entete-value">{{ $bulletin['eleve']['matricule'] }}</span>
    </div>
    <div class="entete-row">
      <span class="entete-label">École :</span>
      <span class="entete-value">{{ $data['classe']['school']['nom'] ?? '—' }}</span>
      &nbsp;&nbsp;&nbsp;
      <span class="entete-label">Classe :</span>
      <span class="entete-value">{{ $data['classe']['nom'] }}</span>
    </div>
    <div class="entete-row">
      <span class="entete-label">Année scolaire :</span>
      <span class="entete-value">{{ $data['annee_scolaire']['libelle'] ?? '—' }}</span>
      &nbsp;&nbsp;&nbsp;
      @if($data['trimestre'])
      <span class="entete-label">Trimestre :</span>
      <span class="entete-value">{{ $data['trimestre'] }}</span>
      @endif
      &nbsp;&nbsp;&nbsp;
      <span class="entete-label">Nombre d'élèves :</span>
      <span class="entete-value">{{ $data['nombre_eleves'] }}</span>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th class="th-discipline">Discipline</th>
        <th>Max T.J.</th>
        <th>Max Exam.</th>
        <th>Max Total</th>
        <th>T.J.</th>
        <th>Exam.</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      @php
        $currentCategorie = null;
      @endphp
      @foreach($bulletin['cours'] as $cours)
        @if($cours['categorie'] !== $currentCategorie)
          @php $currentCategorie = $cours['categorie']; @endphp
          @if($currentCategorie)
          <tr>
            <td colspan="7" class="cat-header">{{ $currentCategorie }}</td>
          </tr>
          @endif
        @endif
        <tr>
          <td class="td-name">{{ $cours['nom'] }}</td>
          <td>{{ $cours['max_tj'] ?: '—' }}</td>
          <td>{{ $cours['max_examen'] ?: '—' }}</td>
          <td>{{ $cours['max_total'] ?: '—' }}</td>
          <td class="note-red">{{ $cours['note_tj'] ?: '—' }}</td>
          <td class="note-red">{{ $cours['note_examen'] ?: '—' }}</td>
          <td class="note-red">{{ $cours['note_total'] ?: '—' }}</td>
        </tr>
      @endforeach
      <tr class="total-row">
        <td class="td-name">Total</td>
        <td colspan="2"></td>
        <td>{{ $bulletin['total_max'] }}</td>
        <td colspan="2"></td>
        <td class="note-red">{{ $bulletin['total_points'] }}</td>
      </tr>
      <tr>
        <td class="td-name"><strong>Pourcentage</strong></td>
        <td colspan="5"></td>
        <td class="note-red"><strong>{{ $bulletin['pourcentage'] }}%</strong></td>
      </tr>
      <tr>
        <td class="td-name"><strong>Place</strong></td>
        <td colspan="5"></td>
        <td><strong>{{ $bulletin['rang'] }} / {{ $data['nombre_eleves'] }}</strong></td>
      </tr>
    </tbody>
  </table>

  <div class="signatures">
    <div class="sig-bloc">
      <div class="sig-label">Titulaire</div>
      <div class="sig-line"></div>
      <div class="sig-sub">Signature</div>
    </div>
    <div class="sig-bloc">
      <div class="sig-label">Directeur</div>
      <div class="sig-line"></div>
      <div class="sig-sub">Signature & cachet</div>
    </div>
    <div class="sig-bloc">
      <div class="sig-label">Parent / Tuteur</div>
      <div class="sig-line"></div>
      <div class="sig-sub">Signature</div>
    </div>
  </div>
</div>
@endforeach
</body>
</html>
