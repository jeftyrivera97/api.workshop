<?php

namespace App\Http\Controllers;

use App\Http\Resources\CompraResource;
use App\Models\Compra;
use App\Models\CompraCategoria;
use App\Models\CompraTipo;
use App\Models\Proveedor;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; 

class CompraController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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

            $moduleName = "compra";
            $moduleTitle = "Compras";

            $dataGraficaMes = $this->graficaMes($year);

            $registrosMesActual = Compra::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1) // ✅ Agregar filtro
                ->get();

            $totalMes = Compra::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');
            $totalMes = "L. " . number_format($totalMes, 2, '.', ',');

            $totalMesYearAnterior = Compra::whereBetween('fecha', [$fecha_inicial_year_anterior, $fecha_final_year_anterior])
                ->where('id_estado', 1)
                ->sum('total');
            $totalMesYearAnterior = "L. " . number_format($totalMesYearAnterior, 2, '.', ',');

            $totalAnual = Compra::whereYear('fecha', $year)
                ->where('id_estado', 1)
                ->sum('total');
            $totalAnual = "L. " . number_format($totalAnual, 2, '.', ',');

            $tiposMes = $this->tiposMes($year, $mes);
            $categoriasMes = $this->categoriasMes($year, $mes);
            $analisisMes = $this->analisisMensual($categoriasMes, $tiposMes);

            $data = CompraResource::collection(
                Compra::with(['categoria', 'estado', 'usuario'])
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
                    'totalMesYearAnterior' => $totalMesYearAnterior,
                    'tiposMes' => $tiposMes,
                    'categoriasMes' => $categoriasMes,
                    'analisisMensual' => $analisisMes,
                ]);

            Log::info('Data:', ['Data' => $data->toJson()]);
            return $data;
        } catch (\Throwable $th) {
            Log::info('Error recibido:', ['error' => $th->getMessage()]);
            return response()->json([
                'error' => 'Error al obtener los ingresos',
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

                $total = Compra::whereBetween('fecha', [$fechaInicial, $fechaFinal])
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

    public function categoriasMes($year, $mes)
    {

        try {
            $fecha_inicial = Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');

            $dataCategorias = [];
            $categorias = CompraCategoria::all();

            $total = Compra::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            if ($total <= 0) {
                return [];
            }

            foreach ($categorias as $categoria) {
                $totalIngreso = Compra::whereBetween('fecha', [$fecha_inicial, $fecha_final])
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

    public function tiposMes($year, $mes)
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

            if ($totalMes <= 0) {
                return [];
            }

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



    public function analisisMensual($categorias, $tipos)
    {
        $analisis = [];
        
        // 📊 Configuración de análisis por tipo
        $tiposAnalisis = [
            'Materia Prima de Produccion' => [
                'categoria' => 'materia_prima',
                'rangos' => [
                    ['min' => 0, 'max' => 39, 'tipo' => 'warning', 'titulo' => '⚠️ Baja Inversión en Materia Prima'],
                    ['min' => 40, 'max' => 60, 'tipo' => 'success', 'titulo' => '✅ Inversión Óptima en Materia Prima'],
                    ['min' => 61, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Sobre-inversión en Materia Prima']
                ],
                'mensajes' => [
                    'warning' => 'El porcentaje de materia prima está por debajo del rango recomendado. Esto puede afectar tu capacidad productiva.',
                    'success' => 'Excelente balance en materia prima. Estás invirtiendo adecuadamente en los insumos principales.',
                    'danger' => 'Gasto excesivo en materia prima. Puede indicar sobrestock, desperdicios o mala gestión de inventarios.'
                ],
                'recomendaciones' => [
                    'warning' => 'Evalúa si necesitas aumentar la compra de materiales productivos para mantener operaciones normales.',
                    'success' => 'Mantén este nivel y busca optimizar calidad y precios con proveedores.',
                    'danger' => 'Revisa inventarios, controla desperdicios, negocia mejores precios y optimiza la gestión de materiales.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron compras de materia prima este mes. Esto puede indicar uso de inventario existente.',
                    'recomendacion' => 'Verifica inventarios y asegúrate de registrar todas las compras de materia prima.'
                ]
            ],
            'Herramientas y Equipo' => [
                'categoria' => 'herramientas',
                'rangos' => [
                    ['min' => 0, 'max' => 9, 'tipo' => 'warning', 'titulo' => '⚠️ Baja Inversión en Herramientas'],
                    ['min' => 10, 'max' => 20, 'tipo' => 'success', 'titulo' => '✅ Inversión Equilibrada en Herramientas'],
                    ['min' => 21, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Alta Inversión en Herramientas']
                ],
                'mensajes' => [
                    'warning' => 'Baja inversión en herramientas y equipo. Puede ser normal si ya tienes el equipamiento necesario.',
                    'success' => 'Buena inversión en herramientas y equipo. Mantienes adecuadamente tus herramientas de trabajo.',
                    'danger' => 'Inversión alta en herramientas. Puede indicar una fase de expansión o posible sobre-inversión.'
                ],
                'recomendaciones' => [
                    'warning' => 'Evalúa el estado de tus herramientas actuales y considera si necesitas modernizar equipamiento.',
                    'success' => 'Continúa invirtiendo en equipos de calidad que mejoren eficiencia.',
                    'danger' => 'Verifica que esta inversión esté justificada y genere retorno productivo.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron compras de herramientas este mes. El equipamiento actual puede estar en buen estado.',
                    'recomendacion' => 'Mantén un programa de mantenimiento preventivo y planifica futuras inversiones.'
                ]
            ],
            'Insumos Operativos' => [
                'categoria' => 'insumos_operativos',
                'rangos' => [
                    ['min' => 0, 'max' => 14, 'tipo' => 'warning', 'titulo' => '⚠️ Bajos Insumos Operativos'],
                    ['min' => 15, 'max' => 25, 'tipo' => 'success', 'titulo' => '✅ Insumos Operativos Adecuados'],
                    ['min' => 26, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Exceso en Insumos Operativos']
                ],
                'mensajes' => [
                    'warning' => 'Porcentaje bajo de insumos operativos. Puede afectar las operaciones diarias.',
                    'success' => 'Nivel adecuado de insumos operativos. Las operaciones diarias están bien respaldadas.',
                    'danger' => 'Gasto excesivo en insumos operativos. Puede indicar ineficiencias en procesos.'
                ],
                'recomendaciones' => [
                    'warning' => 'Evalúa si necesitas más insumos para mantener operaciones eficientes.',
                    'success' => 'Mantén este nivel y optimiza el uso para evitar desperdicios.',
                    'danger' => 'Revisa procesos operativos y controla consumo para reducir desperdicios.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron compras de insumos operativos este mes. Esto puede afectar operaciones.',
                    'recomendacion' => 'Verifica si necesitas insumos operativos para mantener continuidad.'
                ]
            ],
            'Seguridad e Higiene' => [
                'categoria' => 'seguridad_higiene',
                'rangos' => [
                    ['min' => 0, 'max' => 2, 'tipo' => 'danger', 'titulo' => '🚨 Inversión Insuficiente en Seguridad'],
                    ['min' => 3, 'max' => 8, 'tipo' => 'success', 'titulo' => '✅ Seguridad e Higiene Adecuada'],
                    ['min' => 9, 'max' => 100, 'tipo' => 'warning', 'titulo' => '⚠️ Alta Inversión en Seguridad']
                ],
                'mensajes' => [
                    'danger' => 'Inversión muy baja en seguridad e higiene. Esto representa un riesgo para empleados.',
                    'success' => 'Buena inversión en seguridad e higiene. Proteges adecuadamente a tu equipo.',
                    'warning' => 'Inversión alta en seguridad. Puede indicar actualización de equipos.'
                ],
                'recomendaciones' => [
                    'danger' => 'Urgente: aumenta la inversión en seguridad para proteger empleados.',
                    'success' => 'Mantén este nivel y actualiza equipos de seguridad regularmente.',
                    'warning' => 'Verifica que esta inversión extra genere valor efectivo en seguridad.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron compras de seguridad este mes. Esto representa un riesgo para empleados.',
                    'recomendacion' => 'Urgente: evalúa las necesidades de seguridad y cumple con normativas laborales.'
                ]
            ],
            'Administrativos' => [
                'categoria' => 'administrativos',
                'rangos' => [
                    ['min' => 0, 'max' => 4, 'tipo' => 'warning', 'titulo' => '⚠️ Bajos Gastos Administrativos'],
                    ['min' => 5, 'max' => 12, 'tipo' => 'success', 'titulo' => '✅ Gastos Administrativos Controlados'],
                    ['min' => 13, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨Gastos Administrativos Elevados']
                ],
                'mensajes' => [
                    'info' => 'Gastos administrativos muy bajos. Puede indicar alta eficiencia.',
                    'success' => 'Gastos administrativos en rango saludable. Buena gestión de costos.',
                    'warning' => 'Gastos administrativos altos. Puede afectar la rentabilidad operativa.'
                ],
                'recomendaciones' => [
                    'info' => 'Evalúa si necesitas más recursos administrativos para mejorar gestión.',
                    'success' => 'Mantén este control y busca oportunidades de eficiencia.',
                    'warning' => 'Revisa gastos administrativos, elimina redundancias y optimiza procesos.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron gastos administrativos este mes. Puede indicar eficiencia.',
                    'recomendacion' => 'Verifica si hay gastos administrativos que deban registrarse.'
                ]
            ]
        ];

        // 🔄 Procesar cada tipo de análisis
        foreach ($tiposAnalisis as $tipoNombre => $config) {
            $tipoData = collect($tipos)->where('descripcion', $tipoNombre)->first();
            
            if ($tipoData) {
                $porcentaje = floatval($tipoData['porcentaje']);
                $rangoEncontrado = null;
                
                // Buscar el rango apropiado
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
                    'titulo' => '📊 Sin Datos de ' . str_replace(['Materia Prima de Produccion', 'Herramientas y Equipo', 'Insumos Operativos', 'Seguridad e Higiene'], ['Materia Prima', 'Herramientas', 'Insumos Operativos', 'Seguridad e Higiene'], $tipoNombre),
                    'porcentaje' => 0,
                    'ratio' => null,
                    'mensaje' => $config['sin_datos']['mensaje'],
                    'recomendacion' => $config['sin_datos']['recomendacion']
                ];
            }
        }

        // 🔍 6. ANÁLISIS DE EFICIENCIA PRODUCTIVA
        $materiaPrimaTipo = collect($tipos)->where('descripcion', 'Materia Prima de Produccion')->first();
        $insumosTipo = collect($tipos)->where('descripcion', 'Insumos Operativos')->first();
        
        $materiaPrimaPorcentaje = $materiaPrimaTipo ? floatval($materiaPrimaTipo['porcentaje']) : 0;
        $insumosPorcentaje = $insumosTipo ? floatval($insumosTipo['porcentaje']) : 0;
        $porcentajeProductivo = $materiaPrimaPorcentaje + $insumosPorcentaje;

        $eficienciaConfig = [
            ['min' => 55, 'max' => 85, 'tipo' => 'success', 'titulo' => '✅ Alta Eficiencia Productiva'],
            ['min' => 0, 'max' => 54, 'tipo' => 'warning', 'titulo' => '⚠️ Baja Eficiencia Productiva'],
            ['min' => 86, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Muy Alta Concentración Productiva']
        ];

        $eficienciaMensajes = [
            'success' => 'Excelente enfoque en gastos productivos (materia prima + insumos operativos).',
            'warning' => 'Bajo porcentaje en gastos productivos. Puede indicar sobre-inversión en gastos no productivos.',
            'danger' => 'Muy alta concentración en gastos productivos. Asegúrate de no descuidar otros aspectos.'
        ];

        $eficienciaRecomendaciones = [
            'success' => 'Mantén este enfoque productivo y controla gastos no productivos.',
            'warning' => 'Aumenta la proporción de gastos productivos (materia prima e insumos).',
            'danger' => 'Balancea con inversiones necesarias en herramientas, seguridad y administración.'
        ];

        foreach ($eficienciaConfig as $rango) {
            if ($porcentajeProductivo >= $rango['min'] && $porcentajeProductivo <= $rango['max']) {
                $analisis[] = [
                    'categoria' => 'eficiencia_productiva',
                    'tipo' => $rango['tipo'],
                    'titulo' => $rango['titulo'],
                    'porcentaje' => $porcentajeProductivo,
                    'ratio' => $materiaPrimaPorcentaje . '% + ' . $insumosPorcentaje . '%',
                    'mensaje' => $eficienciaMensajes[$rango['tipo']],
                    'recomendacion' => $eficienciaRecomendaciones[$rango['tipo']]
                ];
                break;
            }
        }

        return $analisis;
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
}
