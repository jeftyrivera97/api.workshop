<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Planilla;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Arr;

class PlanillaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $modulo = "Planillas";
        $chartLabel = "Planillas del Mes";
        $mes= now()->format('F');
        $numMes = now()->format('m');
        $mes= $this->obtenerMes($numMes);
        $year = now()->format('y');

        if($numMes== 1){
            $numMesA=12;
            $yearA= $year-1;
        }else{
            $numMesA = $numMes-1;
            $yearA= $year-1;
        }

        $numMes= '01';
        $fecha_inicial="$year-$numMes-01";
        $fecha_final="$year-$numMes-31";

        $enero=Planilla::where('fecha', '>=', "$year-01-01")->where('fecha', '<=', "$year-01-31")->sum('total');
        $febrero=Planilla::where('fecha', '>=', "$year-02-01")->where('fecha', '<=', "$year-02-31")->sum('total');
        $marzo=Planilla::where('fecha', '>=', "$year-03-01")->where('fecha', '<=',"$year-03-31")->sum('total');
        $abril=Planilla::where('fecha', '>=', "$year-04-01")->where('fecha', '<=', "$year-04-31")->sum('total');
        $mayo=Planilla::where('fecha', '>=', "$year-05-01")->where('fecha', '<=', "$year-05-31")->sum('total');
        $junio=Planilla::where('fecha', '>=', "$year-06-01")->where('fecha', '<=', "$year-06-31")->sum('total');
        $julio=Planilla::where('fecha', '>=', "$year-07-01")->where('fecha', '<=',  "$year-07-31")->sum('total');
        $agosto=Planilla::where('fecha', '>=', "$year-08-01")->where('fecha', '<=', "$year-08-31")->sum('total');
        $septiembre=Planilla::where('fecha', '>=', "$year-09-01")->where('fecha', '<=',"$year-09-31")->sum('total');
        $octubre=Planilla::where('fecha', '>=', "$year-10-01")->where('fecha', '<=', "$year-10-31")->sum('total');
        $noviembre=Planilla::where('fecha', '>=', "$year-11-01")->where('fecha', '<=', "$year-11-31")->sum('total');
        $diciembre=Planilla::where('fecha', '>=', "$year-12-01")->where('fecha', '<=', "$year-12-31")->sum('total');


        $dataMes = collect([

            ['descripcion' => 'Enero', 'total' => $enero],
            ['descripcion' => 'Febrero', 'total' => $febrero],
            ['descripcion' => 'Marzo', 'total' => $marzo],
            ['descripcion' => 'Abril', 'total' => $abril],
            ['descripcion' => 'Mayo', 'total' => $mayo],
            ['descripcion' => 'Junio', 'total' => $junio],
            ['descripcion' => 'Julio', 'total' => $julio],
            ['descripcion' => 'Agosto', 'total' => $agosto],
            ['descripcion' => 'Septiembre', 'total' => $septiembre],
            ['descripcion' => 'Octubre', 'total' => $octubre],
            ['descripcion' => 'Noviembre', 'total' => $noviembre],
            ['descripcion' => 'Diciembre', 'total' => $diciembre],

        ]);

        $dataMes->toJson();

        $periodo1=Planilla::where('fecha', '>=', "$year-$numMes-01")->where('fecha', '<=', "$year-$numMes-10")->sum('total');
        $periodo2=Planilla::where('fecha', '>=', "$year-$numMes-11")->where('fecha', '<=', "$year-$numMes-20")->sum('total');
        $periodo3=Planilla::where('fecha', '>=', "$year-$numMes-21")->where('fecha', '<=', "$year-$numMes-31")->sum('total');

        $dataSemanal = collect([
            ['descripcion' => "01 $mes al 10 $mes", 'total' => $periodo1],
            ['descripcion' => "11 $mes al 20 $mes", 'total' => $periodo2],
            ['descripcion' => "21 $mes al 31 $mes", 'total' => $periodo3],
        ]);

        $dataSemanal->toJson();

        $data = Planilla::with('empleado')->where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->orderBy('fecha')->get();
        $totalMes=  Planilla::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->sum('total');
        $totalAnual=Planilla::where('fecha', '>=',"$year-01-01")->where('fecha', '<=', "$year-12-31")->sum('total');
        $mesAnterior=Planilla::where('fecha', '>=', "$yearA-$numMesA-01")->where('fecha', '<=', "$yearA-$numMesA-31")->sum('total');
        $diferencia= $totalMes-$mesAnterior;
        if($mesAnterior<$totalMes){
            $diferencia = number_format($diferencia, 2);
            $diferencia= "L. +$diferencia ";
        }else{
            $diferencia = number_format($diferencia, 2);
            $diferencia= "L. $diferencia ";
        }

        $promedioSemanal= $totalMes/4;
        $promedioSemanal = number_format($promedioSemanal, 2);
        $promedioSemanal= "L. $promedioSemanal ";
        $totalMes = number_format($totalMes, 2);
        $totalMes= "L. $totalMes ";

        $totalAnual = number_format($totalAnual, 2);
        $totalAnual= "L. $totalAnual ";

        $dataCategorias=[];
        $empleados= Empleado::all();
        $contador= count($empleados);

        for($i=0; $i<$contador; $i++)
        {
           $id= $empleados[$i]->id;
           $d= $empleados[$i]->descripcion;

           $total= Planilla::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->sum('total');
           if($totalPlanilla= Planilla::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->where('id_empleado',$id)->sum('total')){
            $p= ($totalPlanilla*100)/$total;
            $p = number_format($p, 2, '.', '');
            $descripcion= "$d | $p%";
            $dataCategorias[$i] = Arr::add(['descripcion' => $descripcion], 'total', $totalPlanilla);
           }
        }
        $columns = array_column($dataCategorias, 'total');
        array_multisort($columns, SORT_DESC, $dataCategorias);

        $titlePeriodos ="$modulo por Periodos";
        $titleCategorias ="$modulo por Empleados";
        $titleGrafica ="$modulo por Mes del $year";

        $headers = array(
            1 => "Descripcion",
            2 => "Total",
        );

        return Inertia::render('Planillas/Index', compact('year','diferencia','dataSemanal','totalAnual',
        'mes','dataMes','totalMes','dataCategorias','promedioSemanal','data','modulo',
        'chartLabel','titlePeriodos','titleCategorias','titleGrafica','headers'));
    }

    public static function obtenerMes($n){
        switch ($n) {
            case '01':
                $nombre="Enero";
                break;
                case '02':
                $nombre="Febrero";
                break;
                case '03':
                $nombre="Marzo";
                break;
                case '04':
                $nombre="Abril";
                break;
                case '05':
                $nombre="Mayo";
                break;
                case '06':
                $nombre="Junio";
                break;
                case '07':
                $nombre="Julio";
                break;
                case '08':
                $nombre="Agosto";
                break;
                case '09':
                $nombre="Septiembre";
                break;
                case '10':
                $nombre="Octubre";
                break;
                case '11':
                $nombre="Noviembre";
                break;
                case '12':
                $nombre="Diciembre";
                break;

            default:
                # code...
                break;
        }
        return $nombre;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Planilla  $planilla
     * @return \Illuminate\Http\Response
     */
    public function show(Planilla $planilla)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Planilla  $planilla
     * @return \Illuminate\Http\Response
     */
    public function edit(Planilla $planilla)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Planilla  $planilla
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Planilla $planilla)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Planilla  $planilla
     * @return \Illuminate\Http\Response
     */
    public function destroy(Planilla $planilla)
    {
        //
    }
}
