<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ingreso;
use App\Models\IngresoCategoria;
use Illuminate\Support\Arr;
use App\Http\Resources\IngresoResource;
use App\Models\IngresoTipo;

class IngresoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $tableHeaders = array(
                1 => "Fecha",
                2 => "Descripcion",
                3 => "Categoria",
                4 => "Total",
            );
            $fechaActual = now();
            $fechaAnterior = $fechaActual->copy()->subMonth();

            $numMes = $fechaActual->format('m');
            $numMesAnterior = $fechaAnterior->format('m');
            $year = $fechaActual->format('Y');
            $yearAnterior = $fechaAnterior->format('Y');

            $fecha_inicial = "{$year}-{$numMes}-01";
            $fecha_final = $fechaActual->endOfMonth()->format('Y-m-d');
            $fecha_inicial_anterior = "{$yearAnterior}-{$numMesAnterior}-01";
            $fecha_final_anterior = $fechaAnterior->endOfMonth()->format('Y-m-d');

            $moduleName = "ingreso";
            $moduleTitle = "Ingresos";

            $dataGraficaMes = $this->graficaMesActual($year);

            $registrosMesActual = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])->where('id_estado', 1)->get();

            $totalMes = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])->sum('total');
            $totalMes = "L. " . number_format($totalMes, 2, '.', ',');

            $totalMesAnterior = Ingreso::whereBetween('fecha', [$fecha_inicial_anterior, $fecha_final_anterior])->sum('total');
            $totalMesAnterior = "L. " . number_format($totalMesAnterior, 2, '.', ',');

            $totalAnual = Ingreso::where('fecha', '>=', "$year-01-01")->where('fecha', '<=', "$year-12-31")->sum('total');
            $totalAnual = "L. " . number_format($totalAnual, 2, '.', ',');

            $tiposCategorias = $this->tiposMes($year, $numMes);
            $categoriasMes = $this->categoriasMes($year, $numMes);

            $data = IngresoResource::collection(
                Ingreso::with(['categoria', 'estado', 'usuario'])
                    ->where('id_estado', 1)
                    ->whereBetween('fecha', [$fecha_inicial, $fecha_final])
                    ->orderBy('fecha', 'desc')
                    ->paginate(10)
            )
                ->additional([
                    'tableHeaders' => $tableHeaders,
                    'contador' => $registrosMesActual->count(),
                    'moduleName' => $moduleName,
                    'moduleTitle' => $moduleTitle,
                    'totalMes' => $totalMes,
                    'totalAnual' => $totalAnual,
                    'dataGraficaMes' => $dataGraficaMes,
                    'totalMesAnterior' => $totalMesAnterior,
                    'tiposMes' => $tiposCategorias,
                    'categoriasMes' => $categoriasMes,
                ]);

            return $data;
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'Error al obtener los ingresos',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function graficaMesActual($year)
    {
        $enero = Ingreso::where('fecha', '>=', "$year-01-01")->where('fecha', '<=', "$year-01-31")->sum('total');
        $febrero = Ingreso::where('fecha', '>=', "$year-02-01")->where('fecha', '<=', "$year-02-31")->sum('total');
        $marzo = Ingreso::where('fecha', '>=', "$year-03-01")->where('fecha', '<=', "$year-03-31")->sum('total');
        $abril = Ingreso::where('fecha', '>=', "$year-04-01")->where('fecha', '<=', "$year-04-31")->sum('total');
        $mayo = Ingreso::where('fecha', '>=', "$year-05-01")->where('fecha', '<=', "$year-05-31")->sum('total');
        $junio = Ingreso::where('fecha', '>=', "$year-06-01")->where('fecha', '<=', "$year-06-31")->sum('total');
        $julio = Ingreso::where('fecha', '>=', "$year-07-01")->where('fecha', '<=',  "$year-07-31")->sum('total');
        $agosto = Ingreso::where('fecha', '>=', "$year-08-01")->where('fecha', '<=', "$year-08-31")->sum('total');
        $septiembre = Ingreso::where('fecha', '>=', "$year-09-01")->where('fecha', '<=', "$year-09-31")->sum('total');
        $octubre = Ingreso::where('fecha', '>=', "$year-10-01")->where('fecha', '<=', "$year-10-31")->sum('total');
        $noviembre = Ingreso::where('fecha', '>=', "$year-11-01")->where('fecha', '<=', "$year-11-31")->sum('total');
        $diciembre = Ingreso::where('fecha', '>=', "$year-12-01")->where('fecha', '<=', "$year-12-31")->sum('total');

        $dataGraficaMes = collect([

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

        $dataGraficaMes->toJson();

        return $dataGraficaMes;
    }

    public function categoriasMes($year, $mes)
    {
        $dataCategorias = [];
        $categorias = IngresoCategoria::all();
        $fecha_inicial = "$year-$mes-01";
        $fecha_final = "$year-$mes-31";

        for ($i = 0; $i < count($categorias); $i++) {
            $id = $categorias[$i]->id;
            $descripcion = $categorias[$i]->descripcion;
            $total = Ingreso::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->sum('total');

            if ($totalIngreso = Ingreso::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->where('id_categoria', $id)->sum('total')) {
                $porcentaje = ($totalIngreso * 100) / $total;
                $porcentaje = number_format($porcentaje, 2, '.', '');
                $dataCategorias[$i] = [
                    'descripcion' => $descripcion,
                    'total' => $totalIngreso,
                    'porcentaje' => $porcentaje
                ];
            }
        }
        $columns = array_column($dataCategorias, 'total');
        array_multisort($columns, SORT_DESC, $dataCategorias);

        usort($dataCategorias, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        return $dataCategorias;
    }


    public function tiposMes($year, $mes)
    {

        $fecha_inicial = "$year-$mes-01";
        $fecha_final = "$year-$mes-31";
        $tipos_categorias = [];

        $tipos = IngresoTipo::all();
        $categorias = IngresoCategoria::all();
        $contador = count($tipos);

        for ($i = 0; $i < count($tipos); $i++) {
            $contadorTipo = 0;
            $id_tipo = $tipos[$i]->id;
            $descripcion_tipo = $tipos[$i]->descripcion;

            for ($j = 0; $j < count($categorias); $j++) {

                $id_categoria = $categorias[$j]->id;
                $id_tipo_categoria = $categorias[$j]->id_tipo;

                if ($id_tipo == $id_tipo_categoria) {
                    $totalCategoria = Ingreso::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->where('id_categoria', $id_categoria)->sum('total');
                    $contadorTipo += $totalCategoria;
                }
            }
            $totalMes = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])->sum('total');
            $porcentaje = ($contadorTipo * 100) / $totalMes;
            $porcentaje = number_format($porcentaje, 2, '.', '');

            $tipos_categorias[$i] = [
                'descripcion' => $descripcion_tipo,
                'total' => $contadorTipo,
                'porcentaje' => $porcentaje
            ];
        }
        usort($tipos_categorias, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        return $tipos_categorias;
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
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
