<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Employes RH</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 20px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Liste des employes</h1>
    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Département</th>
                <th>Poste</th>
                <th>Service</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employes as $employe)
                <tr>
                    <td>{{ $employe->matricule }}</td>
                    <td>{{ $employe->nom }}</td>
                    <td>{{ $employe->prenom }}</td>
                    <td>{{ $employe->departement?->nom }}</td>
                    <td>{{ $employe->poste?->nom }}</td>
                    <td>{{ $employe->service?->nom }}</td>
                    <td>{{ $employe->statut }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
