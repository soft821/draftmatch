<?php

namespace App;

class Invoice extends Model
{
    //

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getPendingInvoices(){
        return Invoice::where('status', '=', 'pending')->get();
    }

    public static function getLastSuccessfull(){
        $oldestPending = Invoice::where('status', '=', 'pending')->first();
        return Invoice::where('id', '<', $oldestPending->id)->where('status', '=', 'completed')->first();
    }
}
