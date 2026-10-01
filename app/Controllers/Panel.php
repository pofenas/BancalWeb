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
        ->orderBy('canal', 'ASC')
        ->orderBy('hora', 'ASC')
        ->orderBy('minuto', 'ASC')
        ->orderBy('turn', 'ASC')
        ->findAll();

      $riegos = [];

      foreach ($rows as $row)
      {
        if ($row['turn'] !== 'ON')
        {
          continue;
        }

        $clave = implode('|', [
          $row['canal'],
          $row['hora'],
          $row['minuto'],
        ]);

        $off = $this->findOffRow(
          $rows,
          $row['canal'],
          $row['dow'],
          $row['hora'],
          $row['minuto']
        );

        if ($off === null)
        {
          continue;
        }

        if (!isset($riegos[$clave]))
        {
          $riegos[$clave] = [
            'canal' => (int) $row['canal'],
            'hora' => (int) $row['hora'],
            'minuto' => (int) $row['minuto'],
            'fin_hora' => (int) $off['hora'],
            'fin_minuto' => (int) $off['minuto'],
            'duracion' => $this->calculateDuration(
              (int) $row['hora'],
              (int) $row['minuto'],
              (int) $off['hora'],
              (int) $off['minuto']
            ),
            'dows' => [],
            'on_ids' => [],
            'off_ids' => [],
          ];
        }

        $riegos[$clave]['dows'][] = (int) $row['dow'];
        $riegos[$clave]['on_ids'][] = (int) $row['id'];
        $riegos[$clave]['off_ids'][] = (int) $off['id'];
      }

      $riegos = array_values($riegos);

      usort($riegos, function (array $a, array $b): int
      {
        if ($a['canal'] !== $b['canal'])
        {
          return $a['canal'] <=> $b['canal'];
        }

        $horaA = ($a['hora'] * 60) + $a['minuto'];
        $horaB = ($b['hora'] * 60) + $b['minuto'];

        return $horaA <=> $horaB;
      });

      return $this->response->setJSON($riegos);
    }

  public function create(): ResponseInterface
  {
    $data = $this->request->getJSON(true);

    if (!$data)
    {
      return $this->response
        ->setStatusCode(400)
        ->setJSON([
          'ok' => false,
          'message' => 'Datos no válidos',
        ]);
    }

    $alias = trim((string) ($data['alias'] ?? ''));
    $canal = (int) ($data['canal'] ?? 0);
    $hora = (int) ($data['hora'] ?? -1);
    $minuto = (int) ($data['minuto'] ?? -1);
    $duracion = (int) ($data['duracion'] ?? 0);
    $dows = $data['dow'] ?? [];

    if (!is_array($dows))
    {
      $dows = [$dows];
    }

    $dows = array_values(array_unique(array_map('intval', $dows)));
    sort($dows);

    if ($alias === '')
    {
      return $this->errorResponse('Alias no válido');
    }

    if ($canal < 1)
    {
      return $this->errorResponse('Canal no válido');
    }

    if ($hora < 0 || $hora > 23)
    {
      return $this->errorResponse('Hora no válida');
    }

    if ($minuto < 0 || $minuto > 59)
    {
      return $this->errorResponse('Minuto no válido');
    }

    if ($duracion <= 0)
    {
      return $this->errorResponse('El tiempo de riego debe ser mayor que cero');
    }

    if (count($dows) === 0)
    {
      return $this->errorResponse(
        'Debes seleccionar al menos un día de la semana'
      );
    }

    foreach ($dows as $dow)
    {
      if ($dow < 0 || $dow > 6)
      {
        return $this->errorResponse('Día de la semana no válido');
      }
    }

    $inicio = ($hora * 60) + $minuto;
    $finCalculado = $inicio + $duracion;
    $avisoMedianoche = false;

    if ($finCalculado >= 1440)
    {
      $fin = 1440;
      $finHora = 0;
      $finMinuto = 0;
      $avisoMedianoche = true;
    }
    else
    {
      $fin = $finCalculado;
      $finHora = intdiv($fin, 60);
      $finMinuto = $fin % 60;
    }

    $db = \Config\Database::connect();
    $db->transBegin();

    foreach ($dows as $dow)
    {
      $solapado = $this->inicioSolapado(
        $alias,
        $canal,
        $dow,
        $inicio,
        $db
      );

      if ($solapado)
      {
        $db->transRollback();

        return $this->errorResponse(
          'La hora de inicio se solapa con otro riego existente para este canal y día'
        );
      }

      $this->todoModel->insert([
        'alias' => $alias,
        'canal' => $canal,
        'turn' => 'ON',
        'hora' => $hora,
        'minuto' => $minuto,
        'dow' => $dow,
      ]);

      $this->todoModel->insert([
        'alias' => $alias,
        'canal' => $canal,
        'turn' => 'OFF',
        'hora' => $finHora,
        'minuto' => $finMinuto,
        'dow' => $dow,
      ]);
    }

    if ($db->transStatus() === false)
    {
      $db->transRollback();

      return $this->errorResponse(
        'No se pudo guardar la programación',
        500
      );
    }

    $db->transCommit();

    $message = 'Riego creado';

    if ($avisoMedianoche)
    {
      $message .= '. La hora de apagado ha quedado establecida a medianoche. '
        . 'Si quieres continuar el riego debes iniciar otro en el siguiente día.';
    }

    return $this->response->setJSON([
      'ok' => true,
      'message' => $message,
    ]);
  }

  public function delete(int $onId, int $offId): ResponseInterface
    {
      $db = \Config\Database::connect();

      $db->transBegin();

      $this->todoModel->delete($onId);
      $this->todoModel->delete($offId);

      if ($db->transStatus() === false)
      {
        $db->transRollback();

        return $this->errorResponse(
          'No se pudo eliminar el riego',
          500
        );
      }

      $db->transCommit();

      return $this->response->setJSON([
        'ok' => true,
      ]);
    }

  protected function inicioSolapado(
    string $alias,
    int $canal,
    int $dow,
    int $inicioNuevo,
    object $db
  ): bool
  {
    $rows = $this->todoModel
      ->where('alias', $alias)
      ->where('canal', $canal)
      ->where('dow', $dow)
      ->orderBy('hora', 'ASC')
      ->orderBy('minuto', 'ASC')
      ->findAll();

    $inicioExistente = null;

    foreach ($rows as $row)
    {
      $minutos = ((int) $row['hora'] * 60) + (int) $row['minuto'];

      if ($row['turn'] === 'ON')
      {
        $inicioExistente = $minutos;
      }
      elseif ($row['turn'] === 'OFF' && $inicioExistente !== null)
      {
        $finExistente = $minutos;

        if (
          $inicioExistente <= $inicioNuevo
          && $inicioNuevo < $finExistente
        )
        {
          return true;
        }

        $inicioExistente = null;
      }
    }

    return false;
  }

