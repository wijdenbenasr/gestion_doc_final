<div id="globalDeleteModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#1a2035;border:1px solid #ef4444;border-radius:12px;padding:2rem;max-width:420px;width:90%;margin:auto;text-align:center;">
    <div style="font-size:3rem;margin-bottom:1rem;">š </div>
    <h5 style="color:white;font-weight:bold;margin-bottom:1rem;" id="globalDeleteTitle">Confirmer la suppression</h5>
    <p style="color:white;" id="globalDeleteName"></p>
    <p style="color:#94a3b8;font-size:0.85rem;">Cette action est irréversible.</p>
    <div style="display:flex;gap:1rem;justify-content:center;margin-top:1.5rem;">
      <button onclick="closeGlobalDeleteModal()" style="padding:8px 24px;background:#4b5563;color:white;border:none;border-radius:8px;cursor:pointer;">Annuler</button>
      <button onclick="document.getElementById('globalDeleteForm').submit()" style="padding:8px 24px;background:#ef4444;color:white;border:none;border-radius:8px;cursor:pointer;">Supprimer</button>
    </div>
  </div>
</div>

<form id="globalDeleteForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<script>
function openGlobalDeleteModal(actionUrl, itemName, title) {
    document.getElementById('globalDeleteForm').action = actionUrl;
    document.getElementById('globalDeleteName').textContent = itemName || '';
    document.getElementById('globalDeleteTitle').textContent = title || 'Confirmer la suppression';
    const modal = document.getElementById('globalDeleteModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeGlobalDeleteModal() {
    document.getElementById('globalDeleteModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('globalDeleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeGlobalDeleteModal();
});
</script>

