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
            $extraParam = $request->get('extraParam'); // ✅ Recibe el parámetro
            Log::info('Parámetro recibido:', ['extraParam' => $extraParam]);

            $tableHeaders = array(
                1 => "ID",
                2 => "# Factura",
                3 => "Fecha",
                4 => "Descripcion",
                5 => "Categoria",
                6 => "Proveedor",
                7 => "Total",
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

            $moduleName = "compra";
            $moduleTitle = "Compras";

            $dataGraficaMes = $this->graficaMesActual($year);

            $registrosMesActual = Compra::whereBetween('fecha', [$fecha_inicial, $fecha_final])->where('id_estado', 1)->get();

            $totalMes = Compra::whereBetween('fecha', [$fecha_inicial, $fecha_final])->sum('total');
            if ($totalMes <= 0) {
                return 0; // Return 0 if no purchases in the month
                $tiposCategorias = [];
                $categoriasMes = [];
            } else {
                $totalMes = "L. " . number_format($totalMes, 2, '.', ',');
                $tiposCategorias = $this->tiposMes($year, $numMes);
                $categoriasMes = $this->categoriasMes($year, $numMes);
            }

            $totalMesAnterior = Compra::whereBetween('fecha', [$fecha_inicial_anterior, $fecha_final_anterior])->sum('total');
            $totalMesAnterior = "L. " . number_format($totalMesAnterior, 2, '.', ',');

            $totalAnual = Compra::where('fecha', '>=', "$year-01-01")->where('fecha', '<=', "$year-12-31")->sum('total');
            $totalAnual = "L. " . number_format($totalAnual, 2, '.', ',');

            $data = CompraResource::collection(
                Compra::with(['categoria', 'estado', 'usuario', 'proveedor'])
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
                'error' => 'Error al obtener las compras',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function graficaMesActual($year)
    {
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
        $categorias = CompraCategoria::all();
        $fecha_inicial = "$year-$mes-01";
        $fecha_final = "$year-$mes-31";

        for ($i = 0; $i < count($categorias); $i++) {
            $id = $categorias[$i]->id;
            $descripcion = $categorias[$i]->descripcion;
            $total = Compra::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->sum('total');

            if ($totalCompra = Compra::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->where('id_categoria', $id)->sum('total')) {
                $porcentaje = ($totalCompra * 100) / $total;
                $porcentaje = number_format($porcentaje, 2, '.', '');
                $dataCategorias[$i] = [
                    'descripcion' => $descripcion,
                    'total' => $totalCompra,
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

        $tipos = CompraTipo::all();
        $categorias = CompraCategoria::all();
        $contador = count($tipos);

        for ($i = 0; $i < count($tipos); $i++) {
            $contadorTipo = 0;
            $id_tipo = $tipos[$i]->id;
            $descripcion_tipo = $tipos[$i]->descripcion;

            for ($j = 0; $j < count($categorias); $j++) {

                $id_categoria = $categorias[$j]->id;
                $id_tipo_categoria = $categorias[$j]->id_tipo;

                if ($id_tipo == $id_tipo_categoria) {
                    $totalCategoria = Compra::where('fecha', '>=', $fecha_inicial)->where('fecha', '<=', $fecha_final)->where('id_categoria', $id_categoria)->sum('total');
                    $contadorTipo += $totalCategoria;
                }
            }
            $totalMes = Compra::whereBetween('fecha', [$fecha_inicial, $fecha_final])->sum('total');
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



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
}
