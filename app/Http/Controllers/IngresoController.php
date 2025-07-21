<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ingreso;
use App\Models\IngresoCategoria;
use Illuminate\Support\Arr;
use App\Http\Resources\IngresoResource;
use App\Models\IngresoTipo;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; 

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

            $moduleName = "ingreso";
            $moduleTitle = "Ingresos";

            $dataGraficaMes = $this->graficaMes($year);

            $registrosMesActual = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1) // ✅ Agregar filtro
                ->get();

            $totalMes = Ingreso::whereBetween('fecha', [$fecha_inicial, $fecha_final])
                ->where('id_estado', 1)
                ->sum('total');
            $totalMes = "L. " . number_format($totalMes, 2, '.', ',');

            $totalMesYearAnterior = Ingreso::whereBetween('fecha', [$fecha_inicial_year_anterior, $fecha_final_year_anterior])
                ->where('id_estado', 1)
                ->sum('total');
            $totalMesYearAnterior = "L. " . number_format($totalMesYearAnterior, 2, '.', ',');

            $totalAnual = Ingreso::whereYear('fecha', $year)
                ->where('id_estado', 1)
                ->sum('total');
            $totalAnual = "L. " . number_format($totalAnual, 2, '.', ',');

            $tiposMes = $this->tiposMes($year, $mes);
            $categoriasMes = $this->categoriasMes($year, $mes);
            $analisisMes = $this->analisisMensual($categoriasMes, $tiposMes);

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

    public function categoriasMes($year, $mes)
    {

        try {
            $fecha_inicial = Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');

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

    public function tiposMes($year, $mes)
    {
        try {
            $fecha_inicial = Carbon::create($year, $mes, 1)->startOfMonth()->format('Y-m-d');
            $fecha_final = Carbon::create($year, $mes, 1)->endOfMonth()->format('Y-m-d');

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



    public function analisisMensual($categorias, $tipos)
    {
        $analisis = [];
        
        // 📊 Configuración de análisis por tipo
        $tiposAnalisis = [
            'Pasivos' => [
                'categoria' => 'pasivos',
                'rangos' => [
                    ['min' => 0, 'max' => 15, 'tipo' => 'success', 'titulo' => '✅ Situación Financiera Excelente'],
                    ['min' => 16, 'max' => 20, 'tipo' => 'warning', 'titulo' => '⚠️ En el Límite Superior Aceptable'],
                    ['min' => 21, 'max' => 100, 'tipo' => 'danger', 'titulo' => '🚨 Nivel de Riesgo Alto']
                ],
                'mensajes' => [
                    'success' => 'Este es un nivel óptimo. La empresa está generando suficientes ingresos operativos por sus servicios y productos. Los préstamos están siendo utilizados como apoyo o para inversión estratégica. Muestra solidez operativa y buena gestión financiera.',
                    'warning' => 'Estás justo en el límite superior aceptable, situación no crítica pero sí de atención. Puede indicar dependencia del financiamiento para cubrir operaciones o momentos de baja liquidez.',
                    'danger' => 'El nivel de pasivos supera el límite recomendado del 20%. Estás en zona de riesgo financiero. Una parte significativa de tus ingresos proviene de endeudamiento.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén este nivel y considera invertir en crecimiento del negocio.',
                    'warning' => 'Monitorea de cerca y evita aumentar más el endeudamiento. Evalúa si los préstamos están generando retorno de inversión.',
                    'danger' => 'Es urgente reducir el endeudamiento. Prioriza el pago de deudas y evita nuevos préstamos. Establece un plan para aumentar los ingresos operacionales.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron ingresos por pasivos este mes. Esto indica una situación financiera saludable sin dependencia de préstamos.',
                    'recomendacion' => 'Excelente! Continúa operando sin depender de financiamiento externo.'
                ]
            ],
            'Ingresos Operacionales' => [
                'categoria' => 'ingresos_operacionales',
                'rangos' => [
                    ['min' => 80, 'max' => 100, 'tipo' => 'success', 'titulo' => '✅ Excelente Base Operativa'],
                    ['min' => 70, 'max' => 79, 'tipo' => 'warning', 'titulo' => '⚠️ Base Operativa Aceptable'],
                    ['min' => 50, 'max' => 69, 'tipo' => 'warning', 'titulo' => '⚠️ Base Operativa Débil'],
                    ['min' => 0, 'max' => 49, 'tipo' => 'danger', 'titulo' => '🚨 Crisis en Ingresos Operacionales']
                ],
                'mensajes' => [
                    'success' => 'Excelente nivel de ingresos operacionales. Tu empresa tiene una base sólida de ingresos provenientes de sus actividades principales. Esto indica una operación saludable y sostenible.',
                    'warning' => 'Los ingresos operacionales necesitan fortalecerse. La empresa depende de otras fuentes de ingresos, lo cual puede ser riesgoso para la sostenibilidad.',
                    'danger' => 'Nivel crítico de ingresos operacionales. La empresa depende excesivamente de fuentes no operacionales, lo cual compromete su sostenibilidad a largo plazo.'
                ],
                'recomendaciones' => [
                    'success' => 'Mantén este nivel y busca optimizar la eficiencia operativa para maximizar estos ingresos.',
                    'warning' => 'Urgente: enfócate en fortalecer el negocio principal. Mejora productos/servicios y estrategias de ventas.',
                    'danger' => 'Crítico: redefine la estrategia de negocio. Evalúa la viabilidad del modelo actual y considera cambios estructurales.'
                ],
                'sin_datos' => [
                    'mensaje' => 'No se registraron ingresos operacionales este mes. Esto puede indicar inactividad comercial o problemas en el registro de datos.',
                    'recomendacion' => 'Urgente: verifica registros o evalúa si la empresa está operando normalmente.'
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
                    // Manejar casos especiales para warnings de ingresos operacionales
                    $mensaje = $config['mensajes'][$rangoEncontrado['tipo']];
                    $recomendacion = $config['recomendaciones'][$rangoEncontrado['tipo']];
                    
                    // Ajustar mensajes específicos para ingresos operacionales
                    if ($config['categoria'] === 'ingresos_operacionales' && $rangoEncontrado['tipo'] === 'warning') {
                        if ($porcentaje >= 70) {
                            $mensaje = 'Nivel aceptable de ingresos operacionales, aunque hay espacio para mejorar. La empresa depende moderadamente de otras fuentes de ingresos.';
                            $recomendacion = 'Trabaja en aumentar los ingresos operacionales mediante mejora de servicios, eficiencia y marketing.';
                        }
                    }
                    
                    $analisis[] = [
                        'categoria' => $config['categoria'],
                        'tipo' => $rangoEncontrado['tipo'],
                        'titulo' => $rangoEncontrado['titulo'],
                        'porcentaje' => $porcentaje,
                        'ratio' => null,
                        'mensaje' => $mensaje,
                        'recomendacion' => $recomendacion
                    ];
                }
            } else {
                // Caso sin datos
                $analisis[] = [
                    'categoria' => $config['categoria'],
                    'tipo' => 'info',
                    'titulo' => '📊 Sin Datos de ' . ($tipoNombre === 'Ingresos Operacionales' ? 'Ingresos Operacionales' : 'Pasivos'),
                    'porcentaje' => 0,
                    'ratio' => null,
                    'mensaje' => $config['sin_datos']['mensaje'],
                    'recomendacion' => $config['sin_datos']['recomendacion']
                ];
            }
        }

        // 🔍 3. ANÁLISIS DEL BALANCE OPERACIONAL VS PASIVOS
        $tipoPasivos = collect($tipos)->where('descripcion', 'Pasivos')->first();
        $tipoOperacionales = collect($tipos)->where('descripcion', 'Ingresos Operacionales')->first();
        
        $porcentajePasivos = $tipoPasivos ? floatval($tipoPasivos['porcentaje']) : 0;
        $porcentajeOperacionales = $tipoOperacionales ? floatval($tipoOperacionales['porcentaje']) : 0;

        // Configuración del análisis de balance
        $balanceConfig = [
            'sin_deuda' => [
                'tipo' => 'success',
                'titulo' => '✅ Balance Financiero Perfecto',
                'mensaje' => 'Situación financiera ideal. Tienes {operacionales}% de ingresos operacionales sin ninguna dependencia de pasivos o financiamiento externo. Esto indica máxima autonomía financiera.',
                'recomendacion' => 'Excelente posición financiera. Considera invertir en crecimiento, mejoras o crear reservas para oportunidades futuras.'
            ],
            'ratio_excelente' => [
                'tipo' => 'success',
                'titulo' => '✅ Balance de Ingresos Excelente',
                'mensaje' => 'Excelente balance entre ingresos operacionales ({operacionales}%) y pasivos ({pasivos}%). Tu empresa genera suficientes ingresos operativos para cubrir ampliamente cualquier dependencia de financiamiento.',
                'recomendacion' => 'Mantén este balance saludable y considera usar excedentes para inversiones estratégicas o reducir pasivos.'
            ],
            'ratio_aceptable' => [
                'tipo' => 'warning',
                'titulo' => '⚠️ Balance de Ingresos Aceptable',
                'mensaje' => 'Balance aceptable entre ingresos operacionales ({operacionales}%) y pasivos ({pasivos}%), aunque hay dependencia notable de financiamiento.',
                'recomendacion' => 'Trabaja en aumentar ingresos operacionales o reducir dependencia de financiamiento para mejorar el ratio.'
            ],
            'ratio_riesgoso' => [
                'tipo' => 'danger',
                'titulo' => '🚨 Balance de Ingresos Riesgoso',
                'mensaje' => 'Balance preocupante. Los pasivos ({pasivos}%) representan una proporción muy alta comparado con los ingresos operacionales ({operacionales}%).',
                'recomendacion' => 'Urgente: reduce pasivos y fortalece ingresos operacionales para mejorar la sostenibilidad financiera.'
            ],
            'crisis_financiera' => [
                'tipo' => 'danger',
                'titulo' => '🚨 Crisis Financiera Severa',
                'mensaje' => 'Situación crítica: tienes {pasivos}% de pasivos pero 0% de ingresos operacionales. Esto indica dependencia total de financiamiento sin actividad comercial.',
                'recomendacion' => 'Crisis financiera: urgente reactivar operaciones comerciales y generar ingresos operacionales inmediatamente.'
            ],
            'sin_actividad' => [
                'tipo' => 'info',
                'titulo' => '📊 Sin Actividad Financiera',
                'mensaje' => 'No se registraron ingresos operacionales ni pasivos este mes. Esto puede indicar inactividad comercial o problemas en el registro de datos.',
                'recomendacion' => 'Verifica si la empresa está operando normalmente y si todos los ingresos están siendo registrados correctamente.'
            ]
        ];

        if ($porcentajeOperacionales > 0) {
            if ($porcentajePasivos == 0) {
                // Caso ideal: Ingresos operacionales sin pasivos
                $config = $balanceConfig['sin_deuda'];
                $analisis[] = [
                    'categoria' => 'balance_general',
                    'tipo' => $config['tipo'],
                    'titulo' => $config['titulo'],
                    'porcentaje' => $porcentajeOperacionales,
                    'ratio' => 'Sin deuda',
                    'mensaje' => str_replace('{operacionales}', $porcentajeOperacionales, $config['mensaje']),
                    'recomendacion' => $config['recomendacion']
                ];
            } elseif ($porcentajePasivos > 0) {
                // Caso con pasivos: calcular ratio
                $ratioOperacionalPasivos = $porcentajeOperacionales / $porcentajePasivos;
                
                if ($ratioOperacionalPasivos >= 4) {
                    $config = $balanceConfig['ratio_excelente'];
                } elseif ($ratioOperacionalPasivos >= 2) {
                    $config = $balanceConfig['ratio_aceptable'];
                } else {
                    $config = $balanceConfig['ratio_riesgoso'];
                }
                
                $analisis[] = [
                    'categoria' => 'balance_general',
                    'tipo' => $config['tipo'],
                    'titulo' => $config['titulo'],
                    'porcentaje' => $porcentajeOperacionales,
                    'ratio' => number_format($ratioOperacionalPasivos, 1) . ':1',
                    'mensaje' => str_replace(['{operacionales}', '{pasivos}'], [$porcentajeOperacionales, $porcentajePasivos], $config['mensaje']),
                    'recomendacion' => $config['recomendacion']
                ];
            }
        } else {
            // Caso sin ingresos operacionales
            if ($porcentajePasivos > 0) {
                $config = $balanceConfig['crisis_financiera'];
                $analisis[] = [
                    'categoria' => 'balance_general',
                    'tipo' => $config['tipo'],
                    'titulo' => $config['titulo'],
                    'porcentaje' => $porcentajePasivos,
                    'ratio' => 'Solo pasivos',
                    'mensaje' => str_replace('{pasivos}', $porcentajePasivos, $config['mensaje']),
                    'recomendacion' => $config['recomendacion']
                ];
            } else {
                $config = $balanceConfig['sin_actividad'];
                $analisis[] = [
                    'categoria' => 'balance_general',
                    'tipo' => $config['tipo'],
                    'titulo' => $config['titulo'],
                    'porcentaje' => 0,
                    'ratio' => 'Sin datos',
                    'mensaje' => $config['mensaje'],
                    'recomendacion' => $config['recomendacion']
                ];
            }
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
