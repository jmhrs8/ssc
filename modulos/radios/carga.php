<div class="card p-4">
    <h4>Carga Masiva de Radios</h4>
    <input type="file" id="excel_radios" class="form-control mb-3">
    <select id="modo_radios" class="form-select mb-3">
        <option value="duplicar">Permitir Duplicados</option>
        <option value="reemplazar">Reemplazar Duplicados (por Serie)</option>
    </select>
    <button onclick="subirRadios()" class="btn btn-success">Iniciar Subida</button>

    <div class="progress mt-4" style="height: 25px; display:none;" id="cont_progreso">
        <div id="barra_radios" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%;">0%</div>
    </div>
</div>

<script>
function subirRadios() {
    let file = document.getElementById('excel_radios').files[0];
    let modo = document.getElementById('modo_radios').value;
    let formData = new FormData();
    formData.append('archivo', file);
    formData.append('modo', modo);

    document.getElementById('cont_progreso').style.display = 'block';

    fetch('subir_excel.php', { method: 'POST', body: formData });

    let int = setInterval(() => {
        fetch('obtener_progreso.php').then(r => r.json()).then(d => {
            document.getElementById('barra_radios').style.width = d.p + '%';
            document.getElementById('barra_radios').innerText = d.p + '%';
            if(d.p >= 100) clearInterval(int);
        });
    }, 500);
}
</script>
