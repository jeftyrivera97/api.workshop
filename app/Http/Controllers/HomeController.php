<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraCategoria;
use App\Models\CompraTipo;
use App\Models\Gasto;
use App\Models\GastoCategoria;
use App\Models\GastoTipo;
use App\Models\Ingreso;
use App\Models\IngresoCategoria;
use App\Models\IngresoTipo;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {


            $fechaParam = Carbon::now(); // Fecha de HOY
            $monthParam = $fechaParam->format('Y-m'); // "2025-07"

            $fechaParam = Carbon::createFromFormat('Y-m', $monthParam);
            $year = $fechaParam->year;  // 2025
            $mes = $fechaParam->month;  // 7 (sin cero inicial)
            $mesFormatted = $fechaParam->format('m'); // "07" (con cero inicial)

            // Fechas del año actual
            $yearActual = Carbon::now()->year; // 2025
            $fecha_inicial_año = Carbon::createFromFormat('Y', $yearActual)->startOfYear()->format('Y-m-d');
            $fecha_final_año = Carbon::createFromFormat('Y', $yearActual)->endOfYear()->format('Y-m-d');

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

            $moduleName = "inicio";
            $moduleTitle = "Inicio";

            $totalIngresosActual = Ingreso::whereBetween('fecha', [$fecha_inicial_año, $fecha_final_año])
                ->where('id_estado', 1) // ✅ Agregar filtro
                ->sum('total');

            $totalComprasActual = Compra::whereBetween('fecha', [$fecha_inicial_año, $fecha_final_año])
                ->where('id_estado', 1) // ✅ Agregar filtro
                ->sum('total');

            $totalGastosActual = Gasto::whereBetween('fecha', [$fecha_inicial_año, $fecha_final_año])
                ->where('id_estado', 1) // ✅ Agregar filtro
                ->sum('total');


            $dataGraficaIngresos = $this->graficaIngresos($year);
            $dataGraficaEgresos = $this->graficaEgresos($year);

            $categoriasIngresosAnual = $this->categoriasIngresosAnual($year);
            $categoriasComprasAnual = $this->categoriasComprasAnual($year);
            $categoriasGastosAnual = $this->categoriasGastosAnual($year);

            $balanceAnual = $totalIngresosActual - ($totalComprasActual + $totalGastosActual);



            //  AGREGAR: Obtener datos del año anterior para comparación
            $yearAnterior = $year - 1;
            $fecha_inicial_año_anterior = Carbon::create($yearAnterior, 1, 1)->startOfYear()->format('Y-m-d');
            $fecha_final_año_anterior = Carbon::create($yearAnterior, 12, 31)->endOfYear()->format('Y-m-d');

            $totalIngresosAnterior = Ingreso::whereBetween('fecha', [$fecha_inicial_año_anterior, $fecha_final_año_anterior])
                ->where('id_estado', 1)
                ->sum('total');

            $totalComprasAnterior = Compra::whereBetween('fecha', [$fecha_inicial_año_anterior, $fecha_final_año_anterior])
                ->where('id_estado', 1)
                ->sum('total');

            $totalGastosAnterior = Gasto::whereBetween('fecha', [$fecha_inicial_año_anterior, $fecha_final_año_anterior])
                ->where('id_estado', 1)
                ->sum('total');

            $balanceAnterior = $totalIngresosAnterior - ($totalComprasAnterior + $totalGastosAnterior);

            // ANÁLISIS COMPLETO DE BALANCE (Opción 2)
            $totalEgresosActual = $totalComprasActual + $totalGastosActual;
            $totalEgresosAnterior = $totalComprasAnterior + $totalGastosAnterior;

            $totales = [
                'ingresosAnualActual' => $totalIngresosActual,
                'comprasAnualActual' => $totalComprasActual,
                'gastosAnualActual' => $totalGastosActual,
                'balanceAnual' => $balanceAnual,

                'ingresosAnualAnterior' => $totalIngresosAnterior,
                'comprasAnualAnterior' => $totalComprasAnterior,
                'gastosAnualAnterior' => $totalGastosAnterior,
                'balanceAnterior' => $balanceAnterior,

                'egresosAnualActual' => $totalEgresosActual,
                'egresosAnualAnterior' => $totalEgresosAnterior,
            ];


            $tiposIngresosAnual = $this->tiposIngresosAnual($year);

            // 1. Porcentaje de balance sobre ingresos (Margen de ganancia)
            $margenGananciaActual = 0;
            $margenGananciaAnterior = 0;
            if ($totalIngresosActual > 0) {
                $margenGananciaActual = ($balanceAnual * 100) / $totalIngresosActual;
            }
            if ($totalIngresosAnterior > 0) {
                $margenGananciaAnterior = ($balanceAnterior * 100) / $totalIngresosAnterior;
            }

            // 2. Porcentaje de egresos sobre ingresos
            $porcentajeEgresosActual = 0;
            $porcentajeEgresosAnterior = 0;
            if ($totalIngresosActual > 0) {
                $porcentajeEgresosActual = ($totalEgresosActual * 100) / $totalIngresosActual;
            }
            if ($totalIngresosAnterior > 0) {
                $porcentajeEgresosAnterior = ($totalEgresosAnterior * 100) / $totalIngresosAnterior;
            }

            // 3. Porcentaje de compras vs gastos (año actual)
            $porcentajeCompras = 0;
            $porcentajeGastos = 0;
            if ($totalEgresosActual > 0) {
                $porcentajeCompras = ($totalComprasActual * 100) / $totalEgresosActual;
                $porcentajeGastos = ($totalGastosActual * 100) / $totalEgresosActual;
            }

            $porcentajes = [
                'margen_ganancia_actual' => $margenGananciaActual,
                'margen_ganancia_anterior' => $margenGananciaAnterior,
                'porcentaje_egresos_actual' => $porcentajeEgresosActual,
                'porcentaje_egresos_anterior' => $porcentajeEgresosAnterior,
                'porcentaje_compras' => $porcentajeCompras,
                'porcentaje_gastos' => $porcentajeGastos,
            ];

            // 4. Estado del balance actual
            $estadoBalance = 'equilibrado';
            $colorBalance = 'info';
            if ($balanceAnual > 0) {
                $estadoBalance = 'positivo';
                $colorBalance = 'success';
            } elseif ($balanceAnual < 0) {
                $estadoBalance = 'negativo';
                $colorBalance = 'danger';
            }

            $balances = [
                'balance_anual' => $balanceAnual,
                'estado_balance' => $estadoBalance,
                'color_balance' => $colorBalance,
            ];

            // ✅ ANÁLISIS AVANZADO CON COMPARACIÓN (Opción 3)
            // 5. Variaciones año vs año anterior
            $variacionIngresos = 0;
            $variacionEgresos = 0;
            $variacionBalance = 0;
            $tendenciaIngresos = 'igual';
            $tendenciaEgresos = 'igual';
            $tendenciaBalance = 'igual';

            if ($totalIngresosAnterior > 0) {
                $variacionIngresos = (($totalIngresosActual - $totalIngresosAnterior) * 100) / $totalIngresosAnterior;
                $tendenciaIngresos = $variacionIngresos > 0 ? 'crecimiento' : ($variacionIngresos < 0 ? 'decrecimiento' : 'igual');
            }

            if ($totalEgresosAnterior > 0) {
                $variacionEgresos = (($totalEgresosActual - $totalEgresosAnterior) * 100) / $totalEgresosAnterior;
                $tendenciaEgresos = $variacionEgresos > 0 ? 'aumento' : ($variacionEgresos < 0 ? 'reduccion' : 'igual');
            }

            if ($balanceAnterior != 0) {
                $variacionBalance = (($balanceAnual - $balanceAnterior) * 100) / abs($balanceAnterior);
                $tendenciaBalance = $variacionBalance > 0 ? 'mejora' : ($variacionBalance < 0 ? 'deterioro' : 'igual');
            }

            // 6. Variación del margen de ganancia
            $variacionMargen = $margenGananciaActual - $margenGananciaAnterior;
            $tendenciaMargen = $variacionMargen > 0 ? 'mejora' : ($variacionMargen < 0 ? 'deterioro' : 'igual');

            $variaciones = [
                'variacion_ingresos' => $variacionIngresos,
                'variacion_egresos' => $variacionEgresos,
                'variacion_balance' => $variacionBalance,
                'variacion_margen' => $variacionMargen,
                'tendencia_ingresos' => $tendenciaIngresos,
                'tendencia_egresos' => $tendenciaEgresos,
                'tendencia_balance' => $tendenciaBalance,
                'tendencia_margen' => $tendenciaMargen,
            ];

            // 7. Análisis de eficiencia (compras vs gastos)
            $eficienciaCompras = 'equilibrado';
            $colorEficiencia = 'info';
            $recomendacionEficiencia = 'Mantén el equilibrio entre compras y gastos para una operación saludable.';

            if ($totalEgresosActual > 0) {
                if ($porcentajeCompras > 70) {
                    $eficienciaCompras = 'compras_dominantes';
                    $colorEficiencia = 'primary';
                    $recomendacionEficiencia = 'Revisa si puedes optimizar las compras o negociar mejores precios con proveedores.';
                } elseif ($porcentajeGastos > 70) {
                    $eficienciaCompras = 'gastos_dominantes';
                    $colorEficiencia = 'warning';
                    $recomendacionEficiencia = 'Analiza los gastos administrativos y busca oportunidades de reducción.';
                } else {
                    $eficienciaCompras = 'equilibrado';
                    $colorEficiencia = 'info';
                    $recomendacionEficiencia = 'Buen balance entre compras y gastos. Sigue monitoreando para mantener la eficiencia.';
                }
            }

            $eficiencia = [
                'eficiencia_compras' => $eficienciaCompras,
                'color_eficiencia' => $colorEficiencia,
                'recomendacion' => $recomendacionEficiencia, // ✅ Se agrega la recomendación aquí
            ];

            // 8. Clasificación de rentabilidad
            $clasificacionRentabilidad = 'no_clasificado';
            $mensajeRentabilidad = '';
            $recomendacionRentabilidad = '';

            if ($margenGananciaActual >= 20) {
                $clasificacionRentabilidad = 'excelente';
                $mensajeRentabilidad = 'Rentabilidad excelente con margen superior al 20%';
                $recomendacionRentabilidad = 'Mantén esta rentabilidad y considera reinversión para crecimiento';
            } elseif ($margenGananciaActual >= 10) {
                $clasificacionRentabilidad = 'buena';
                $mensajeRentabilidad = 'Buena rentabilidad con margen entre 10% y 20%';
                $recomendacionRentabilidad = 'Busca oportunidades para optimizar costos y mejorar margen';
            } elseif ($margenGananciaActual >= 5) {
                $clasificacionRentabilidad = 'aceptable';
                $mensajeRentabilidad = 'Rentabilidad aceptable pero con margen bajo (5-10%)';
                $recomendacionRentabilidad = 'Revisa estructura de costos y precios para mejorar rentabilidad';
            } elseif ($margenGananciaActual > 0) {
                $clasificacionRentabilidad = 'minima';
                $mensajeRentabilidad = 'Rentabilidad mínima con margen muy bajo (0-5%)';
                $recomendacionRentabilidad = 'Urgente: optimiza costos o aumenta precios para mejorar rentabilidad';
            } elseif ($margenGananciaActual == 0) {
                $clasificacionRentabilidad = 'equilibrio';
                $mensajeRentabilidad = 'Punto de equilibrio - ingresos igualan egresos';
                $recomendacionRentabilidad = 'Reduce costos o aumenta ingresos para generar ganancias';
            } else {
                $clasificacionRentabilidad = 'perdidas';
                $mensajeRentabilidad = 'Operando con pérdidas - egresos superan ingresos';
                $recomendacionRentabilidad = 'Crítico: reestructura operaciones para reducir pérdidas inmediatamente';
            }

            $rentabilidad = [
                'clasificacion_rentabilidad' => $clasificacionRentabilidad,
                'mensaje_rentabilidad' => $mensajeRentabilidad,
                'recomendacion_rentabilidad' => $recomendacionRentabilidad,
            ];

            // ✅ AGREGAR TODOS LOS NUEVOS DATOS AL ARRAY
            $data = [
                'moduleName' => $moduleName,
                'moduleTitle' => $moduleTitle,

                // Totales
                'totales' => $totales,
                // Porcentajes
                'porcentajes' => $porcentajes,
                // Balances
                'balances' => $balances,
                // Variaciones
                'variaciones' => $variaciones,
                // Eficiencia
                'eficiencia' => $eficiencia,
                // Rentabilidad
                'rentabilidad' => $rentabilidad,

                // Datos para gráficas (mantener existentes)
                'dataGraficaIngresos' => $dataGraficaIngresos,
                'dataGraficaEgresos' => $dataGraficaEgresos,
                'categoriasIngresosAnual' => $categoriasIngresosAnual,
                'categoriasComprasAnual' => $categoriasComprasAnual,
                'categoriasGastosAnual' => $categoriasGastosAnual,
                'tiposIngresosAnual' => $tiposIngresosAnual,
            ];

            Log::info('Data:', ['Data' => json_encode($data)]);
            return $data;
        } catch (\Throwable $th) {
            Log::info('Error recibido:', ['error' => $th->getMessage()]);
            return response()->json([
                'error' => 'Error al obtener los ingresos',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function graficaIngresos($year)
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

                $total = Ingreso::whereBetween('fecha', [$fechaInicial, $fechaFinal])
                    ->where('id_estado', 1)
                    ->sum('total');

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
                'error' => 'Error al obtener los ingresos',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function graficaEgresos($year)
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

                $totalCompras = Compra::whereBetween('fecha', [$fechaInicial, $fechaFinal])
                    ->where('id_estado', 1)
                    ->sum('total');

                $totalGastos = Gasto::whereBetween('fecha', [$fechaInicial, $fechaFinal])
                    ->where('id_estado', 1)
                    ->sum('total');

                $total = $totalCompras + $totalGastos;

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
                'error' => 'Error al obtener los egresos',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function tiposIngresosAnual($year)
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

    public function categoriasIngresosAnual($year)
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

    public function categoriasComprasAnual($year)
    {

        try {
            $fecha_inicial = Carbon::create($year, 1, 1)->startOfYear()->format('Y-m-d');
            $fecha_final = Carbon::create($year, 12, 31)->endOfYear()->format('Y-m-d');

            $dataCategorias = [];
            $categorias = CompraCategoria::all();

            $total = Compra::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            if ($total <= 0) {
                return [];
            }

            foreach ($categorias as $categoria) {
                $totalCompra = Compra::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                    ->where('id_estado', 1)
                    ->where('id_categoria', $categoria->id)
                    ->sum('total');

                if ($totalCompra > 0) {
                    $porcentaje = ($totalCompra * 100) / $total;
                    $porcentaje = number_format($porcentaje, 2, '.', '');

                    $dataCategorias[] = [
                        'descripcion' => $categoria->descripcion,
                        'total' => $totalCompra,
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
                'error' => 'Error al obtener las compras',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function categoriasGastosAnual($year)
    {

        try {
            $fecha_inicial = Carbon::create($year, 1, 1)->startOfYear()->format('Y-m-d');
            $fecha_final = Carbon::create($year, 12, 31)->endOfYear()->format('Y-m-d');

            $dataCategorias = [];
            $categorias = GastoCategoria::all();

            $total = Gasto::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            if ($total <= 0) {
                return [];
            }

            foreach ($categorias as $categoria) {
                $totalGasto = Gasto::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                    ->where('id_estado', 1)
                    ->where('id_categoria', $categoria->id)
                    ->sum('total');

                if ($totalGasto > 0) {
                    $porcentaje = ($totalGasto * 100) / $total;
                    $porcentaje = number_format($porcentaje, 2, '.', '');

                    $dataCategorias[] = [
                        'descripcion' => $categoria->descripcion,
                        'total' => $totalGasto,
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
                'error' => 'Error al obtener los gastos',
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function tiposComprasAnual($year, $mes)
    {
        try {
            $fecha_inicial = Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');

            $tipos_categorias = [];
            $tipos = CompraTipo::all();
            $categorias = CompraCategoria::all();

            $totalMes = Compra::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            Log::info('Total Compra Tipo Mes:', ['total' => $totalMes]);

            // ✅ Incluir todos los tipos, incluso los que tienen 0
            foreach ($tipos as $tipo) {
                $contadorTipo = 0;
                $id_tipo = $tipo->id;
                $descripcion_tipo = $tipo->descripcion;

                foreach ($categorias as $categoria) {
                    if ($categoria->id_tipo == $id_tipo) {
                        $totalCategoria = Compra::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                            ->where('id_estado', 1)
                            ->where('id_categoria', $categoria->id)
                            ->sum('total');
                        $contadorTipo += $totalCategoria;
                    }
                }

                // ✅ Incluir TODOS los tipos, incluso con $contadorTipo = 0
                $porcentaje = $totalMes > 0 ? ($contadorTipo * 100) / $totalMes : 0;
                $porcentaje = number_format($porcentaje, 2, '.', '');

                $tipos_categorias[] = [
                    'descripcion' => $descripcion_tipo,
                    'total' => $contadorTipo,
                    'porcentaje' => $porcentaje
                ];
            }

            usort($tipos_categorias, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            Log::info('Tipos de compras procesados:', [
                'total_tipos' => count($tipos_categorias),
                'tipos' => $tipos_categorias
            ]);

            return $tipos_categorias;
        } catch (\Throwable $th) {
            Log::error('Error en tiposAnual en compras:', ['error' => $th->getMessage()]);
            return [];
        }
    }
    public function tiposGastosAnua($year, $mes)
    {
        try {
            $fecha_inicial = Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');

            $tipos_categorias = [];
            $tipos = GastoTipo::all();
            $categorias = GastoCategoria::all();

            $totalMes = Gasto::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            Log::info('Total Gasto Tipo Mes:', ['total' => $totalMes]);

            // ✅ Incluir todos los tipos, incluso los que tienen 0
            foreach ($tipos as $tipo) {
                $contadorTipo = 0;
                $id_tipo = $tipo->id;
                $descripcion_tipo = $tipo->descripcion;

                foreach ($categorias as $categoria) {
                    if ($categoria->id_tipo == $id_tipo) {
                        $totalCategoria = Gasto::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                            ->where('id_estado', 1)
                            ->where('id_categoria', $categoria->id)
                            ->sum('total');
                        $contadorTipo += $totalCategoria;
                    }
                }

                // ✅ Incluir TODOS los tipos, incluso con $contadorTipo = 0
                $porcentaje = $totalMes > 0 ? ($contadorTipo * 100) / $totalMes : 0;
                $porcentaje = number_format($porcentaje, 2, '.', '');

                $tipos_categorias[] = [
                    'descripcion' => $descripcion_tipo,
                    'total' => $contadorTipo,
                    'porcentaje' => $porcentaje
                ];
            }

            usort($tipos_categorias, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            Log::info('Tipos de gastos procesados:', [
                'total_tipos' => count($tipos_categorias),
                'tipos' => $tipos_categorias
            ]);

            return $tipos_categorias;
        } catch (\Throwable $th) {
            Log::error('Error en tiposAnual en gastos:', ['error' => $th->getMessage()]);
            return [];
        }
    }



    public function analisisMensual($categorias, $tipos)
    {
        $analisis = [];

        // ✅ Validación inicial
        if (!$tipos || !is_array($tipos) || empty($tipos)) {
            Log::warning('Tipos de planillas inválidos o vacíos');
            return [
                [
                    'categoria' => 'error',
                    'tipo' => 'info',
                    'titulo' => '👥 Sin Datos de Planillas',
                    'porcentaje' => 0,
                    'ratio' => null,
                    'mensaje' => 'No hay datos de planillas para analizar este mes.',
                    'recomendacion' => 'Verifica que existan registros de planillas para este período.'
                ]
            ];
        }

        // 📊 Configuración de análisis por tipo de planilla
        $tiposAnalisis = [
            'Salarios a empleados directos' => [
                'categoria' => 'salarios_directos',
                'rangos' => [
                    ['min' => 40, 'max' => 70, 'tipo' => 'success', 'titulo' => '✅ Salarios Directos Óptimos'],
                    ['min' => 25, 'max' => 39, 'tipo' => 'warning', 'titulo' => '⚠️ Salarios Directos Bajos'],
                    ['min' => 71, 'max' => 85, 'tipo' => 'warning', 'titulo' => '⚠️ Salarios Directos Altos'],
                    ['min' => 86, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Salarios Directos Excesivos'],
                    ['min' => 0, 'max' => 24, 'tipo' => 'danger', 'titulo' => '🚨 Salarios Directos Insuficientes']
                ],
                'mensajes' => [
                    'success' => 'Excelente distribución de salarios directos. El personal productivo recibe una compensación adecuada que impulsa la generación de ingresos.',
                    'warning' => 'Distribución de salarios directos que requiere atención. Puede afectar la productividad o indicar desequilibrio en la estructura salarial.',
                    'danger' => 'Nivel crítico de salarios directos. Puede indicar falta de personal productivo o exceso de costos laborales directos.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén esta estructura salarial y considera incentivos por productividad.',
                    'warning' => 'Evalúa aumentar salarios directos o reducir personal administrativo según el caso.',
                    'danger' => 'Urgente: reequilibra la estructura salarial enfocándote en personal productivo.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron salarios de empleados directos este mes.',
                    'recomendacion' => 'Crítico: verifica si hay personal productivo sin registrar o problemas operativos.'
                ]
            ],
            'Salarios a empleados indirectos' => [
                'categoria' => 'salarios_indirectos',
                'rangos' => [
                    ['min' => 0, 'max' => 30, 'tipo' => 'success', 'titulo' => '✅ Salarios Indirectos Controlados'],
                    ['min' => 31, 'max' => 45, 'tipo' => 'warning', 'titulo' => '⚠️ Salarios Indirectos Moderados'],
                    ['min' => 46, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Salarios Indirectos Excesivos']
                ],
                'mensajes' => [
                    'success' => 'Excelente control de salarios indirectos. La estructura administrativa es eficiente sin comprometer la operación.',
                    'warning' => 'Salarios indirectos en nivel moderado que requiere monitoreo para mantener eficiencia operativa.',
                    'danger' => 'Nivel preocupante de salarios indirectos. El personal administrativo puede estar sobrecargando los costos laborales.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén esta eficiencia administrativa y busca automatización cuando sea posible.',
                    'warning' => 'Revisa funciones administrativas y considera optimización de procesos.',
                    'danger' => 'Urgente: reduce personal administrativo o aumenta personal productivo para equilibrar.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron salarios de empleados indirectos este mes.',
                    'recomendacion' => 'Verifica si hay personal administrativo sin registrar o si la estructura es muy plana.'
                ]
            ],
            'Liquidaciones' => [
                'categoria' => 'liquidaciones',
                'rangos' => [
                    ['min' => 0, 'max' => 5, 'tipo' => 'success', 'titulo' => '✅ Liquidaciones Normales'],
                    ['min' => 6, 'max' => 15, 'tipo' => 'warning', 'titulo' => '⚠️ Liquidaciones Moderadas'],
                    ['min' => 16, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Liquidaciones Excesivas']
                ],
                'mensajes' => [
                    'success' => 'Nivel normal de liquidaciones que indica estabilidad laboral y baja rotación de personal.',
                    'warning' => 'Nivel moderado de liquidaciones que puede indicar algunos ajustes de personal o problemas de retención.',
                    'danger' => 'Nivel muy alto de liquidaciones que sugiere problemas serios de rotación, clima laboral o reestructuración masiva.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén este ambiente laboral estable y continúa con políticas de retención.',
                    'warning' => 'Analiza causas de rotación y mejora estrategias de retención de talento.',
                    'danger' => 'Urgente: identifica causas de alta rotación e implementa plan de retención inmediato.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron liquidaciones este mes.',
                    'recomendacion' => 'Excelente! Indica estabilidad laboral total. Mantén estas condiciones.'
                ]
            ],
            'Bonos y Beneficios Laborales' => [
                'categoria' => 'bonos_beneficios',
                'rangos' => [
                    ['min' => 5, 'max' => 20, 'tipo' => 'success', 'titulo' => '✅ Bonos y Beneficios Óptimos'],
                    ['min' => 2, 'max' => 4, 'tipo' => 'warning', 'titulo' => '⚠️ Bonos y Beneficios Bajos'],
                    ['min' => 21, 'max' => 100, 'tipo' => 'warning', 'titulo' => '⚠️ Bonos y Beneficios Altos'],
                    ['min' => 0, 'max' => 1, 'tipo' => 'danger', 'titulo' => '🚨 Bonos y Beneficios Insuficientes']
                ],
                'mensajes' => [
                    'success' => 'Excelente inversión en bonos y beneficios que motiva al personal y mejora la retención de talento.',
                    'warning' => 'Nivel de bonos y beneficios que requiere ajuste para optimizar motivación del personal o controlar costos.',
                    'danger' => 'Nivel crítico de bonos y beneficios que puede afectar la motivación, productividad y retención del personal.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén esta inversión en capital humano y mide su impacto en productividad.',
                    'warning' => 'Ajusta bonos según productividad o considera beneficios no monetarios más costo-efectivos.',
                    'danger' => 'Urgente: implementa programa de incentivos para mejorar motivación y retención.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron bonos ni beneficios laborales este mes.',
                    'recomendacion' => 'Considera implementar incentivos para motivar al personal y mejorar retención.'
                ]
            ],
            'Retenciones' => [
                'categoria' => 'retenciones',
                'rangos' => [
                    ['min' => 15, 'max' => 25, 'tipo' => 'success', 'titulo' => '✅ Retenciones Normales'],
                    ['min' => 10, 'max' => 14, 'tipo' => 'warning', 'titulo' => '⚠️ Retenciones Bajas'],
                    ['min' => 26, 'max' => 35, 'tipo' => 'warning', 'titulo' => '⚠️ Retenciones Altas'],
                    ['min' => 0, 'max' => 9, 'tipo' => 'danger', 'titulo' => '🚨 Retenciones Muy Bajas'],
                    ['min' => 36, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Retenciones Excesivas']
                ],
                'mensajes' => [
                    'success' => 'Nivel normal de retenciones que indica cumplimiento adecuado de obligaciones fiscales y laborales.',
                    'warning' => 'Nivel de retenciones que requiere revisión para asegurar cumplimiento normativo correcto.',
                    'danger' => 'Nivel crítico de retenciones que puede indicar problemas de cumplimiento fiscal o errores en cálculos.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén este cumplimiento normativo y continúa con controles internos.',
                    'warning' => 'Revisa cálculos de retenciones y asegura cumplimiento de todas las obligaciones.',
                    'danger' => 'Urgente: audita cálculos de retenciones y corrige inmediatamente para evitar problemas legales.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron retenciones este mes.',
                    'recomendacion' => 'Crítico: verifica cumplimiento de obligaciones fiscales y laborales.'
                ]
            ]
        ];

        // 🔄 Procesar cada tipo de análisis
        foreach ($tiposAnalisis as $tipoNombre => $config) {
            $tipoData = collect($tipos)->where('descripcion', $tipoNombre)->first();

            if ($tipoData && isset($tipoData['porcentaje']) && floatval($tipoData['porcentaje']) > 0) {
                $porcentaje = floatval($tipoData['porcentaje']);
                $rangoEncontrado = null;

                foreach ($config['rangos'] as $rango) {
                    if ($porcentaje >= $rango['min'] && $porcentaje <= $rango['max']) {
                        $rangoEncontrado = $rango;
                        break;
                    }
                }

                if ($rangoEncontrado) {
                    $analisis[] = [
                        'categoria' => $config['categoria'],
                        'tipo' => $rangoEncontrado['tipo'],
                        'titulo' => $rangoEncontrado['titulo'],
                        'porcentaje' => $porcentaje,
                        'ratio' => null,
                        'mensaje' => $config['mensajes'][$rangoEncontrado['tipo']],
                        'recomendacion' => $config['recomendaciones'][$rangoEncontrado['tipo']]
                    ];
                }
            } else {
                // Caso sin datos
                $analisis[] = [
                    'categoria' => $config['categoria'],
                    'tipo' => 'info',
                    'titulo' => '📊 Sin Datos de ' . $tipoNombre,
                    'porcentaje' => 0,
                    'ratio' => null,
                    'mensaje' => $config['sin_datos']['mensaje'],
                    'recomendacion' => $config['sin_datos']['recomendacion']
                ];
            }
        }

        // 🔍 ANÁLISIS DE EFICIENCIA DE RECURSOS HUMANOS (Directos vs Indirectos)
        $tipoDirectos = collect($tipos)->where('descripcion', 'Salarios a empleados directos')->first();
        $tipoIndirectos = collect($tipos)->where('descripcion', 'Salarios a empleados indirectos')->first();

        $porcentajeDirectos = ($tipoDirectos && isset($tipoDirectos['porcentaje'])) ? floatval($tipoDirectos['porcentaje']) : 0;
        $porcentajeIndirectos = ($tipoIndirectos && isset($tipoIndirectos['porcentaje'])) ? floatval($tipoIndirectos['porcentaje']) : 0;

        if ($porcentajeDirectos > 0 || $porcentajeIndirectos > 0) {
            if ($porcentajeDirectos > 0 && $porcentajeIndirectos > 0) {
                $ratioDirectoIndirecto = $porcentajeDirectos / $porcentajeIndirectos;

                if ($ratioDirectoIndirecto >= 2.5) {
                    $analisis[] = [
                        'categoria' => 'eficiencia_rrhh',
                        'tipo' => 'success',
                        'titulo' => '✅ Excelente Eficiencia de RRHH',
                        'porcentaje' => $porcentajeDirectos + $porcentajeIndirectos,
                        'ratio' => number_format($ratioDirectoIndirecto, 1) . ':1',
                        'mensaje' => 'Excelente estructura: ' . $porcentajeDirectos . '% personal productivo vs ' . $porcentajeIndirectos . '% administrativo.',
                        'recomendacion' => 'Mantén esta eficiencia y considera incentivos por productividad.'
                    ];
                } elseif ($ratioDirectoIndirecto >= 1.5) {
                    $analisis[] = [
                        'categoria' => 'eficiencia_rrhh',
                        'tipo' => 'warning',
                        'titulo' => '⚠️ Eficiencia de RRHH Aceptable',
                        'porcentaje' => $porcentajeDirectos + $porcentajeIndirectos,
                        'ratio' => number_format($ratioDirectoIndirecto, 1) . ':1',
                        'mensaje' => 'Estructura aceptable pero mejorable: ' . $porcentajeDirectos . '% productivo vs ' . $porcentajeIndirectos . '% administrativo.',
                        'recomendacion' => 'Considera optimizar personal administrativo o aumentar personal productivo.'
                    ];
                } else {
                    $analisis[] = [
                        'categoria' => 'eficiencia_rrhh',
                        'tipo' => 'danger',
                        'titulo' => '🚨 Baja Eficiencia de RRHH',
                        'porcentaje' => $porcentajeDirectos + $porcentajeIndirectos,
                        'ratio' => number_format($ratioDirectoIndirecto, 1) . ':1',
                        'mensaje' => 'Estructura ineficiente: demasiado personal administrativo vs productivo.',
                        'recomendacion' => 'Urgente: reestructura para tener más personal productivo que administrativo.'
                    ];
                }
            } elseif ($porcentajeDirectos > 0) {
                $analisis[] = [
                    'categoria' => 'eficiencia_rrhh',
                    'tipo' => 'success',
                    'titulo' => '✅ Estructura 100% Productiva',
                    'porcentaje' => $porcentajeDirectos,
                    'ratio' => 'Solo productivos',
                    'mensaje' => 'Estructura muy eficiente: todo el personal (' . $porcentajeDirectos . '%) es productivo.',
                    'recomendacion' => 'Excelente enfoque. Considera mínimo apoyo administrativo si es necesario.'
                ];
            } else {
                $analisis[] = [
                    'categoria' => 'eficiencia_rrhh',
                    'tipo' => 'danger',
                    'titulo' => '🚨 Solo Personal Administrativo',
                    'porcentaje' => $porcentajeIndirectos,
                    'ratio' => 'Solo administrativos',
                    'mensaje' => 'Crítico: solo personal administrativo sin personal productivo.',
                    'recomendacion' => 'Urgente: contrata personal productivo inmediatamente.'
                ];
            }
        } else {
            $analisis[] = [
                'categoria' => 'eficiencia_rrhh',
                'tipo' => 'info',
                'titulo' => '📊 Sin Análisis de Eficiencia RRHH',
                'porcentaje' => 0,
                'ratio' => 'Sin datos',
                'mensaje' => 'No hay datos de salarios directos ni indirectos.',
                'recomendacion' => 'Registra y clasifica correctamente los tipos de empleados.'
            ];
        }

        // 🔍 ANÁLISIS DE CLIMA LABORAL (Liquidaciones vs Bonos)
        $tipoLiquidaciones = collect($tipos)->where('descripcion', 'Liquidaciones')->first();
        $tipoBonos = collect($tipos)->where('descripcion', 'Bonos y Beneficios Laborales')->first();

        $porcentajeLiquidaciones = ($tipoLiquidaciones && isset($tipoLiquidaciones['porcentaje'])) ? floatval($tipoLiquidaciones['porcentaje']) : 0;
        $porcentajeBonos = ($tipoBonos && isset($tipoBonos['porcentaje'])) ? floatval($tipoBonos['porcentaje']) : 0;

        if ($porcentajeLiquidaciones > 0 || $porcentajeBonos > 0) {
            if ($porcentajeBonos > 0 && $porcentajeLiquidaciones == 0) {
                $analisis[] = [
                    'categoria' => 'clima_laboral',
                    'tipo' => 'success',
                    'titulo' => '✅ Excelente Clima Laboral',
                    'porcentaje' => $porcentajeBonos,
                    'ratio' => 'Solo bonos',
                    'mensaje' => 'Clima laboral excelente: ' . $porcentajeBonos . '% en bonos sin liquidaciones.',
                    'recomendacion' => 'Mantén esta inversión en personal y estrategias de retención.'
                ];
            } elseif ($porcentajeBonos > $porcentajeLiquidaciones && $porcentajeBonos > 0) {
                $ratioBonos = $porcentajeBonos / ($porcentajeLiquidaciones ?: 1);
                $analisis[] = [
                    'categoria' => 'clima_laboral',
                    'tipo' => 'success',
                    'titulo' => '✅ Buen Clima Laboral',
                    'porcentaje' => $porcentajeBonos + $porcentajeLiquidaciones,
                    'ratio' => number_format($ratioBonos, 1) . ':1',
                    'mensaje' => 'Buen clima: más inversión en bonos (' . $porcentajeBonos . '%) que en liquidaciones (' . $porcentajeLiquidaciones . '%).',
                    'recomendacion' => 'Continúa enfocándote en retención mediante incentivos.'
                ];
            } elseif ($porcentajeLiquidaciones > $porcentajeBonos) {
                $analisis[] = [
                    'categoria' => 'clima_laboral',
                    'tipo' => 'danger',
                    'titulo' => '🚨 Clima Laboral Problemático',
                    'porcentaje' => $porcentajeBonos + $porcentajeLiquidaciones,
                    'ratio' => 'Más liquidaciones',
                    'mensaje' => 'Clima problemático: más liquidaciones (' . $porcentajeLiquidaciones . '%) que bonos (' . $porcentajeBonos . '%).',
                    'recomendacion' => 'Urgente: mejora condiciones laborales e implementa programa de retención.'
                ];
            } else {
                $analisis[] = [
                    'categoria' => 'clima_laboral',
                    'tipo' => 'warning',
                    'titulo' => '⚠️ Clima Laboral Neutral',
                    'porcentaje' => $porcentajeBonos + $porcentajeLiquidaciones,
                    'ratio' => 'Equilibrado',
                    'mensaje' => 'Clima neutral con equilibrio entre bonos y liquidaciones.',
                    'recomendacion' => 'Mejora estrategias de retención para reducir rotación.'
                ];
            }
        } else {
            $analisis[] = [
                'categoria' => 'clima_laboral',
                'tipo' => 'info',
                'titulo' => '📊 Sin Análisis de Clima Laboral',
                'porcentaje' => 0,
                'ratio' => 'Sin datos',
                'mensaje' => 'No hay datos de liquidaciones ni bonos para analizar clima.',
                'recomendacion' => 'Implementa sistema de bonos y monitorea rotación de personal.'
            ];
        }

        return $analisis;
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
