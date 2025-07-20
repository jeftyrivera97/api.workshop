<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ingreso;
use App\Models\IngresoCategoria;
use Illuminate\Support\Arr;
use App\Http\Resources\IngresoResource;
use App\Models\IngresoTipo;
use Illuminate\Support\Facades\Log;


class IngresoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $monthParam = $request->get('dateParam'); // "2025-07"
            Log::info('Parámetro recibido:', ['dateParam' => $monthParam]);

            $fechaParam = \Carbon\Carbon::createFromFormat('Y-m', $monthParam);
            $year = $fechaParam->year;  // 2025
            $mes = $fechaParam->month;  // 7 (sin cero inicial)
            $mesFormatted = $fechaParam->format('m'); // "07" (con cero inicial)

            // ✅ Fechas del mes actual (crear nuevas instancias)
            $fecha_inicial = \Carbon\Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()->format('Y-m-d');
            $fecha_final = \Carbon\Carbon::createFromFormat('Y-m', $monthParam)->endOfMonth()->format('Y-m-d');

            // ✅ Fechas del mes anterior (crear nueva instancia y restar mes)
            $fechaAnterior = \Carbon\Carbon::createFromFormat('Y-m', $monthParam)->subMonth();
            $mesAnterior = $fechaAnterior->month;
            $mesAnteriorFormatted = $fechaAnterior->format('m');

            // ✅ Crear fechas del mes anterior desde cero
            $fecha_inicial_anterior = \Carbon\Carbon::createFromFormat('Y-m', $monthParam)->subMonth()->startOfMonth()->format('Y-m-d');
            $fecha_final_anterior = \Carbon\Carbon::createFromFormat('Y-m', $monthParam)->subMonth()->endOfMonth()->format('Y-m-d');

            Log::info('Fechas Obtenidas:', [
                'Fecha Inicial Seleccionada' => $fecha_inicial,
                'Fecha Final Seleccionada' => $fecha_final,
                'Mes Actual' => $mes,
                'Mes Actual Formateado' => $mesFormatted,
                'Año Actual' => $year,
                'Fecha Inicial Anterior' => $fecha_inicial_anterior,
                'Fecha Final Anterior' => $fecha_final_anterior,
                'Mes Anterior' => $mesAnterior,
                'Mes Anterior Formateado' => $mesAnteriorFormatted,

            ]);

            $tableHeaders = array(
                1 => "ID",
                2 => "Fecha",
                3 => "Descripcion",
                4 => "Categoria",
                5 => "Total",
            );

            $moduleName = "ingreso";
            $moduleTitle = "Ingresos";

            $dataGraficaMes = $this->graficaMes($year);

            $registrosMesActual = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])->where('id_estado', 1)->get();

            $totalMes = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])->sum('total');
            $totalMes = "L. " . number_format($totalMes, 2, '.', ',');

            $totalMesAnterior = Ingreso::whereBetween('fecha', [$fecha_inicial_anterior, $fecha_final_anterior])->sum('total');
            $totalMesAnterior = "L. " . number_format($totalMesAnterior, 2, '.', ',');

            $totalAnual = Ingreso::where('fecha', '>=', "$year-01-01")->where('fecha', '<=', "$year-12-31")->sum('total');
            $totalAnual = "L. " . number_format($totalAnual, 2, '.', ',');

            $tiposCategorias = $this->tiposMes($year, $mesFormatted);
            $categoriasMes = $this->categoriasMes($year, $mesFormatted);

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

            Log::info('Data:', ['Data' => $data->toJson()]);
            return $data;
        } catch (\Throwable $th) {
            Log::info('Error recibido:', ['error' => $th->getMessage(),]);
            return response()->json([
                'error' => 'Error al obtener los ingresos',
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function graficaMes($year)
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
        // ✅ Usar fechas correctas con Carbon
        $fecha_inicial = \Carbon\Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
        $fecha_final = \Carbon\Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');
        
        $dataCategorias = [];
        $categorias = IngresoCategoria::all();

        // ✅ Calcular total una sola vez
        $total = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])
            ->where('id_estado', 1)
            ->sum('total');

        // ✅ Verificar división por cero
        if ($total <= 0) {
            return [];
        }

        foreach ($categorias as $categoria) {
            $totalIngreso = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->where('id_categoria', $categoria->id)
                ->sum('total');

            if ($totalIngreso > 0) {
                $porcentaje = ($totalIngreso * 100) / $total;
                $porcentaje = number_format($porcentaje, 2, '.', '');

                $dataCategorias[] = [
                    'descripcion' => $categoria->descripcion,
                    'total' => $totalIngreso,
                    'porcentaje' => $porcentaje
                ];
            }
        }

        // ✅ Ordenar de mayor a menor
        usort($dataCategorias, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return $dataCategorias;
    }


    public function tiposMes($year, $mes)
    {
        // ✅ Usar fechas correctas con Carbon
        $fecha_inicial = \Carbon\Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
        $fecha_final = \Carbon\Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');
        
        $tipos_categorias = [];
        $tipos = IngresoTipo::all();
        $categorias = IngresoCategoria::all();

        // ✅ Calcular total una sola vez y correctamente
        $totalMes = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])
            ->where('id_estado', 1)
            ->sum('total');
        
        Log::info('Total Ingreso Tipo Mes:', ['total' => $totalMes]);
        
        // ✅ Verificar división por cero
        if ($totalMes <= 0) {
            return [];
        }

        foreach ($tipos as $index => $tipo) {
            $contadorTipo = 0;
            $id_tipo = $tipo->id;
            $descripcion_tipo = $tipo->descripcion;

            // ✅ Usar foreach en lugar de loops manuales
            foreach ($categorias as $categoria) {
                if ($categoria->id_tipo == $id_tipo) {
                    $totalCategoria = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                        ->where('id_estado', 1)
                        ->where('id_categoria', $categoria->id)
                        ->sum('total');
                    $contadorTipo += $totalCategoria;
                }
            }

            // ✅ Solo agregar si hay datos
            if ($contadorTipo > 0) {
                $porcentaje = ($contadorTipo * 100) / $totalMes;
                $porcentaje = number_format($porcentaje, 2, '.', '');

                $tipos_categorias[] = [
                    'descripcion' => $descripcion_tipo,
                    'total' => $contadorTipo,
                    'porcentaje' => $porcentaje
                ];
            }
        }

        // ✅ Ordenar de mayor a menor
        usort($tipos_categorias, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        
        return $tipos_categorias;
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
