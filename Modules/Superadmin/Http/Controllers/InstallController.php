<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

use App\System;

class InstallController extends Controller
{
    public function __construct(){
        $this->module_name = 'superadmin';
    }

    /**
     * Install
     * @return Response
     */
    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $this->installSettings();
        
        //Check if installed or not.
        $is_installed = System::getProperty($this->module_name . '_version');
        if(empty($is_installed)){
            DB::statement('SET default_storage_engine=INNODB;');
            Artisan::call('migrate', ["--force"=> true]);
        }

        $output = ['success' => 1,
                    'msg' => 'Superadmin module installed succesfully'
                ];
        return redirect()->action('\Modules\Superadmin\Http\Controllers\SuperadminController@index')
            ->with('status', $output);
    }

    /**
     * Initialize all install functions
     *
     */
    private function installSettings()
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
    }
}
