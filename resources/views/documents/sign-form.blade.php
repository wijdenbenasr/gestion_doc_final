@extends('layouts.app')

@section('title', 'Signer le document')

@section('content')
<div style="background:#0f172a;border:1px solid #3b82f6;border-radius:16px;padding:2rem;max-width:480px;width:90%;margin:2rem auto;box-shadow:0 25px 60px rgba(59,130,246,0.15);">

  <!-- Header -->
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #1e293b;">
    <div style="width:42px;height:42px;background:rgba(59,130,246,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:bold;color:#3b82f6;">S</div>
    <div>
      <h5 style="color:white;font-weight:700;margin:0;font-size:1.1rem;">Signer et envoyer le document</h5>
      <p style="color:#64748b;margin:0;font-size:0.8rem;">Téléversez le document signé pour continuer le workflow</p>
    </div>
  </div>

  <!-- Document info -->
  <div style="background:#1e293b;border-radius:10px;padding:12px 16px;margin-bottom:1.2rem;">
    <p style="color:#94a3b8;font-size:0.75rem;font-weight:600;letter-spacing:0.05em;margin:0 0 4px;">DOCUMENT</p>
    <p style="color:white;font-weight:600;margin:0;">{{ $document->name }}</p>
    <p style="color:#3b82f6;font-size:0.82rem;margin:4px 0 0;">Code : {{ $document->code }}</p>
  </div>

  <form method="POST" action="{{ route('documents.sign.upload', $document->id) }}" enctype="multipart/form-data">
    @csrf

    <!-- File upload -->
    <div style="margin-bottom:1.2rem;">
      <label style="color:#94a3b8;font-size:0.78rem;font-weight:600;letter-spacing:0.05em;display:block;margin-bottom:8px;">
        TÉLÉVERSER LE DOCUMENT SIGNÉ <span style="color:#ef4444;">*</span>
      </label>
      <label for="signedFile" style="display:flex;align-items:center;gap:12px;background:#1e293b;border:2px dashed #334155;border-radius:10px;padding:16px;cursor:pointer;transition:all 0.2s;"
             onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#334155'">
        <div style="width:36px;height:36px;background:rgba(59,130,246,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;color:#3b82f6;">+</div>
        <div>
          <p style="color:white;font-weight:500;margin:0;font-size:0.9rem;" id="signedFileName">Choisir un fichier PDF</p>
          <p style="color:#64748b;font-size:0.78rem;margin:2px 0 0;">Format accepté : PDF uniquement</p>
        </div>
      </label>
      <input type="file" id="signedFile" name="signed_pdf" accept=".pdf" required style="display:none;"
             onchange="document.getElementById('signedFileName').textContent = this.files[0]?.name || 'Choisir un fichier PDF'">
      @error('signed_pdf')
          <div style="color:#ef4444;font-size:0.85rem;margin-top:0.5rem;">{{ $message }}</div>
      @enderror
    </div>

    <!-- Buttons -->
    <div style="display:flex;gap:12px;justify-content:flex-end;">
      <a href="{{ route('documents.my') }}"
        style="padding:10px 24px;background:#1e293b;color:#94a3b8;border:1px solid #334155;border-radius:10px;cursor:pointer;font-size:0.9rem;text-decoration:none;transition:all 0.2s;display:inline-flex;align-items:center;"
        onmouseover="this.style.background='#334155'" onmouseout="this.style.background='#1e293b'">
        Retour
      </a>
      <button type="submit"
        style="padding:10px 24px;background:#3b82f6;color:white;border:none;border-radius:10px;cursor:pointer;font-size:0.9rem;font-weight:600;transition:all 0.2s;"
        onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
        Envoyer au validateur
      </button>
    </div>
  </form>

</div>
@endsection
