<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultor;
use App\Models\Tramite;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class OscController extends Controller
{
    public function asignar()
    {
        $tramites = Tramite::where('estado', '=', 1)->get();
        $users = User::role('RevisorOSC')->pluck('name', 'id');
        return view('admin.osc.asignar', compact('tramites', 'users'));
    }

    public function aceptar(Request $request)
    {
        $tramite = Tramite::find($request->tramite_id);
        $tramite->user_id = $request->user;
        $tramite->estado = 2;
        $tramite->save();
        $user = User::find($request->user);
        return redirect()->route('admin.osc.asignar')
        ->with('info', 'Se asigno correctamente el trámite a ' . $user->name );
    }

    public function revisar()
    {
        $tramites = Tramite::select('*')
                ->where('user_id', '=', auth()->user()->id)
                ->where('revosc', '=', 0)
                ->paginate();
        return view('admin.osc.revisar', compact('tramites'));
    }

    public function revisarosc()

    {
        $tramites = Tramite::whereNotNull('resosc')->get();
        return view('admin.osc.revisarosc', compact('tramites'));
    }

    public function download($id)
    {
        $tramite = Tramite::find($id);
        return Storage::download($tramite->documento);
    }

    public function download1($id)
    {
        $tramite = Tramite::find($id);
        return Storage::download($tramite->resosc);
    }

    public function finalizar($id)
    {
        $tramite = Tramite::find($id);
        $revisor = User::where('id', '=', $tramite->user_id)->first();
        $consultor = Consultor::where('id', '=', $tramite->consultor_id)->first();
        $nombre = User::where('id', '=', $consultor->user_id)->first();
        return view('admin.osc.finalizar', compact('tramite', 'consultor', 'nombre'));

    }

    public function store(Request $request)
    {
        $tramite = Tramite::find($request->id);

        $observacion = Storage::put('observaciones', $request->file('observacion'));
        $tramite->obserosc = $observacion;
        $resolucion = Storage::put('resoluciones', $request->file('resolucion'));
        $tramite->resosc = $resolucion;
        $tramite->revosc = $request->status;
        if($request->status == 2){
            $tramite->estado = 4;
        } elseif($request->status == 1){
            $tramite->estado = 3;
        } elseif($request->status == 3){
            $tramite->estado = 9;
        }

        $tramite->save();
        return redirect()->route('admin.osc.revisor')
        ->with('info', 'Se envio la información correctamente ' );
    }

    public function estado()
    {

        $tramites = Tramite::orderBy('id', 'desc')->where('estado', '<', 4)->get();
        $users = User::all();
        return view('admin.osc.estado', compact('tramites', 'users'));

    }
}
