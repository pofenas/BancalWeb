<?php

namespace App\Controllers;

use App\Models\Acciones;
use App\Models\DispositivosModel;
use App\Models\StatusModel;
use CodeIgniter\HTTP\ResponseInterface;

class Api extends BaseController
{
  

  public function accion(): ResponseInterface
    {
      $mac = $this->request->getGet('mac');

      if (empty($mac))
      {
        return $this->response
          ->setStatusCode(400)
          ->setContentType('text/plain')
          ->setBody('null');
      }

      $acciones = new Acciones();
      $resultado = $acciones->getAccion($mac);

      return $this->response
        ->setContentType('text/plain')
        ->setBody($resultado ?? 'null');
    }

    public function status(string $mac, int $canal): ResponseInterface
    {
      $status = strtoupper(trim((string) $this->request->getRawInputVar('status')));

      if ($mac === '' || $canal < 1 || !in_array($status, ['ON', 'OFF'], true))
      {
        return $this->response
          ->setStatusCode(400)
          ->setContentType('text/plain')
          ->setBody('ERROR');
      }

      $dispositivosModel = new DispositivosModel();
      $statusModel = new StatusModel();

      $dispositivo = $dispositivosModel->where('mac', $mac)->first();

      if (!$dispositivo)
      {
        return $this->response
          ->setStatusCode(404)
          ->setContentType('text/plain')
          ->setBody('ERROR');
      }

      $ok = $statusModel->upsertStatus($dispositivo['alias'], $canal, $status);

      if (!$ok)
      {
        return $this->response
          ->setStatusCode(500)
          ->setContentType('text/plain')
          ->setBody('ERROR');
      }

      return $this->response
        ->setContentType('text/plain')
        ->setBody('OK');
    }
  public function getSchedule(string $mac): ResponseInterface
    {
      $mac = trim($mac);

      if ($mac === '')
      {
        return $this->response
          ->setStatusCode(400)
          ->setContentType('text/plain')
          ->setBody('');
      }

      $dispositivos = new \App\Models\DispositivosModel();
      $dispositivo = $dispositivos->where('mac', $mac)->first();

      if (!$dispositivo)
      {
        return $this->response
          ->setContentType('text/plain')
          ->setBody('');
      }

      // Actualizar lastcon
      $db = \Config\Database::connect();
      $db->table('dispositivos')
        ->where('id', $dispositivo['id'])
        ->update(['lastcon' => date('Y-m-d H:i:s')]);

      $dow = (int) date('w');
      $db = \Config\Database::connect();

      $rows = $db->table('todo')
        ->select('canal, hora, minuto, turn AS accion')
        ->where('alias', $dispositivo['alias'])
        ->groupStart()
        ->where('dow', $dow)
        ->orWhere('dow', 9)
        ->groupEnd()
        ->orderBy('canal', 'ASC')
        ->orderBy('hora', 'ASC')
        ->orderBy('minuto', 'ASC')
        ->get()
        ->getResultArray();

      $lines = [];

      foreach ($rows as $row)
      {
        $lines[] = $row['canal'] . ',' . $row['hora'] . ',' . $row['minuto'] . ',' . $row['accion'];
      }

      return $this->response
        ->setContentType('text/plain')
        ->setBody(implode("\n", $lines));
    }

  public function login(string $mac): ResponseInterface
    {
      $mac = trim($mac);

      if ($mac === '')
      {
        return $this->response
          ->setStatusCode(400)
          ->setContentType('text/plain')
          ->setBody('null');
      }

      $dispositivos = new DispositivosModel();
      $dispositivo = $dispositivos->where('mac', $mac)->first();

      if (!$dispositivo)
      {
        $dispositivos->insert([
          'mac' => $mac,
          'alias' => '',
        ]);

        return $this->response
          ->setContentType('text/plain')
          ->setBody('No configurado');
      }

      return $this->response
        ->setContentType('text/plain')
        ->setBody($dispositivo['alias']);
    }
}