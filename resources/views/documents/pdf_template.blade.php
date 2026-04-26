<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Document - {{ $document->nom }}</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
    .info { margin: 20px 0; }
    .info table { width: 100%; border-collapse: collapse; }
    .info td { padding: 8px; border: 1px solid #ddd; }
    .info td:first-child { font-weight: bold; background: #f5f5f5; width: 30%; }
  </style>
</head>
<body>
  <div class="header">
    <h2>GESTION DOCUMENTAIRE QUALITÉ</h2>
    <h3>{{ $document->nom }}</h3>
  </div>
  
  <div class="info">
    <table>
      <tr>
        <td>Code</td>
        <td>{{ $document->code }}</td>
      </tr>
      <tr>
        <td>Type</td>
        <td>{{ $document->type }}</td>
      </tr>
      <tr>
        <td>AIO</td>
        <td>{{ $document->aio }}</td>
      </tr>
      <tr>
        <td>Ligne</td>
        <td>{{ $document->ligne }}</td>
      </tr>
      <tr>
        <td>Phase</td>
        <td>{{ $document->phase }}</td>
      </tr>
      <tr>
        <td>Révision</td>
        <td>{{ $document->revision }}</td>
      </tr>
      <tr>
        <td>Créateur</td>
        <td>{{ $document->creator->name }}</td>
      </tr>
      <tr>
        <td>Date création</td>
        <td>{{ $document->created_at->format('d/m/Y') }}</td>
      </tr>
      <tr>
        <td>Statut</td>
        <td>{{ strtoupper($document->status) }}</td>
      </tr>
    </table>
  </div>
  
  <div style="margin-top: 40px;">
    <p><strong>Hash SHA-256 :</strong> {{ $document->hash }}</p>
  </div>
</body>
</html>