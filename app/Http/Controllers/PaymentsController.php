<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

use App\Models\UploadModel;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\Request;
use App\Models\CounterModel;
use App\Models\AdvanceReceiptModel;
use App\Models\ExpenseModel;
use App\Models\PaymentsModel;
use App\Models\ReceiptsModel;
use App\Models\User;
use App\Models\WhatsAppQueue;
use App\Models\WhatsappQueueModel;
use Auth;
use App\Models\CurrencyModel;

class AccountsController extends Controller
{

}