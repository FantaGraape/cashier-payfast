<?php

namespace Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Model;
use EllisSystems\Payfast\Billable;

class User extends Model
{
    use Billable;

    protected $guarded = [];
}
