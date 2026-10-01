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

      <div class="card" id="todoContainer">
        <div class="table-container">
          <table id="todoTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Canal</th>
                <th>Turn</th>
                <th>Hora</th>
                <th>Minuto</th>
                <th>DOW</th>
                <th>Eliminar</th>
              </tr>
            </thead>
            <tbody id="todoBody">
              <tr>
                <td colspan="7">Selecciona un dispositivo</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    const aliasSelect = document.getElementById('aliasSelect');
    const todoBody = document.getElementById('todoBody');
    const todoContainer = document.getElementById('todoContainer');
    const msg = document.getElementById('msg');

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

    function dowOptionsHtml(selectedValue)
    {
      const options = [
        { value: '0', label: 'domingo' },
        { value: '1', label: 'lunes' },
        { value: '2', label: 'martes' },
        { value: '3', label: 'miércoles' },
        { value: '4', label: 'jueves' },
        { value: '5', label: 'viernes' },
        { value: '6', label: 'sábado' },
        { value: '9', label: 'todos los días' },
        { value: 'now', label: 'ahora' }
      ];

      return options.map(opt => {
        const selected = String(opt.value) === String(selectedValue) ? ' selected' : '';
        return '<option value="' + opt.value + '"' + selected + '>' + opt.label + '</option>';
      }).join('');
    }

    function newRowTemplate()
      {
        return `
          <tr class="new-row" data-new="1">
            <td class="id-cell-muted">nuevo</td>
            <td><input type="number" min="1" class="field" data-field="canal" value=""></td>
            <td>
              <select class="field" data-field="turn">
                <option value="OFF" selected>OFF</option>
                <option value="ON">ON</option>
              </select>
            </td>
            <td><input type="number" min="0" max="23" class="field" data-field="hora" value=""></td>
            <td><input type="number" min="0" max="59" class="field" data-field="minuto" value=""></td>
            <td>
              <select class="field" data-field="dow">
                ${dowOptionsHtml('9')}
              </select>
            </td>
            <td></td>
          </tr>
        `;
      }
    function existingRowTemplate(row)
    {
      return `
        <tr data-id="${row.id}">
          <td>${escapeHtml(row.id)}</td>
          <td><input type="number" min="1" class="field" data-field="canal" value="${escapeHtml(row.canal)}"></td>
          <td>
            <select class="field" data-field="turn">
              <option value="ON" ${row.turn === 'ON' ? 'selected' : ''}>ON</option>
              <option value="OFF" ${row.turn === 'OFF' ? 'selected' : ''}>OFF</option>
            </select>
          </td>
          <td><input type="number" min="0" max="23" class="field" data-field="hora" value="${escapeHtml(row.hora)}"></td>
          <td><input type="number" min="0" max="59" class="field" data-field="minuto" value="${escapeHtml(row.minuto)}"></td>
          <td>
            <select class="field" data-field="dow">
              ${dowOptionsHtml(row.dow)}
            </select>
          </td>
          <td><button type="button" class="deleteBtn">Eliminar</button></td>
        </tr>
      `;
    }

    function appendNewRow()
    {
      todoBody.insertAdjacentHTML('beforeend', newRowTemplate());
    }

    function renderRows(rows)
    {
      todoBody.innerHTML = '';

      rows.forEach((row) => {
        todoBody.insertAdjacentHTML('beforeend', existingRowTemplate(row));
      });

      appendNewRow();
    }

    function getRowPayload(tr)
    {
      const payload = {};

      tr.querySelectorAll('.field').forEach((field) => {
        payload[field.dataset.field] = field.value;
      });

      return payload;
    }

    function newRowHasAnyValue(tr)
    {
      const payload = getRowPayload(tr);

      return Object.values(payload).some((value) => String(value).trim() !== '');
    }

    async function loadTodo(alias)
    {
      if (!alias)
      {
        todoBody.innerHTML = '<tr><td colspan="7">Selecciona un dispositivo</td></tr>';
        return;
      }

      todoContainer.classList.add('loading');
      setMessage('Cargando...');

      try
      {
        const response = await fetch('/panel/data/' + encodeURIComponent(alias), {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

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
        todoBody.innerHTML = '<tr><td colspan="7">Error cargando datos</td></tr>';
        setMessage(error.message, 'error');
      }
      finally
      {
        todoContainer.classList.remove('loading');
      }
    }

    function renderRows(rows)
    {
      todoBody.innerHTML = '';

      rows.forEach((row) => {
        todoBody.insertAdjacentHTML('beforeend', existingRowTemplate(row));
      });

      appendNewRow();
    }

    function getRowPayload(tr)
    {
      const payload = {};

      tr.querySelectorAll('.field').forEach((field) => {
        payload[field.dataset.field] = field.value;
      });

      return payload;
    }

    function newRowHasAnyValue(tr)
    {
      const payload = getRowPayload(tr);

      return Object.values(payload).some((value) => String(value).trim() !== '');
    }

    async function updateRow(tr)
    {
      const id = tr.dataset.id;
      const payload = getRowPayload(tr);

      tr.classList.add('loading');
      setMessage('Actualizando...');

      try
      {
        const response = await fetch('/panel/update/' + encodeURIComponent(id), {
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
          throw new Error(data.message || 'No se pudo actualizar');
        }

        setMessage('Actualizado', 'ok');
      }
      catch (error)
      {
        setMessage(error.message, 'error');
      }
      finally
      {
        tr.classList.remove('loading');
      }
    }

    async function createRow(tr)
    {
      const alias = aliasSelect.value;
      const payload = getRowPayload(tr);

      payload.alias = alias;

      tr.classList.add('loading');
      setMessage('Creando...');

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
          throw new Error(data.message || 'No se pudo crear');
        }

        tr.outerHTML = existingRowTemplate(data.row);
        appendNewRow();
        setMessage('Creado', 'ok');
      }
      catch (error)
      {
        setMessage(error.message, 'error');
        tr.classList.remove('loading');
      }
    }

    async function deleteRow(tr)
    {
      const id = tr.dataset.id;

      tr.classList.add('loading');
      setMessage('Eliminando...');

      try
      {
        const response = await fetch('/panel/delete/' + encodeURIComponent(id), {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const data = await response.json();

        if (!response.ok || !data.ok)
        {
          throw new Error('No se pudo eliminar');
        }

        tr.remove();

        if (!todoBody.querySelector('tr'))
        {
          appendNewRow();
        }

        setMessage('Eliminado', 'ok');
      }
      catch (error)
      {
        setMessage(error.message, 'error');
        tr.classList.remove('loading');
      }
    }

    aliasSelect.addEventListener('change', function()
    {
      loadTodo(this.value);
    });

    todoBody.addEventListener('change', function(event)
    {
      const field = event.target.closest('.field');

      if (!field)
      {
        return;
      }

      const tr = field.closest('tr');

      if (tr.dataset.new === '1')
      {
        if (newRowHasAnyValue(tr))
        {
          createRow(tr);
        }

        return;
      }

      updateRow(tr);
    });

    todoBody.addEventListener('click', function(event)
    {
      const button = event.target.closest('.deleteBtn');

      if (!button)
      {
        return;
      }

      const tr = button.closest('tr');
      deleteRow(tr);
    });
  </script>
  <script>
   // Auto-refresco cada 60 segundos, solo si no hay foco en inputs del formulario
   setInterval(function()
    {
      const active = document.activeElement;
      if (!active)
      {
        window.location.reload();
        return;
      }

      const tagName = active.tagName.toLowerCase();
      if (tagName === 'input' || tagName === 'select' || tagName === 'textarea')
      {
        // No recargar si el foco está en un campo de edición
        return;
      }

      window.location.reload();
    }, 60000);
</script>
</body>
</html>