<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Planilla;
use App\Models\PlanillaCategoria;
use App\Models\PlanillaTipo;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\PlanillaResource;
use App\Models\EmpleadoCategoria;

class PlanillaController extends Controller
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
            $moduleTitle = "Planillas";

            $dataGraficaMes = $this->graficaMes($year);

            $registrosMesActual = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1) // ✅ Agregar filtro
                ->get();

            $totalMes = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');
            $totalMes = "L. " . number_format($totalMes, 2, '.', ',');

            $totalMesYearAnterior = Planilla::whereBetween('fecha', [$fecha_inicial_year_anterior, $fecha_final_year_anterior])
                ->where('id_estado', 1)
                ->sum('total');
            $totalMesYearAnterior = "L. " . number_format($totalMesYearAnterior, 2, '.', ',');

            $totalAnual = Planilla::whereYear('fecha', $year)
                ->where('id_estado', 1)
                ->sum('total');
            $totalAnual = "L. " . number_format($totalAnual, 2, '.', ',');

            $tiposMes = $this->tiposMes($year, $mes);
            $categoriasMes = $this->categoriasMes($year, $mes);
            $analisisMes = $this->analisisMensual($categoriasMes, $tiposMes);
            $empleadosMes = $this->empleadosMes($year, $mes);
            $puestosMes = $this->puestosMes($year, $mes);
            $areasMes = $this->areasMes($year, $mes);

            $data = PlanillaResource::collection(
                Planilla::with(['categoria', 'estado', 'usuario', 'empleado.categoria']) // ✅ Agregar empleado.categoria
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
                    'empleadosMes' => $empleadosMes,
                    'puestosMes' => $puestosMes,
                    'areasMes' => $areasMes,
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

                $total = Planilla::whereBetween('fecha', [$fechaInicial, $fechaFinal])
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
            $categorias = PlanillaCategoria::all();

            $total = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            if ($total <= 0) {
                return [];
            }

            foreach ($categorias as $categoria) {
                $totalPlanilla = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                    ->where('id_estado', 1)
                    ->where('id_categoria', $categoria->id)
                    ->sum('total');

                if ($totalPlanilla > 0) {
                    $porcentaje = ($totalPlanilla * 100) / $total;
                    $porcentaje = number_format($porcentaje, 2, '.', '');

                    $dataCategorias[] = [
                        'descripcion' => $categoria->descripcion,
                        'total' => $totalPlanilla,
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
    public function empleadosMes($year, $mes)
    {

        try {
            $fecha_inicial = Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');

            $dataCategorias = [];
            $categorias = Empleado::all();

            $total = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            if ($total <= 0) {
                return [];
            }

            foreach ($categorias as $categoria) {
                $totalPlanilla = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                    ->where('id_estado', 1)
                    ->where('id_empleado', $categoria->id)
                    ->sum('total');

                if ($totalPlanilla > 0) {
                    $porcentaje = ($totalPlanilla * 100) / $total;
                    $porcentaje = number_format($porcentaje, 2, '.', '');

                    $dataCategorias[] = [
                        'descripcion' => $categoria->descripcion,
                        'total' => $totalPlanilla,
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
    public function puestosMes($year, $mes)
    {
        try {
            $fecha_inicial = Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');

            $puestos_categorias = [];
            $puestos = EmpleadoCategoria::all();

            $totalMes = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            Log::info('Total Planilla Puesto Mes:', ['total' => $totalMes]);

            // ✅ CORREGIDO: Iterar por cada categoría de empleado (puesto)
            foreach ($puestos as $puesto) {
                $id_categoria_empleado = $puesto->id;

                // ✅ CONCATENAR: Descripción + Área + Rango
                $descripcion_completa = $puesto->descripcion;
                if (!empty($puesto->area)) {
                    $descripcion_completa = "Puesto: " . $descripcion_completa . " - Area: " . $puesto->area;
                }

                // ✅ CORREGIDO: Sumar planillas de empleados que pertenecen a esta categoría
                $totalPuesto = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                    ->where('id_estado', 1)
                    ->whereHas('empleado', function ($query) use ($id_categoria_empleado) {
                        $query->where('id_categoria', $id_categoria_empleado);
                    })
                    ->sum('total');

                // ✅ SOLO INCLUIR si el total es mayor a 0
                if ($totalPuesto > 0) {
                    $porcentaje = $totalMes > 0 ? ($totalPuesto * 100) / $totalMes : 0;
                    $porcentaje = number_format($porcentaje, 2, '.', '');

                    $puestos_categorias[] = [
                        'descripcion' => $descripcion_completa,
                        'total' => $totalPuesto,
                        'porcentaje' => $porcentaje
                    ];
                }
            }

            // Ordenar por total descendente
            usort($puestos_categorias, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            Log::info('Puestos de empleados procesados:', [
                'total_puestos' => count($puestos_categorias),
                'puestos' => $puestos_categorias
            ]);

            return $puestos_categorias;
        } catch (\Throwable $th) {
            Log::error('Error en puestosMes:', ['error' => $th->getMessage()]);
            return [];
        }
    }


    public function tiposMes($year, $mes)
    {
        try {
            $fecha_inicial = Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');

            $tipos_categorias = [];
            $tipos = PlanillaTipo::all();
            $categorias = PlanillaCategoria::all();

            $totalMes = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            Log::info('Total Planilla Tipo Mes:', ['total' => $totalMes]);

            // ✅ Incluir todos los tipos, incluso los que tienen 0
            foreach ($tipos as $tipo) {
                $contadorTipo = 0;
                $id_tipo = $tipo->id;
                $descripcion_tipo = $tipo->descripcion;

                foreach ($categorias as $categoria) {
                    if ($categoria->id_tipo == $id_tipo) {
                        $totalCategoria = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
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

            Log::info('Tipos de planilla procesados:', [
                'total_tipos' => count($tipos_categorias),
                'tipos' => $tipos_categorias
            ]);

            return $tipos_categorias;
        } catch (\Throwable $th) {
            Log::error('Error en tiposMes planillas:', ['error' => $th->getMessage()]);
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

    public function areasMes($year, $mes)
    {
        try {
            $fecha_inicial = Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');

            Log::info('DEBUG areasMes - Iniciando:', [
                'year' => $year,
                'mes' => $mes,
                'fecha_inicial' => $fecha_inicial,
                'fecha_final' => $fecha_final
            ]);

            // ✅ Ahora que las relaciones están correctas, usar whereHas
            $areas_categorias = [];

            // Obtener áreas únicas que tienen planillas
            $areas = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->with('empleado.categoria')
                ->get()
                ->pluck('empleado.categoria.area')
                ->filter() // Quitar nulls
                ->unique()
                ->values();

            Log::info('DEBUG areasMes - Áreas encontradas:', [
                'areas_count' => $areas->count(),
                'areas' => $areas->toArray()
            ]);

            $totalMes = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');

            Log::info('DEBUG areasMes - Total del mes:', ['total' => $totalMes]);

            // Iterar por cada área
            foreach ($areas as $area_nombre) {
                if (empty($area_nombre)) continue;

                Log::info('DEBUG areasMes - Procesando área:', ['area' => $area_nombre]);

                // ✅ Usar whereHas con relaciones correctas
                $totalArea = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                    ->where('id_estado', 1)
                    ->whereHas('empleado.categoria', function ($query) use ($area_nombre) {
                        $query->where('area', $area_nombre);
                    })
                    ->sum('total');

                Log::info('DEBUG areasMes - Total área:', [
                    'area' => $area_nombre,
                    'total' => $totalArea
                ]);

                if ($totalArea > 0) {
                    $porcentaje = $totalMes > 0 ? ($totalArea * 100) / $totalMes : 0;
                    $porcentaje = number_format($porcentaje, 2, '.', '');

                    $areas_categorias[] = [
                        'descripcion' => "Área: " . $area_nombre,
                        'total' => $totalArea,
                        'porcentaje' => $porcentaje
                    ];
                }
            }

            // Verificar empleados sin área
            $totalSinArea = Planilla::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->whereHas('empleado.categoria', function ($query) {
                    $query->where(function ($q) {
                        $q->whereNull('area')
                            ->orWhere('area', '')
                            ->orWhere('area', 'Sin área');
                    });
                })
                ->sum('total');

            if ($totalSinArea > 0) {
                $porcentajeSinArea = $totalMes > 0 ? ($totalSinArea * 100) / $totalMes : 0;
                $porcentajeSinArea = number_format($porcentajeSinArea, 2, '.', '');

                $areas_categorias[] = [
                    'descripcion' => "Área: Sin área definida",
                    'total' => $totalSinArea,
                    'porcentaje' => $porcentajeSinArea
                ];
            }

            // Ordenar por total descendente
            usort($areas_categorias, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            Log::info('DEBUG areasMes - Resultado final:', [
                'total_areas' => count($areas_categorias),
                'areas' => $areas_categorias
            ]);

            return $areas_categorias;
        } catch (\Throwable $th) {
            Log::error('Error en areasMes:', [
                'error' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile()
            ]);
            return [];
        }
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
