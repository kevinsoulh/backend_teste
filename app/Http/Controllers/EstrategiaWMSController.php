<?php

namespace App\Http\Controllers;

use App\Models\EstrategiaWMS;
use Illuminate\Support\Facades\DB;
use App\Models\EstrategiaWMSHorarioPrioridade;
use App\Http\Requests\StoreEstrategiaWMSRequest;

class EstrategiaWMSController extends Controller
{
    // Método para criar nova estratégia
    public function store(StoreEstrategiaWMSRequest $request)
    {
        if($request->wantsJson()) {
            DB::beginTransaction();

            try {
                $estrategiaWMS = EstrategiaWMS::create([
                    'ds_estrategia_wms' => $request->get('dsEstrategia'),
                    'nr_prioridade' => $request->get('nrPrioridade'),
                ]);

                if($estrategiaWMS && $request->filled('horarios')) {
                    foreach($request->get('horarios') as $horario) {
                        EstrategiaWMSHorarioPrioridade::create([
                            'cd_estrategia_wms' => $estrategiaWMS->cd_estrategia_wms,
                            'ds_horario_inicio' => $horario['dsHorarioInicio'],
                            'ds_horario_final' => $horario['dsHorarioFinal'],
                            'nr_prioridade' => $horario['nrPrioridade'],
                        ]);
                    }
                }

                DB::commit();
                return response()->json(['message' => 'Estratégia criada com sucesso!'], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
    }

    // Método para obter a prioridade baseada na hora e minuto
    public function getPrioridade($cdEstrategia, $dsHora, $dsMinuto)
    {
        try {
            $hora = $dsHora . ':' . $dsMinuto;

            $estrategiaWMS = EstrategiaWMS::find($cdEstrategia);

            if (!$estrategiaWMS) {
                return response()->json(['error' => 'Estratégia não encontrada'], 404);
            }

            $horarioPrioridade = EstrategiaWMSHorarioPrioridade::where('cd_estrategia_wms', $cdEstrategia, function($query) use ($hora) {
                $query->where('ds_horario_inicio', '<=', $hora)->where('ds_horario_final', '>=', $hora);
            })->orderBy('nr_prioridade', 'desc')->first();
                
            if ($horarioPrioridade) {
                return response()->json(['nrPrioridade' => $horarioPrioridade->nr_prioridade]);
            } else {
                return response()->json(['nrPrioridade' => $estrategiaWMS->nr_prioridade]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

   // Método para deletar uma estratégia
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $estrategiaWMS = EstrategiaWMS::find($id);
            if ($estrategiaWMS) {
                $estrategiaWMS->delete();
                DB::commit();
                return response()->json(['message' => 'Estratégia deletada com sucesso!']);
            } else {
                DB::rollBack();
                return response()->json(['error' => 'Estratégia não encontrada!'], 404);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao deletar a estratégia: ' . $e->getMessage()], 500);
        }
    }
}

