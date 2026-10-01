<?php

namespace App\Models;

use CodeIgniter\Model;

class StatusModel extends Model
{
  protected $table = 'status';
  protected $primaryKey = 'id';
  protected $returnType = 'array';
  protected $allowedFields = ['alias', 'canal', 'status'];

  public function upsertStatus(string $alias, int $canal, string $status): bool
  {
    $row = $this->where('alias', $alias)
      ->where('canal', $canal)
      ->first();

    if ($row)
    {
      return (bool) $this->update($row['id'], [
        'status' => $status,
      ]);
    }

    return (bool) $this->insert([
      'alias' => $alias,
      'canal' => $canal,
      'status' => $status,
    ]);
  }
}