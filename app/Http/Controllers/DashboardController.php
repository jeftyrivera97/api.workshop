<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

        public function categoriasAnual($year)
    {

        try {
            $fecha_inicial = Carbon::create($year, 1, 1)->startOfYear()->format('Y-m-d');
            $fecha_final = Carbon::create($year, 12, 31)->endOfYear()->format('Y-m-d');

            $dataCategorias = [];
            $categorias = IngresoCategoria::all();

            $total = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

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

            usort($dataCategorias, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            return $dataCategorias;
        } catch (\Throwable $th) {
            Log::info('Error recibido:', ['error' => $th->getMessage()]);
            return response()->json([
                'error' => 'Error al obtener los ingresos',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function tiposAnual($year)
    {
        try {
            $fecha_inicial = Carbon::create($year, 1, 1)->startOfYear()->format('Y-m-d');
            $fecha_final = Carbon::create($year, 12, 31)->endOfYear()->format('Y-m-d');

            $tipos_categorias = [];
            $tipos = IngresoTipo::all();
            $categorias = IngresoCategoria::all();

            $totalMes = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            Log::info('Total Ingreso Tipo Mes:', ['total' => $totalMes]);

            if ($totalMes <= 0) {
                return [];
            }

            foreach ($tipos as $tipo) {
                $contadorTipo = 0;
                $id_tipo = $tipo->id;
                $descripcion_tipo = $tipo->descripcion;

                foreach ($categorias as $categoria) {
                    if ($categoria->id_tipo == $id_tipo) {
                        $totalCategoria = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                            ->where('id_estado', 1)
                            ->where('id_categoria', $categoria->id)
                            ->sum('total');
                        $contadorTipo += $totalCategoria;
                    }
                }

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

            usort($tipos_categorias, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            return $tipos_categorias;
        } catch (\Throwable $th) {
            Log::info('Error recibido:', ['error' => $th->getMessage()]);
            return response()->json([
                'error' => 'Error al obtener los ingresos',
                'message' => $th->getMessage(),
            ], 500);
        }
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
