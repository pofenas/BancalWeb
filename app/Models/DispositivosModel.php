<?php

namespace App\Models;

use CodeIgniter\Model;

class DispositivosModel extends Model
{
  protected $table = 'dispositivos';
  protected $primaryKey = 'id';
  protected $returnType = 'array';
  protected $allowedFields = ['mac', 'alias', 'lastcon'];
}