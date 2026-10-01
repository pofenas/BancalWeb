<?php

namespace App\Controllers;

use App\Models\DispositivosModel;
use App\Models\StatusModel;
use App\Models\TodoModel;
use CodeIgniter\HTTP\ResponseInterface;

class Panel extends BaseController
{
  protected DispositivosModel $dispositivosModel;
  protected StatusModel $statusModel;
  protected TodoModel $todoModel;

  public function __construct()
    {
      $this->dispositivosModel = new DispositivosModel();
      $this->statusModel = new StatusModel();
      $this->todoModel = new TodoModel();
    }


  public function index(): string
    {
      $dispositivos = $this->dispositivosModel
        ->orderBy('alias', 'ASC')
        ->findAll();

      $status = $this->statusModel
        ->orderBy('alias', 'ASC')
        ->orderBy('canal', 'ASC')
        ->findAll();

      // Cargar lastcon por alias
      $db = \Config\Database::connect();
      $lastconRows = $db->table('dispositivos')
        ->select('alias, lastcon')
        ->orderBy('alias', 'ASC')
        ->get()
        ->getResultArray();

      $lastcon = [];
      foreach ($lastconRows as $row)
      {
        $lastcon[$row['alias']] = $row['lastcon'];
      }

      // Agrupar status por alias
      $statusByAlias = [];
      foreach ($status as $row)
      {
        $alias = $row['alias'];
        if (!isset($statusByAlias[$alias]))
        {
          $statusByAlias[$alias] = [];
        }
        $statusByAlias[$alias][] = $row;
      }

      return view('panel', [
        'dispositivos' => $dispositivos,
        'statusByAlias' => $statusByAlias,
        'lastcon' => $lastcon,
      ]);
    }
  public function todo(string $alias): ResponseInterface
    {
      $rows = $this->todoModel
        ->where('alias', $alias)
        ->orderBy('dow', 'ASC')
        ->orderBy('hora', 'ASC')
        ->orderBy('minuto', 'ASC')
        ->orderBy('canal', 'ASC')
        ->findAll();

      return $this->response->setJSON($rows);
    }

  public function update(int $id): ResponseInterface
    {
      $data = $this->request->getJSON(true);

      if (!$data)
      {
        return $this->response
          ->setStatusCode(400)
          ->setJSON(['ok' => false, 'message' => 'Datos no válidos']);
      }

      $dowValue = (string) ($data['dow'] ?? '9');

      if ($dowValue === 'now')
      {
        $dowValue = (string) (int) date('w');
      }

      $update = [
        'canal' => (int) ($data['canal'] ?? 0),
        'turn' => strtoupper(trim((string) ($data['turn'] ?? ''))),
        'hora' => (int) ($data['hora'] ?? -1),
        'minuto' => (int) ($data['minuto'] ?? -1),
        'dow' => (int) $dowValue,
      ];

      $error = $this->validateTodoData($update, false);

      if ($error !== null)
      {
        return $this->response
          ->setStatusCode(400)
          ->setJSON(['ok' => false, 'message' => $error]);
      }

      $ok = $this->todoModel->update($id, $update);

      return $this->response->setJSON([
        'ok' => (bool) $ok,
      ]);
    }

  public function create(): ResponseInterface
    {
      $data = $this->request->getJSON(true);

      if (!$data)
      {
        return $this->response
          ->setStatusCode(400)
          ->setJSON(['ok' => false, 'message' => 'Datos no válidos']);
      }

      $dowValue = (string) ($data['dow'] ?? '9');

      if ($dowValue === 'now')
      {
        $dowValue = (string) (int) date('w');
      }

      $insert = [
        'alias' => trim((string) ($data['alias'] ?? '')),
        'canal' => (int) ($data['canal'] ?? 0),
        'turn' => strtoupper(trim((string) ($data['turn'] ?? 'OFF'))),
        'hora' => (int) ($data['hora'] ?? 0),
        'minuto' => (int) ($data['minuto'] ?? 0),
        'dow' => (int) $dowValue,
      ];

      $error = $this->validateTodoData($insert, true);

      if ($error !== null)
      {
        return $this->response
          ->setStatusCode(400)
          ->setJSON(['ok' => false, 'message' => $error]);
      }

      $id = $this->todoModel->insert($insert, true);

      if (!$id)
      {
        return $this->response
          ->setStatusCode(500)
          ->setJSON(['ok' => false, 'message' => 'No se pudo crear']);
      }

      $row = $this->todoModel->find($id);

      return $this->response->setJSON([
        'ok' => true,
        'row' => $row,
      ]);
    }

  public function delete(int $id): ResponseInterface
    {
      $ok = $this->todoModel->delete($id);

      return $this->response->setJSON([
        'ok' => (bool) $ok,
      ]);
    }

  protected function validateTodoData(array $data, bool $checkAlias): ?string
    {
      if ($checkAlias && $data['alias'] === '')
      {
        return 'Alias no válido';
      }

      if (!in_array($data['turn'], ['ON', 'OFF'], true))
      {
        return 'Turn no válido';
      }

      if ($data['canal'] < 1)
      {
        return 'Canal no válido';
      }

      if ($data['hora'] < 0 || $data['hora'] > 23)
      {
        return 'Hora no válida';
      }

      if ($data['minuto'] < 0 || $data['minuto'] > 59)
      {
        return 'Minuto no válido';
      }

      if (($data['dow'] < 1 || $data['dow'] > 7) && $data['dow'] <>9)
      {
        return 'DOW no válido';
      }

      return null;
    }
}