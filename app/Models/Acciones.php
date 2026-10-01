<?php

namespace App\Models;

use CodeIgniter\Model;

class Acciones extends Model
{
  public function getAccion(string $mac): ?string
    {
      $hora = (int) date('G');
      $minuto = (int) date('i');
      $dow = (int) date('w');

      $db = \Config\Database::connect();

      $builder = $db->table('todo t');
      $builder->select('t.canal, t.turn');
      $builder->join('dispositivos d', 'd.alias = t.alias');
      $builder->where('d.mac', $mac);
      $builder->where('t.hora', $hora);
      $builder->where('t.minuto', $minuto);
      $builder->groupStart();
      $builder->where('t.dow', $dow);
      $builder->orWhere('t.dow', 0);
      $builder->groupEnd();
      $builder->limit(1);

      $row = $builder->get()->getRow();

      if (!$row)
      {
        return null;
      }

      return 'canal=' . $row->canal . ';' . strtoupper(trim($row->turn));
    }
}