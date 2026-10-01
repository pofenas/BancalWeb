<?php

namespace App\Models;

use CodeIgniter\Model;

class TodoModel extends Model
{
  protected $table = 'todo';
  protected $primaryKey = 'id';
  protected $returnType = 'array';
  protected $allowedFields = ['alias', 'canal', 'turn', 'hora', 'minuto', 'dow'];
}