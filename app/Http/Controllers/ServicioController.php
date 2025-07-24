<?php

namespace App\Http\Controllers;

use App\Http\Resources\ServicioResource;
use App\Models\Auto;
use App\Models\Ingreso;
use App\Models\IngresoServicio;
use App\Models\Servicio;
use App\Models\ServicioCategoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            $monthParam = $request->get('dateParam'); // "2025-07"
            Log::info('Parámetro recibido:', ['dateParam' => $monthParam]);

            $fechaParam = Carbon::createFromFormat('Y-m', $monthParam);
            $year = $fechaParam->year;  // 2025
            $mes = $fechaParam->month;  // 7 (sin cero inicial)
            $mesFormatted = $fechaParam->format('m'); // "07" (con cero inicial)

            // Fechas del mes actual
            $fecha_inicial = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::createFromFormat('Y-m', $monthParam)->endOfMonth()->format('Y-m-d');

            // Fechas del mes anterior
            $fechaAnterior = Carbon::createFromFormat('Y-m', $monthParam)->subMonth();
            $mesAnterior = $fechaAnterior->month;
            $mesAnteriorFormatted = $fechaAnterior->format('m');

            $fecha_inicial_anterior = Carbon::createFromFormat('Y-m', $monthParam)->subMonth()->startOfMonth()->format('Y-m-d');
            $fecha_final_anterior = Carbon::createFromFormat('Y-m', $monthParam)->subMonth()->endOfMonth()->format('Y-m-d');

            // Fechas del mismo mes pero año anterior
            $fechaAñoPasado = Carbon::createFromFormat('Y-m', $monthParam)->subYear();
            $añoPasado = $fechaAñoPasado->year;  // 2024
            $mesMismoAñoPasado = $fechaAñoPasado->month;  // 7 (mismo mes)

            $fecha_inicial_year_anterior = Carbon::createFromFormat('Y-m', $monthParam)->subYear()->startOfMonth()->format('Y-m-d');
            $fecha_final_year_anterior = Carbon::createFromFormat('Y-m', $monthParam)->subYear()->endOfMonth()->format('Y-m-d');


            $registrosMesActual = Servicio::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1) // Agregar filtro
                ->get();

            $totalMes = Servicio::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1) // Agregar filtro
                ->sum('total');
            $totalMes = "L. " . number_format($totalMes, 2, '.', ',');

            $totalAnual = Servicio::whereYear('fecha', $year)
                ->where('id_estado', 1)
                ->sum('total');
            $totalAnual = "L. " . number_format($totalAnual, 2, '.', ',');

            $totalMesYearAnterior = Servicio::whereBetween('fecha', [$fecha_inicial_year_anterior, $fecha_final_year_anterior])
                ->where('id_estado', 1) // Agregar filtro
                ->sum('total');
            $totalMesYearAnterior = "L. " . number_format($totalMesYearAnterior, 2, '.', ',');

            $tableHeaders = array(
                1 => "ID",
                2 => "Fecha",
                3 => "Descripcion",
                4 => "Cliente",
                5 => "Auto",
                6 => "Categoria",
                7 => "Color",
                8 => "Placa",
                9 => "Tipo de Pago",
                10 => "Total",
            );

            $moduleName = "servicio";
            $moduleTitle = "Servicios";

            $dataGraficaMes = $this->graficaMes($year);

            $categoriasMes = $this->categoriasMes($year, $mes);
            $autosMes = $this->autosMes($year, $mes);


            $data = ServicioResource::collection(
                Servicio::with(['categoria', 'estado', 'usuario']) // <-- corregido, sin 'servicio.categoria'
                    ->where('id_estado', 1)
                    ->whereBetween('fecha', [$fecha_inicial, $fecha_final])
                    ->orderBy('fecha', 'desc')
                    ->paginate(10)
            )
                ->additional([
                    'tableHeaders' => $tableHeaders,
                    'counter' => $registrosMesActual->count(),
                    'moduleName' => $moduleName,
                    'moduleTitle' => $moduleTitle,
                    'totalMes' => $totalMes,
                    'totalAnual' => $totalAnual,
                    'dataGraficaMes' => $dataGraficaMes,
                    'totalMesYearAnterior' => $totalMesYearAnterior,
                    'tiposMes' => $autosMes,
                    'categoriasMes' => $categoriasMes,
                    'analisisMensual' => $moduleName,
                ]);

                return $data;
        } catch (\Throwable $th) {
            Log::info('Error recibido:', ['error' => $th->getMessage()]);
            return response()->json([
                'error' => 'Error al obtener los servicios',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

      public function autosMes($year, $mes) //Autos
    {

        try {
            $fecha_inicial = Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');

            $dataAutos = [];
            $modelosAutos = Auto::all();

            $total = Servicio::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            if ($total <= 0) {
                return [];
            }

            foreach ($modelosAutos as $modelo) {
                $totalIngreso = Servicio::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                    ->where('id_estado', 1)
                    ->where('id_auto', $modelo->id)
                    ->sum('total');

                $totalUnidades = Servicio::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                    ->where('id_estado', 1)
                    ->where('id_auto', $modelo->id)
                    ->count();

                if ($totalIngreso > 0) {
                    $porcentaje = ($totalIngreso * 100) / $total;
                    $porcentaje = number_format($porcentaje, 2, '.', '');

                    $dataAutos[] = [
                        'descripcion' =>  $modelo->modelo . ' ' . $modelo->year . ' ' . $modelo->traccion . ' (' . $totalUnidades . ' unds.) ',
                        'total' => $totalIngreso,
                        'porcentaje' => $porcentaje
                    ];
                }
            }

            usort($dataAutos, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            return $dataAutos;
        } catch (\Throwable $th) {
            Log::info('Error recibido:', ['error' => $th->getMessage()]);
            return response()->json([
                'error' => 'Error al obtener los autos en servicios en la funcion tiposMes',
                'message' => $th->getMessage(),
            ], 500);
        }
    }


      public function categoriasMes($year, $mes)
    {

        try {
            $fecha_inicial = Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');

            $dataCategorias = [];
            $categorias = ServicioCategoria::all();

            $total = Servicio::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            if ($total <= 0) {
                return [];
            }

            foreach ($categorias as $categoria) {
                $totalIngreso = Servicio::whereBetween('fecha', [$fecha_inicial, $fecha_final])
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

            usort($dataCategorias, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            return $dataCategorias;
        } catch (\Throwable $th) {
            Log::info('Error recibido:', ['error' => $th->getMessage()]);
            return response()->json([
                'error' => 'Error al obtener las categorias de servicios en la funcion categoriasMes',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function graficaMes($year)
    {
        try {
            $meses = [
                'Enero',
                'Febrero',
                'Marzo',
                'Abril',
                'Mayo',
                'Junio',
                'Julio',
                'Agosto',
                'Septiembre',
                'Octubre',
                'Noviembre',
                'Diciembre'
            ];

            $dataGraficaMes = [];

            for ($i = 1; $i <= 12; $i++) {

                $fechaInicial = Carbon::create($year, $i, 1)->startOfMonth()->format('Y-m-d');
                $fechaFinal = Carbon::create($year, $i, 1)->endOfMonth()->format('Y-m-d');

                $total = count(Servicio::whereBetween('fecha', [$fechaInicial, $fechaFinal])
                    ->where('id_estado', 1)->get());

                $dataGraficaMes[] = [
                    'descripcion' => $meses[$i - 1],
                    'total' => $total,
                    'mes_numero' => $i,
                ];
            }

            return collect($dataGraficaMes);
        } catch (\Throwable $th) {
            Log::info('Error recibido:', ['error' => $th->getMessage()]);
            return response()->json([
                'error' => 'Error al obtener los servicios en la funcion graficaMes',
                'message' => $th->getMessage(),
            ], 500);
        }
    }



    /**
     * Show the form for creating a new resource.
     */


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
