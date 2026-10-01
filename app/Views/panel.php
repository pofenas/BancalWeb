<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bancal</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f5f7fa;
      color: #222;
    }

    .layout {
      display: flex;
      min-height: 100vh;
      flex-direction: column;
    }

    @media (min-width: 768px) {
      .layout {
        flex-direction: row;
      }
    }

    .panel {
      width: 100%;
      padding: 20px;
      overflow: auto;
    }

    @media (min-width: 768px) {
      .panel {
        width: 50%;
      }
    }

    .panel-left {
      border-bottom: 1px solid #d7dce2;
      background: #ffffff;
    }

    @media (min-width: 768px) {
      .panel-left {
        border-right: 1px solid #d7dce2;
        border-bottom: none;
      }
    }

    .panel-right {
      background: #f8fafc;
    }

    h1,
    h2 {
      margin-top: 0;
    }

    .card {
      background: #fff;
      border: 1px solid #d7dce2;
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 20px;
    }

    .table-container {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      min-width: 600px;
    }

    th,
    td {
      border: 1px solid #d7dce2;
      padding: 8px;
      text-align: left;
      vertical-align: middle;
      white-space: nowrap;
    }

    th {
      background: #eef2f7;
    }

    input,
    select,
    button {
      width: 100%;
      padding: 6px 8px;
      font-size: 14px;
      min-width: 80px;
    }

    select {
      min-width: 100px;
    }

    button {
      cursor: pointer;
      border: 1px solid #b8c2cc;
      background: #fff;
      white-space: nowrap;
    }

    button:hover {
      background: #f0f3f6;
    }

    .status-on {
      color: #0a7d2d;
      font-weight: bold;
    }

    .status-off {
      color: #b42318;
      font-weight: bold;
    }

    .msg {
      min-height: 22px;
      margin: 10px 0 0;
      font-size: 14px;
    }

    .msg.ok {
      color: #0a7d2d;
    }

    .msg.error {
      color: #b42318;
    }

    .loading {
      opacity: 0.6;
      pointer-events: none;
    }

    .new-row {
      background: #fdfdf2;
    }

    .new-row td {
      border-top: 2px dashed #d6c97a;
    }

    .id-cell-muted {
      color: #777;
      font-style: italic;
    }
    .dow-list {
      display: grid;
      grid-template-columns: repeat(2, minmax(120px, 1fr));
      gap: 8px;
      margin: 10px 0 16px;
    }

    .dow-list label {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .dow-list input {
      width: auto;
      min-width: auto;
    }

    .button-row {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .button-row button {
      width: auto;
    }
    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      align-items: center;
    }

    .action-buttons button {
      width: auto;
      min-width: 0;
      padding: 5px 8px;
      font-size: 13px;
    }
  </style>
</head>
<body>
  <div class="layout">
    <div class="panel panel-left">
      <h1>Estado actual</h1>

      <div class="card">
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Alias</th>
                <th>Última conexión</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dispositivos as $dispositivo): ?>
                <?php $alias = $dispositivo['alias']; ?>
                <!-- Fila principal: alias + lastcon -->
                <tr>
                  <td><?= esc($alias) ?></td>
                  <td>
                    <?php if (!empty($lastcon[$alias])): ?>
                      <?= esc($lastcon[$alias]) ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                </tr>

                <!-- Fila con sub‑tabla de canales -->
                <tr>
                  <td colspan="2" style="padding:0;">
                    <div class="table-container" style="margin:0;">
                      <table>
                        <thead>
                          <tr>
                            <th>Canal</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (!empty($statusByAlias[$alias])): ?>
                            <?php foreach ($statusByAlias[$alias] as $s): ?>
                              <tr>
                                <td><?= esc($s['canal']) ?></td>
                                <td class="<?= $s['status'] === 'ON' ? 'status-on' : 'status-off' ?>">
                                  <?= esc($s['status']) ?>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <tr>
                              <td colspan="2">-</td>
                            </tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="panel panel-right">
  <h2>Programación</h2>

  <div class="card">
    <label for="aliasSelect">Dispositivo</label>
    <select id="aliasSelect">
      <option value="">Selecciona un dispositivo</option>
      <?php foreach ($dispositivos as $dispositivo): ?>
        <option value="<?= esc($dispositivo['alias']) ?>">
          <?= esc($dispositivo['alias']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <div id="msg" class="msg"></div>
  </div>

  <div class="card">
    <h3>Nuevo riego</h3>

    <label for="canalInput">Canal</label>
    <input id="canalInput" type="number" min="1">

    <label for="horaInput">Hora de encendido</label>
    <input id="horaInput" type="time">

    <label for="duracionInput">Tiempo de riego, en minutos</label>
    <input id="duracionInput" type="number" min="1">

    <h4>Días de la semana</h4>

    <div class="dow-list">
      <label>
        <input type="checkbox" class="dowCheckbox" value="0">
        Domingo
      </label>

      <label>
        <input type="checkbox" class="dowCheckbox" value="1">
        Lunes
      </label>

      <label>
        <input type="checkbox" class="dowCheckbox" value="2">
        Martes
      </label>

      <label>
        <input type="checkbox" class="dowCheckbox" value="3">
        Miércoles
      </label>

      <label>
        <input type="checkbox" class="dowCheckbox" value="4">
        Jueves
      </label>

      <label>
        <input type="checkbox" class="dowCheckbox" value="5">
        Viernes
      </label>

      <label>
        <input type="checkbox" class="dowCheckbox" value="6">
        Sábado
      </label>
    </div>

    <div class="button-row">
      <button type="button" id="selectAllBtn">Marcar todos</button>
      <button type="button" id="clearAllBtn">Desmarcar todos</button>
      <button type="button" id="createBtn">Guardar riego</button>
    </div>
  </div>

  <div class="card" id="todoContainer">
    <h3>Riegos programados</h3>

    <div class="table-container">
      <table id="todoTable">
        <thead>
          <tr>
            <th>Canal</th>
            <th>Días</th>
            <th>Inicio</th>
            <th>Duración</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="todoBody">
          <tr>
            <td colspan="5">Selecciona un dispositivo</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  const aliasSelect = document.getElementById('aliasSelect');
  const todoBody = document.getElementById('todoBody');
  const todoContainer = document.getElementById('todoContainer');
  const msg = document.getElementById('msg');
  const createBtn = document.getElementById('createBtn');
  const selectAllBtn = document.getElementById('selectAllBtn');
  const clearAllBtn = document.getElementById('clearAllBtn');
  const dowCheckboxes = document.querySelectorAll('.dowCheckbox');

  const dowLabels = [
    'domingo',
    'lunes',
    'martes',
    'miércoles',
    'jueves',
    'viernes',
    'sábado'
  ];

  function setMessage(text, type = '')
  {
    msg.textContent = text;
    msg.className = 'msg' + (type ? ' ' + type : '');
  }

  function escapeHtml(value)
  {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function formatTime(hora, minuto)
  {
    return String(hora).padStart(2, '0')
      + ':'
      + String(minuto).padStart(2, '0');
  }

  function formatDuration(minutes)
  {
    if (minutes < 60)
    {
      return minutes + ' min';
    }

    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (rest === 0)
    {
      return hours + ' h';
    }

    return hours + ' h ' + rest + ' min';
  }

  function existingRowTemplate(row)
  {
    const dias = row.dows
      .sort((a, b) => a - b)
      .map((dow) => dowLabels[dow])
      .join(', ');

    const inicio = formatTime(row.hora, row.minuto);

    return `
      <tr
        data-on-ids="${escapeHtml(row.on_ids.join(','))}"
        data-off-ids="${escapeHtml(row.off_ids.join(','))}"
        data-canal="${escapeHtml(row.canal)}"
        data-hora="${escapeHtml(row.hora)}"
        data-minuto="${escapeHtml(row.minuto)}"
        data-dows="${escapeHtml(row.dows.join(','))}"
        data-duracion="${escapeHtml(row.duracion)}">
        <td>${escapeHtml(row.canal)}</td>
        <td>${escapeHtml(dias)}</td>
        <td>${escapeHtml(inicio)}</td>
        <td>${escapeHtml(formatDuration(row.duracion))}</td>
        <td>
          <div class="action-buttons">
            <button type="button" class="deleteBtn">
              Eliminar
            </button>
          </div>
        </td>
      </tr>
    `;
  }

  function renderRows(rows)
  {
    todoBody.innerHTML = '';

    if (rows.length === 0)
    {
      todoBody.innerHTML = `
        <tr>
          <td colspan="5">No hay riegos programados</td>
        </tr>
      `;

      return;
    }

    rows.forEach((row) =>
    {
      todoBody.insertAdjacentHTML(
        'beforeend',
        existingRowTemplate(row)
      );
    });
  }

  function getSelectedDows()
  {
    return Array.from(dowCheckboxes)
      .filter((checkbox) => checkbox.checked)
      .map((checkbox) => Number(checkbox.value));
  }

  function clearForm()
  {
    document.getElementById('canalInput').value = '';
    document.getElementById('horaInput').value = '';
    document.getElementById('duracionInput').value = '';

    dowCheckboxes.forEach((checkbox) =>
    {
      checkbox.checked = false;
    });
  }

  async function loadTodo(alias)
  {
    if (!alias)
    {
      todoBody.innerHTML = `
        <tr>
          <td colspan="5">Selecciona un dispositivo</td>
        </tr>
      `;

      return;
    }

    todoContainer.classList.add('loading');
    setMessage('Cargando...');

    try
    {
      const response = await fetch(
        '/panel/data/' + encodeURIComponent(alias),
        {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        }
      );

      if (!response.ok)
      {
        throw new Error('No se pudo cargar la programación');
      }

      const rows = await response.json();

      renderRows(rows);
      setMessage('');
    }
    catch (error)
    {
      todoBody.innerHTML = `
        <tr>
          <td colspan="5">Error cargando datos</td>
        </tr>
      `;

      setMessage(error.message, 'error');
    }
    finally
    {
      todoContainer.classList.remove('loading');
    }
  }

  function loadRiegoInForm(tr)
  {
    document.getElementById('canalInput').value =
      tr.dataset.canal;

    const hora = String(tr.dataset.hora).padStart(2, '0');
    const minuto = String(tr.dataset.minuto).padStart(2, '0');

    document.getElementById('horaInput').value =
      hora + ':' + minuto;

    document.getElementById('duracionInput').value =
      tr.dataset.duracion;

    const selectedDows = tr.dataset.dows
      .split(',')
      .filter((dow) => dow !== '');

    dowCheckboxes.forEach((checkbox) =>
    {
      checkbox.checked = selectedDows.includes(checkbox.value);
    });
  }

  async function createRiego()
  {
    const alias = aliasSelect.value;
    const canal = document.getElementById('canalInput').value;
    const horaCompleta = document.getElementById('horaInput').value;
    const duracion = document.getElementById('duracionInput').value;
    const dows = getSelectedDows();

    if (!alias)
    {
      setMessage('Selecciona un dispositivo', 'error');
      return;
    }

    if (!canal || !horaCompleta || !duracion)
    {
      setMessage(
        'Completa canal, hora de encendido y tiempo de riego',
        'error'
      );

      return;
    }

    if (dows.length === 0)
    {
      setMessage(
        'Debes seleccionar al menos un día de la semana',
        'error'
      );

      return;
    }

    const partesHora = horaCompleta.split(':');

    const payload = {
      alias: alias,
      canal: Number(canal),
      hora: Number(partesHora[0]),
      minuto: Number(partesHora[1]),
      duracion: Number(duracion),
      dow: dows
    };

    createBtn.disabled = true;
    setMessage('Guardando...');

    try
    {
      const response = await fetch('/panel/create', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (!response.ok || !data.ok)
      {
        throw new Error(
          data.message || 'No se pudo crear el riego'
        );
      }

      clearForm();

      await loadTodo(alias);

      setMessage(data.message, 'ok');
    }
    catch (error)
    {
      setMessage(error.message, 'error');
    }
    finally
    {
      createBtn.disabled = false;
    }
  }

  async function deleteRiego(tr, loadForm)
  {
    const onIds = (tr.dataset.onIds || '')
      .split(',')
      .filter((id) => id !== '')
      .map(Number);

    const offIds = (tr.dataset.offIds || '')
      .split(',')
      .filter((id) => id !== '')
      .map(Number);

    if (loadForm)
    {
      loadRiegoInForm(tr);
    }

    tr.classList.add('loading');
    setMessage('Eliminando...');

    try
    {
      const response = await fetch('/panel/delete-riego', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          on_ids: onIds,
          off_ids: offIds
        })
      });

      const data = await response.json();

      if (!response.ok || !data.ok)
      {
        throw new Error(
          data.message || 'No se pudo eliminar'
        );
      }

      await loadTodo(aliasSelect.value);

      setMessage(
        loadForm
          ? 'Riego eliminado. Modifica los datos y pulsa Guardar riego.'
          : 'Riego eliminado',
        'ok'
      );
    }
    catch (error)
    {
      setMessage(error.message, 'error');
      tr.classList.remove('loading');
    }
  }

  aliasSelect.addEventListener('change', function()
  {
    clearForm();
    loadTodo(this.value);
  });

  selectAllBtn.addEventListener('click', function()
  {
    dowCheckboxes.forEach((checkbox) =>
    {
      checkbox.checked = true;
    });
  });

  clearAllBtn.addEventListener('click', function()
  {
    dowCheckboxes.forEach((checkbox) =>
    {
      checkbox.checked = false;
    });
  });

  createBtn.addEventListener('click', createRiego);

  todoBody.addEventListener('click', function(event)
  {
    const deleteButton = event.target.closest('.deleteBtn');

    if (!deleteButton)
    {
      return;
    }

    const tr = deleteButton.closest('tr');

    deleteRiego(tr, true);
  });
</script>

<script>
  setInterval(function()
  {
    const active = document.activeElement;

    if (!active)
    {
      window.location.reload();
      return;
    }

    const tagName = active.tagName.toLowerCase();

    if (
      tagName === 'input'
      || tagName === 'select'
      || tagName === 'textarea'
    )
    {
      return;
    }

    window.location.reload();
  }, 60000);
</script>
</body>
</html>