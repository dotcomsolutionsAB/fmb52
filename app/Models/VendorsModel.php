<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorsModel extends Model
{
    //
    protected $table = 't_vendors';  // Replace with your actual table name

    protected $fillable = [
        'jamiat_id', 'vendor_id', 'name', 'company_name', 'group', 'mobile', 'email', 
        'pan_card', 'address_line_1', 'address_line_2', 'city', 'pincode', 'state',
        'bank_name', 'bank_account_no', 'bank_ifsc', 'bank_account_name', 'vpa', 'status'
    ];
}
