<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportEvidence extends Model
{
    protected $table = 'report_evidences';
    protected $guarded = ['id'];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
