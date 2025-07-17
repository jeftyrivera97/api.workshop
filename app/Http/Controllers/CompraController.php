<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompraResource;
use App\Models\Compra;
use App\Models\CompraCategoria;
use App\Models\Proveedor;

use Illuminate\Support\Arr;


class CompraController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexGerencia()
    {
        $modulo = "Compras";
        $chartLabel = "Compras del Mes";
        $mes = now()->format('F');
        $numMes = now()->format('m');
        $mes = $this->obtenerMes($numMes);
        $year = now()->format('y');

        if ($numMes == 1) {
            $numMesA = 12;
            $yearA = $year - 1;
        } else {
            $numMesA = $numMes - 1;
            $yearA = $year - 1;
        }

        $numMes = '01';

        $fecha_inicial = "$year-$numMes-01";
        $fecha_final = "$year-$numMes-31";

        $enero = Compra::where('fecha', '>=', "$year-01-01")->where('fecha', '<=', "$year-01-31")->sum('total');
        $febrero = Compra::where('fecha', '>=', "$year-02-01")->where('fecha', '<=', "$year-02-31")->sum('total');
        $marzo = Compra::where('fecha', '>=', "$year-03-01")->where('fecha', '<=', "$year-03-31")->sum('total');
        $abril = Compra::where('fecha', '>=', "$year-04-01")->where('fecha', '<=', "$year-04-31")->sum('total');
        $mayo = Compra::where('fecha', '>=', "$year-05-01")->where('fecha', '<=', "$year-05-31")->sum('total');
        $junio = Compra::where('fecha', '>=', "$year-06-01")->where('fecha', '<=', "$year-06-31")->sum('total');
        $julio = Compra::where('fecha', '>=', "$year-07-01")->where('fecha', '<=',  "$year-07-31")->sum('total');
        $agosto = Compra::where('fecha', '>=', "$year-08-01")->where('fecha', '<=', "$year-08-31")->sum('total');
        $septiembre = Compra::where('fecha', '>=', "$year-09-01")->where('fecha', '<=', "$year-09-31")->sum('total');
        $octubre = Compra::where('fecha', '>=', "$year-10-01")->where('fecha', '<=', "$year-10-31")->sum('total');
        $noviembre = Compra::where('fecha', '>=', "$year-11-01")->where('fecha', '<=', "$year-11-31")->sum('total');
        $diciembre = Compra::where('fecha', '>=', "$year-12-01")->where('fecha', '<=', "$year-12-31")->sum('total');

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


        $periodo1 = Compra::where('fecha', '>=', "$year-$numMes-01")->where('fecha', '<=', "$year-$numMes-10")->sum('total');
        $periodo2 = Compra::where('fecha', '>=', "$year-$numMes-11")->where('fecha', '<=', "$year-$numMes-20")->sum('total');
        $periodo3 = Compra::where('fecha', '>=', "$year-$numMes-21")->where('fecha', '<=', "$year-$numMes-31")->sum('total');


        $dataSemanal = collect([
            ['descripcion' => "01 $mes al 10 $mes", 'total' => $periodo1],
            ['descripcion' => "11 $mes al 20 $mes", 'total' => $periodo2],
            ['descripcion' => "21 $mes al 31 $mes", 'total' => $periodo3],
        ]);

        $dataSemanal->toJson();


        $data = CompraResource::collection(Compra::where('id_estado', 1)->where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->orderBy('fecha')->get());
        $totalC = Compra::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->sum('total');
        $totalMes =  Compra::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->sum('total');
        $totalAnual = Compra::where('fecha', '>=', "$year-01-01")->where('fecha', '<=', "$year-12-31")->sum('total');
        $totalAnual = number_format($totalAnual, 2);
        $totalAnual = "L. $totalAnual ";

        $mesAnterior = Compra::where('fecha', '>=', "$yearA-$numMesA-01")->where('fecha', '<=', "$yearA-$numMesA-31")->sum('total');
        $diferencia = $totalMes - $mesAnterior;
        if ($mesAnterior < $totalMes) {
            $diferencia = number_format($diferencia, 2);
            $diferencia = "L. +$diferencia ";
        } else {
            $diferencia = number_format($diferencia, 2);
            $diferencia = "L. $diferencia ";
        }


        $promedioSemanal = $totalMes / 4.5;
        $promedioSemanal = number_format($promedioSemanal, 2);
        $promedioSemanal = "L. $promedioSemanal ";
        $totalMes = number_format($totalMes, 2);
        $totalMes = "L. $totalMes ";

        $total = Compra::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->sum('total');

        $dataCategorias = [];
        $categorias = CompraCategoria::all();
        $contador = count($categorias);

        for ($i = 0; $i < $contador; $i++) {
            $id = $categorias[$i]->id;
            $d = $categorias[$i]->descripcion;
            if ($totalCompra = Compra::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->where('id_categoria', $id)->sum('total')) {
                $p = ($totalCompra * 100) / $total;
                $p = number_format($p, 2, '.', '');
                $descripcion = "$d | $p%";
                $dataCategorias[$i] = Arr::add(['descripcion' => $descripcion], 'total', $totalCompra);
            }
        }
        $columns = array_column($dataCategorias, 'total');
        array_multisort($columns, SORT_DESC, $dataCategorias);

        $dataProveedores = [];
        $proveedores = Proveedor::all();
        $contador = count($proveedores);

        for ($i = 0; $i < $contador; $i++) {
            $id = $proveedores[$i]->id;
            $d = $proveedores[$i]->descripcion;
            if ($totalCompra = Compra::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->where('id_proveedor', $id)->sum('total')) {
                $p = ($totalCompra * 100) / $total;
                $p = number_format($p, 2, '.', '');
                $descripcion = "$d | $p%";
                $dataProveedores[$i] = Arr::add(['descripcion' => $descripcion], 'total', $totalCompra);
            }
        }
        $columns = array_column($dataProveedores, 'total');
        array_multisort($columns, SORT_DESC, $dataProveedores);

        $titleProveedores = "$modulo por Proveedores";
        $titlePeriodos = "$modulo por Periodos";
        $titleCategorias = "$modulo por Categorias";
        $titleGrafica = "$modulo por Mes del $year";

        $headers = array(
            1 => "Descripcion",
            2 => "Total",
        );



        // return Inertia::render('Compras/Index', compact('dataProveedores','year','diferencia','totalAnual','dataSemanal','mes',
        // 'dataMes','data','totalMes','dataCategorias','promedioSemanal','modulo','chartLabel',
        // 'titleProveedores','headers','titlePeriodos','titleCategorias','titleGrafica'));

        return response()->json([
            'dataProveedores' => $dataProveedores,
            'year' => $year,
            'diferencia' => $diferencia,
            'totalAnual' => $totalAnual,
            'dataSemanal' => $dataSemanal,
            'mes' => $mes,
            'dataMes' => $dataMes,
            'data' => $data,
            'totalMes' => $totalMes,
            'dataCategorias' => $dataCategorias,
            'promedioSemanal' => $promedioSemanal,
            'modulo' => $modulo,
            'chartLabel' => $chartLabel,
            'titleProveedores' => $titleProveedores,
            'headers' => $headers,
            'titlePeriodos' => $titlePeriodos,
            'titleCategorias' => $titleCategorias,
            'titleGrafica' => $titleGrafica
        ]);
    }

    public static function obtenerMes($n)
    {
        switch ($n) {
            case '01':
                $nombre = "Enero";
                break;
            case '02':
                $nombre = "Febrero";
                break;
            case '03':
                $nombre = "Marzo";
                break;
            case '04':
                $nombre = "Abril";
                break;
            case '05':
                $nombre = "Mayo";
                break;
            case '06':
                $nombre = "Junio";
                break;
            case '07':
                $nombre = "Julio";
                break;
            case '08':
                $nombre = "Agosto";
                break;
            case '09':
                $nombre = "Septiembre";
                break;
            case '10':
                $nombre = "Octubre";
                break;
            case '11':
                $nombre = "Noviembre";
                break;
            case '12':
                $nombre = "Diciembre";
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
}
