<?php

namespace App\Http\Controllers;

use App\Http\Resources\GastoResource;
use App\Models\Gasto;
use App\Models\GastoCategoria;
use App\Models\GastoTipo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Arr;
use Carbon\Carbon; 
use Illuminate\Support\Facades\Log;



class GastoController extends Controller
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


            $tableHeaders = array(
                1 => "ID",
                2 => "Fecha",
                3 => "Descripcion",
                4 => "Categoria",
                5 => "Total",
            );

            $moduleName = "ingreso";
            $moduleTitle = "Gastos";

            $dataGraficaMes = $this->graficaMes($year);

            $registrosMesActual = Gasto::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1) // ✅ Agregar filtro
                ->get();

            $totalMes = Gasto::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');
            $totalMes = "L. " . number_format($totalMes, 2, '.', ',');

            $totalMesYearAnterior = Gasto::whereBetween('fecha', [$fecha_inicial_year_anterior, $fecha_final_year_anterior])
                ->where('id_estado', 1)
                ->sum('total');
            $totalMesYearAnterior = "L. " . number_format($totalMesYearAnterior, 2, '.', ',');

            $totalAnual = Gasto::whereYear('fecha', $year)
                ->where('id_estado', 1)
                ->sum('total');
            $totalAnual = "L. " . number_format($totalAnual, 2, '.', ',');

            $tiposMes = $this->tiposMes($year, $mes);
            $categoriasMes = $this->categoriasMes($year, $mes);
            $analisisMes = $this->analisisMensual($categoriasMes, $tiposMes);

            $data = GastoResource::collection(
                Gasto::with(['categoria', 'estado', 'usuario'])
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

                $total = Gasto::whereBetween('fecha', [$fechaInicial, $fechaFinal])
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
            $tipos = GastoTipo::all();
            $categorias = GastoCategoria::all();

            $totalMes = Gasto::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            Log::info('Total Gasto Tipo Mes:', ['total' => $totalMes]);

            if ($totalMes <= 0) {
                return [];
            }

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
        
        // ✅ Validación mejorada
        if (!$tipos || !is_array($tipos)) {
            Log::warning('Tipos de gastos inválidos:', [
                'tipos' => $tipos,
                'es_array' => is_array($tipos)
            ]);
            
            return [
                [
                    'categoria' => 'error',
                    'tipo' => 'info',
                    'titulo' => '📊 Sin Datos de Gastos',
                    'porcentaje' => 0,
                    'ratio' => null,
                    'mensaje' => 'No hay datos de gastos para analizar este mes.',
                    'recomendacion' => 'Verifica que existan registros de gastos para este período.'
                ]
            ];
        }

        // ✅ Log de debug
        Log::info('Análisis iniciado:', [
            'tipos_count' => count($tipos),
            'tipos_disponibles' => array_column($tipos, 'descripcion')
        ]);

        // 📊 Configuración de análisis por tipo de gasto
        $tiposAnalisis = [
            'Gastos Fijos' => [
                'categoria' => 'gastos_fijos',
                'rangos' => [
                    ['min' => 0, 'max' => 25, 'tipo' => 'success', 'titulo' => '✅ Gastos Fijos Controlados'],
                    ['min' => 26, 'max' => 35, 'tipo' => 'warning', 'titulo' => '⚠️ Gastos Fijos Moderados'],
                    ['min' => 36, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Gastos Fijos Excesivos']
                ],
                'mensajes' => [
                    'success' => 'Excelente control de gastos fijos. Estructura eficiente que permite flexibilidad operativa.',
                    'warning' => 'Gastos fijos moderados. Requiere atención para evitar rigidez financiera.',
                    'danger' => 'Nivel peligroso de gastos fijos que reduce capacidad de adaptación.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén este nivel eficiente y busca optimización continua.',
                    'warning' => 'Revisa contratos y considera convertir algunos gastos fijos en variables.',
                    'danger' => 'Urgente: reestructura gastos fijos y renegocia contratos.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron gastos fijos este mes.',
                    'recomendacion' => 'Verifica registro de gastos fijos recurrentes (alquiler, servicios, etc.).'
                ]
            ],
            'Gastos Variables' => [
                'categoria' => 'gastos_variables',
                'rangos' => [
                    ['min' => 40, 'max' => 70, 'tipo' => 'success', 'titulo' => '✅ Gastos Variables Óptimos'],
                    ['min' => 25, 'max' => 39, 'tipo' => 'warning', 'titulo' => '⚠️ Gastos Variables Bajos'],
                    ['min' => 71, 'max' => 100, 'tipo' => 'warning', 'titulo' => '⚠️ Gastos Variables Altos'],
                    ['min' => 0, 'max' => 24, 'tipo' => 'danger', 'titulo' => '🚨 Gastos Variables Insuficientes']
                ],
                'mensajes' => [
                    'success' => 'Excelente proporción de gastos variables que indica flexibilidad operativa.',
                    'warning' => 'Proporción de gastos variables que requiere atención.',
                    'danger' => 'Proporción muy baja de gastos variables indica falta de inversión en crecimiento.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén esta flexibilidad y optimiza el retorno.',
                    'warning' => 'Evalúa oportunidades de inversión variable para crecimiento.',
                    'danger' => 'Considera aumentar inversiones variables en marketing y desarrollo.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron gastos variables este mes.',
                    'recomendacion' => 'Verifica si hay gastos variables sin registrar.'
                ]
            ],
            'Gastos Directos' => [
                'categoria' => 'gastos_directos',
                'rangos' => [
                    ['min' => 50, 'max' => 75, 'tipo' => 'success', 'titulo' => '✅ Gastos Directos Eficientes'],
                    ['min' => 35, 'max' => 49, 'tipo' => 'warning', 'titulo' => '⚠️ Gastos Directos Moderados'],
                    ['min' => 76, 'max' => 100, 'tipo' => 'warning', 'titulo' => '⚠️ Gastos Directos Altos'],
                    ['min' => 0, 'max' => 34, 'tipo' => 'danger', 'titulo' => '🚨 Gastos Directos Insuficientes']
                ],
                'mensajes' => [
                    'success' => 'Excelente enfoque en gastos directos relacionados con generación de ingresos.',
                    'warning' => 'Proporción de gastos directos que necesita atención.',
                    'danger' => 'Proporción muy baja de gastos directos indica exceso de gastos indirectos.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén este enfoque eficiente y maximiza el retorno.',
                    'warning' => 'Analiza oportunidades para aumentar gastos directos.',
                    'danger' => 'Urgente: reduce gastos indirectos y enfócate en gastos productivos.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron gastos directos este mes. Falta de inversión en actividades generadoras.',
                    'recomendacion' => 'Urgente: verifica gastos directos o evalúa actividades productivas.'
                ]
            ],
            'Gastos Indirectos' => [
                'categoria' => 'gastos_indirectos',
                'rangos' => [
                    ['min' => 0, 'max' => 25, 'tipo' => 'success', 'titulo' => '✅ Gastos Indirectos Controlados'],
                    ['min' => 26, 'max' => 35, 'tipo' => 'warning', 'titulo' => '⚠️ Gastos Indirectos Moderados'],
                    ['min' => 36, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Gastos Indirectos Excesivos']
                ],
                'mensajes' => [
                    'success' => 'Excelente control de gastos indirectos con estructura administrativa eficiente.',
                    'warning' => 'Gastos indirectos requieren monitoreo.',
                    'danger' => 'Nivel preocupante de gastos indirectos que afecta rentabilidad.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén este control y optimiza procesos administrativos.',
                    'warning' => 'Revisa gastos administrativos y busca eficiencias.',
                    'danger' => 'Urgente: reduce gastos indirectos y elimina redundancias.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron gastos indirectos este mes.',
                    'recomendacion' => 'Verifica si hay gastos administrativos sin registrar.'
                ]
            ],
            'Gastos Extraordinarios' => [
                'categoria' => 'gastos_extraordinarios',
                'rangos' => [
                    ['min' => 0, 'max' => 5, 'tipo' => 'success', 'titulo' => '✅ Gastos Extraordinarios Normales'],
                    ['min' => 6, 'max' => 15, 'tipo' => 'warning', 'titulo' => '⚠️ Gastos Extraordinarios Moderados'],
                    ['min' => 16, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Gastos Extraordinarios Excesivos']
                ],
                'mensajes' => [
                    'success' => 'Nivel normal de gastos extraordinarios con operaciones estables.',
                    'warning' => 'Nivel moderado de gastos extraordinarios que impacta presupuesto.',
                    'danger' => 'Nivel muy alto de gastos extraordinarios que afecta finanzas.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén este control y crea un fondo de emergencia.',
                    'warning' => 'Analiza causas y busca formas de prevenir estos gastos.',
                    'danger' => 'Urgente: identifica causas e implementa medidas preventivas.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron gastos extraordinarios este mes.',
                    'recomendacion' => 'Excelente! Mantén esta estabilidad operativa.'
                ]
            ]
        ];

        // 🔄 Procesar cada tipo de análisis
        foreach ($tiposAnalisis as $tipoNombre => $config) {
            // ✅ Buscar datos del tipo de manera segura
            $tipoData = collect($tipos)->where('descripcion', $tipoNombre)->first();
            
            if ($tipoData && isset($tipoData['porcentaje']) && floatval($tipoData['porcentaje']) > 0) {
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
                // ✅ Caso sin datos - SIEMPRE se agrega
                $analisis[] = [
                    'categoria' => $config['categoria'],
                    'tipo' => 'info',
                    'titulo' => '📊 Sin Datos de ' . str_replace(['Gastos '], [''], $tipoNombre),
                    'porcentaje' => 0,
                    'ratio' => null,
                    'mensaje' => $config['sin_datos']['mensaje'],
                    'recomendacion' => $config['sin_datos']['recomendacion']
                ];
            }
        }

        // 🔍 6. ANÁLISIS DE EFICIENCIA DE GASTOS (Directos vs Indirectos)
        $tipoDirectos = collect($tipos)->where('descripcion', 'Gastos Directos')->first();
        $tipoIndirectos = collect($tipos)->where('descripcion', 'Gastos Indirectos')->first();
        
        // ✅ Manejo seguro de porcentajes
        $porcentajeDirectos = ($tipoDirectos && isset($tipoDirectos['porcentaje'])) ? floatval($tipoDirectos['porcentaje']) : 0;
        $porcentajeIndirectos = ($tipoIndirectos && isset($tipoIndirectos['porcentaje'])) ? floatval($tipoIndirectos['porcentaje']) : 0;
        
        // ✅ CORREGIDO: Analizar si hay AL MENOS uno de los dos tipos
        if ($porcentajeDirectos > 0 || $porcentajeIndirectos > 0) {
            if ($porcentajeDirectos > 0 && $porcentajeIndirectos > 0) {
                // Caso 1: Ambos tipos tienen datos - Calcular ratio normal
                $ratioDirectoIndirecto = $porcentajeDirectos / $porcentajeIndirectos;
                
                if ($ratioDirectoIndirecto >= 3) {
                    $analisis[] = [
                        'categoria' => 'eficiencia_gastos',
                        'tipo' => 'success',
                        'titulo' => '✅ Excelente Eficiencia de Gastos',
                        'porcentaje' => $porcentajeDirectos + $porcentajeIndirectos,
                        'ratio' => number_format($ratioDirectoIndirecto, 1) . ':1',
                        'mensaje' => 'Excelente ratio entre gastos directos (' . $porcentajeDirectos . '%) e indirectos (' . $porcentajeIndirectos . '%). La mayoría contribuyen directamente a generar ingresos.',
                        'recomendacion' => 'Mantén esta eficiencia y maximiza el retorno de los gastos directos.'
                    ];
                } elseif ($ratioDirectoIndirecto >= 1.5) {
                    $analisis[] = [
                        'categoria' => 'eficiencia_gastos',
                        'tipo' => 'warning',
                        'titulo' => '⚠️ Eficiencia de Gastos Aceptable',
                        'porcentaje' => $porcentajeDirectos + $porcentajeIndirectos,
                        'ratio' => number_format($ratioDirectoIndirecto, 1) . ':1',
                        'mensaje' => 'Ratio aceptable entre gastos directos (' . $porcentajeDirectos . '%) e indirectos (' . $porcentajeIndirectos . '%), con espacio para mejorar.',
                        'recomendacion' => 'Busca aumentar gastos directos o reducir indirectos para mejorar eficiencia.'
                    ];
                } else {
                    $analisis[] = [
                        'categoria' => 'eficiencia_gastos',
                        'tipo' => 'danger',
                        'titulo' => '🚨 Baja Eficiencia de Gastos',
                        'porcentaje' => $porcentajeDirectos + $porcentajeIndirectos,
                        'ratio' => number_format($ratioDirectoIndirecto, 1) . ':1',
                        'mensaje' => 'Ratio preocupante: gastos indirectos (' . $porcentajeIndirectos . '%) superan o igualan a directos (' . $porcentajeDirectos . '%).',
                        'recomendacion' => 'Urgente: reduce gastos indirectos y aumenta proporción de gastos directos.'
                    ];
                }
            } elseif ($porcentajeDirectos > 0) {
                // Caso 2: Solo gastos directos (EXCELENTE)
                if ($porcentajeDirectos >= 70) {
                    $analisis[] = [
                        'categoria' => 'eficiencia_gastos',
                        'tipo' => 'success',
                        'titulo' => '✅ Eficiencia Máxima - Solo Gastos Directos',
                        'porcentaje' => $porcentajeDirectos,
                        'ratio' => '100% directos',
                        'mensaje' => 'Excelente: todos los gastos (' . $porcentajeDirectos . '%) son directos y contribuyen a generar ingresos. Máxima eficiencia alcanzada.',
                        'recomendacion' => 'Perfecto enfoque. Mantén esta eficiencia al 100% en gastos directos.'
                    ];
                } elseif ($porcentajeDirectos >= 50) {
                    $analisis[] = [
                        'categoria' => 'eficiencia_gastos',
                        'tipo' => 'success',
                        'titulo' => '✅ Alta Eficiencia - Gastos Directos Dominantes',
                        'porcentaje' => $porcentajeDirectos,
                        'ratio' => 'Mayormente directos',
                        'mensaje' => 'Muy buena eficiencia: ' . $porcentajeDirectos . '% de gastos directos sin gastos indirectos significativos.',
                        'recomendacion' => 'Excelente enfoque. Continúa priorizando gastos que generen ingresos directamente.'
                    ];
                } else {
                    $analisis[] = [
                        'categoria' => 'eficiencia_gastos',
                        'tipo' => 'warning',
                        'titulo' => '⚠️ Gastos Directos Moderados',
                        'porcentaje' => $porcentajeDirectos,
                        'ratio' => 'Directos parciales',
                        'mensaje' => 'Solo ' . $porcentajeDirectos . '% son gastos directos. Faltan gastos indirectos registrados o hay otros tipos no clasificados.',
                        'recomendacion' => 'Verifica clasificación de gastos y aumenta proporción de gastos directos.'
                    ];
                }
            } else {
                // Caso 3: Solo gastos indirectos (PROBLEMÁTICO)
                if ($porcentajeIndirectos >= 70) {
                    $analisis[] = [
                        'categoria' => 'eficiencia_gastos',
                        'tipo' => 'danger',
                        'titulo' => '🚨 Eficiencia Crítica - Solo Gastos Indirectos',
                        'porcentaje' => $porcentajeIndirectos,
                        'ratio' => '100% indirectos',
                        'mensaje' => 'Crítico: todos los gastos (' . $porcentajeIndirectos . '%) son indirectos. Ninguno contribuye directamente a generar ingresos.',
                        'recomendacion' => 'Urgente: invierte inmediatamente en gastos directos que generen ingresos (marketing, ventas, producción).'
                    ];
                } elseif ($porcentajeIndirectos >= 50) {
                    $analisis[] = [
                        'categoria' => 'eficiencia_gastos',
                        'tipo' => 'danger',
                        'titulo' => '🚨 Baja Eficiencia - Gastos Indirectos Dominantes',
                        'porcentaje' => $porcentajeIndirectos,
                        'ratio' => 'Mayormente indirectos',
                        'mensaje' => 'Preocupante: ' . $porcentajeIndirectos . '% son gastos indirectos sin gastos directos significativos.',
                        'recomendacion' => 'Crítico: reorienta presupuesto hacia gastos que generen ingresos directamente.'
                    ];
                } else {
                    $analisis[] = [
                        'categoria' => 'eficiencia_gastos',
                        'tipo' => 'warning',
                        'titulo' => '⚠️ Gastos Indirectos Parciales',
                        'porcentaje' => $porcentajeIndirectos,
                        'ratio' => 'Indirectos parciales',
                        'mensaje' => 'Solo ' . $porcentajeIndirectos . '% son gastos indirectos. Faltan gastos directos registrados o hay otros tipos no clasificados.',
                        'recomendacion' => 'Verifica clasificación de gastos y prioriza gastos directos generadores de ingresos.'
                    ];
                }
            }
        } else {
            // Caso 4: No hay datos de directos ni indirectos
            $analisis[] = [
                'categoria' => 'eficiencia_gastos',
                'tipo' => 'info',
                'titulo' => '📊 Sin Análisis de Eficiencia',
                'porcentaje' => 0,
                'ratio' => 'Sin datos',
                'mensaje' => 'No hay datos de gastos directos ni indirectos para analizar eficiencia.',
                'recomendacion' => 'Registra y clasifica correctamente los gastos directos e indirectos.'
            ];
        }

        // 🔍 7. ANÁLISIS DE FLEXIBILIDAD FINANCIERA (Variables vs Fijos)
        $tipoFijos = collect($tipos)->where('descripcion', 'Gastos Fijos')->first();
        $tipoVariables = collect($tipos)->where('descripcion', 'Gastos Variables')->first();
        
        $porcentajeFijos = $tipoFijos ? floatval($tipoFijos['porcentaje']) : 0;
        $porcentajeVariables = $tipoVariables ? floatval($tipoVariables['porcentaje']) : 0;
        $totalFlexibilidad = $porcentajeFijos + $porcentajeVariables;
        
        if ($totalFlexibilidad > 0) {
            $ratioVariableFijo = $porcentajeVariables / ($porcentajeFijos ?: 1);
            
            if ($ratioVariableFijo >= 2) {
                $analisis[] = [
                    'categoria' => 'flexibilidad_financiera',
                    'tipo' => 'success',
                    'titulo' => '✅ Alta Flexibilidad Financiera',
                    'porcentaje' => $totalFlexibilidad,
                    'ratio' => number_format($ratioVariableFijo, 1) . ':1',
                    'mensaje' => 'Excelente flexibilidad financiera con ' . $porcentajeVariables . '% gastos variables vs ' . $porcentajeFijos . '% fijos. Puedes adaptarte fácilmente a cambios en ingresos.',
                    'recomendacion' => 'Mantén esta flexibilidad que te permite adaptarte a variaciones en el negocio.'
                ];
            } elseif ($ratioVariableFijo >= 1) {
                $analisis[] = [
                    'categoria' => 'flexibilidad_financiera',
                    'tipo' => 'warning',
                    'titulo' => '⚠️ Flexibilidad Financiera Moderada',
                    'porcentaje' => $totalFlexibilidad,
                    'ratio' => number_format($ratioVariableFijo, 1) . ':1',
                    'mensaje' => 'Flexibilidad moderada con ' . $porcentajeVariables . '% gastos variables vs ' . $porcentajeFijos . '% fijos. Hay espacio para mejorar la adaptabilidad.',
                    'recomendacion' => 'Considera convertir algunos gastos fijos en variables cuando sea posible para aumentar flexibilidad.'
                ];
            } else {
                $analisis[] = [
                    'categoria' => 'flexibilidad_financiera',
                    'tipo' => 'danger',
                    'titulo' => '🚨 Baja Flexibilidad Financiera',
                    'porcentaje' => $totalFlexibilidad,
                    'ratio' => number_format($ratioVariableFijo, 1) . ':1',
                    'mensaje' => 'Baja flexibilidad con ' . $porcentajeVariables . '% gastos variables vs ' . $porcentajeFijos . '% fijos. Estructura rígida que dificulta adaptación.',
                    'recomendacion' => 'Urgente: reestructura gastos para aumentar flexibilidad. Renegocia contratos y busca alternativas variables.'
                ];
            }
        } else {
            $analisis[] = [
                'categoria' => 'flexibilidad_financiera',
                'tipo' => 'info',
                'titulo' => '📊 Sin Análisis de Flexibilidad',
                'porcentaje' => 0,
                'ratio' => 'Sin datos',
                'mensaje' => 'No se puede analizar flexibilidad financiera por falta de datos de gastos fijos y variables.',
                'recomendacion' => 'Registra y clasifica correctamente los gastos fijos y variables para este análisis.'
            ];
        }

        Log::info('Análisis completado:', ['analisis_count' => count($analisis)]);
        return $analisis;
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
     * @param  \App\Models\Gasto  $gasto
     * @return \Illuminate\Http\Response
     */
    public function show(Gasto $gasto)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Gasto  $gasto
     * @return \Illuminate\Http\Response
     */
    public function edit(Gasto $gasto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Gasto  $gasto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Gasto $gasto)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Gasto  $gasto
     * @return \Illuminate\Http\Response
     */
    public function destroy(Gasto $gasto)
    {
        //
    }
}