protected function errorResponse(
      string $message,
      int $statusCode = 400
    ): ResponseInterface
  {
         return $this->response
        ->setStatusCode($statusCode)
        ->setJSON([
          'ok' => false,
          'message' => $message,
        ]);
    }
protected function findOffRow(
    array $rows,
    int $canal,
    int $dow,
    int $horaOn,
    int $minutoOn
  ): ?array
  {
    $inicioOn = ($horaOn * 60) + $minutoOn;

    foreach ($rows as $row)
    {
      if (
        $row['turn'] !== 'OFF'
        || (int) $row['canal'] !== $canal
        || (int) $row['dow'] !== $dow
      )
      {
        continue;
      }

      $inicioOff = ((int) $row['hora'] * 60) + (int) $row['minuto'];

      if ($inicioOff > $inicioOn)
      {
        return $row;
      }

      if ($inicioOff === 0)
      {
        return $row;
      }
    }

    return null;
  }

protected function calculateDuration(
    int $horaInicio,
    int $minutoInicio,
    int $horaFin,
    int $minutoFin
  ): int
  {
    $inicio = ($horaInicio * 60) + $minutoInicio;
    $fin = ($horaFin * 60) + $minutoFin;

    if ($fin === 0)
    {
      return 1440 - $inicio;
    }

    return max(0, $fin - $inicio);
  }
public function deleteRiego(): ResponseInterface
    {
      $data = $this->request->getJSON(true);

      if (!$data)
      {
        return $this->errorResponse('Datos no válidos');
      }

      $onIds = array_map('intval', $data['on_ids'] ?? []);
      $offIds = array_map('intval', $data['off_ids'] ?? []);

      $ids = array_values(array_unique(array_merge($onIds, $offIds)));

      if (count($ids) === 0)
      {
        return $this->errorResponse('No hay riegos para eliminar');
      }

      $db = \Config\Database::connect();
      $db->transBegin();

      foreach ($ids as $id)
      {
        $this->todoModel->delete($id);
      }

      if ($db->transStatus() === false)
      {
        $db->transRollback();

        return $this->errorResponse(
          'No se pudo eliminar el riego',
          500
        );
      }

      $db->transCommit();

      return $this->response->setJSON([
        'ok' => true,
      ]);
    }
}