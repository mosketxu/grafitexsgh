<?php

namespace App\Http\Controllers;

use App\Imports\{MaestrosImport, MaestrosImportSGH};
use App\{Maestro,Ubicacion,Area, CampaignElemento, Carteleria, Elemento, Furniture, Material, Medida, Mobiliario, Segmento, Storeconcept,Propxelemento, TarifaFamilia};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class MaestroController extends Controller
{


    public function index(Request $request)
    {
        $sto=$request->get('sto');
        $nam=$request->get('nam');
        $coun=$request->get('coun');
        $are=$request->get('are');
        $seg=$request->get('seg');
        $cha=$request->get('cha');
        $clu=$request->get('clu');
        $conce=$request->get('conce');
        $fur=$request->get('fur');
        $ubi=$request->get('ubi');
        $mob=$request->get('mob');
        $cart=$request->get('cart');
        $mat=$request->get('mat');
        $med=$request->get('med');
        $propx=$request->get('propx');

        $maestros=Maestro::sto($request->sto)
        ->nam($request->nam)
        ->coun($request->coun)
        ->are($request->are)
        ->seg($request->seg)
        ->conce($request->conce)
        ->ubi($request->ubi)
        ->mob($request->mob)
        ->cart($request->cart)
        ->mat($request->mat)
        ->med($request->med)
        ->propx($request->propx)
        ->paginate(10);

        return view('maestro.index',compact('maestros','sto','nam','coun','are','seg','cha','clu','conce','fur','ubi','mob','cart','mat','med','propx'));
    }
 

    public function import(Request $request,$origen)
    {
        $request->validate([
            'maestro' => 'required|mimes:xls,xlsx',
            ]);
            
        DB::table('maestros')->delete();
            
        try{
            if($origen=="Grafitex"){
                (new MaestrosImport)->import(request()->file('maestro'));   
            }else{
                (new MaestrosImportSGH)->import(request()->file('maestro'));   
            }
        }catch(\ErrorException $ex){
            // dd($ex->getMessage());
            return back()->withError($ex->getMessage());
        }

        $notification = array(
            'message' => '??Maestro importado satisfactoriamente!',
            'alert-type' => 'success'
        );
        
        return redirect('/maestro')->with($notification);
        
    }

    public function actualizarTablas($origen)
    {
        //elimino los elementos de las stores para volver a a??adirlos
        DB::table('store_elementos')->delete();
        DB::table('elementos')->delete(); // si no los elimino es muy lento y salta

        //Inserto actualizo Ubicaciones
        $ubicaciones=Maestro::select('ubicacion')->distinct('ubicacion')->get()->toArray();
        foreach ($ubicaciones as $ubicacion){
            Ubicacion::firstOrCreate($ubicacion);
        }
        // Ubicacion::insert(Maestro::select('ubicacion')->distinct('ubicacion')->get()->toArray());
        $areas=Maestro::select('area')->distinct('area')->get()->toArray();
        foreach ($areas as $area){
            Area::firstOrCreate($area);
        }
        // Area::insert(Maestro::select('area')->distinct('area')->get()->toArray());
        $cartelerias=Maestro::select('carteleria')->distinct('carteleria')->get()->toArray();
        foreach ($cartelerias as $carteleria){
            Carteleria::firstOrCreate($carteleria);
        }
        // Carteleria::insert(Maestro::select('carteleria')->distinct('carteleria')->get()->toArray());
        $medidas=Maestro::select('medida')->distinct('medida')->get()->toArray();
        foreach ($medidas as $medida){
            Medida::firstOrCreate($medida);
        }
        // Medida::insert(Maestro::select('medida')->distinct('medida')->get()->toArray());
        $mobiliarios=Maestro::select('mobiliario')->distinct('mobiliario')->get()->toArray();
        foreach ($mobiliarios as $mobiliario){
            Mobiliario::firstOrCreate($mobiliario);
        }
        // Mobiliario::insert(Maestro::select('mobiliario')->distinct('mobiliario')->get()->toArray());
        $propxelementos=Maestro::select('propxelemento')->distinct('propxelemento')->get()->toArray();
        foreach ($propxelementos as $propxelemento){
            Propxelemento::firstOrCreate($propxelemento);
        }
        // Propxelemento::insert(Maestro::select('propxelemento')->distinct('propxelemento')->get()->toArray());
        $segmentos=Maestro::select('segmento')->distinct('segmento')->get()->toArray();
        foreach ($segmentos as $segmento){
            Segmento::firstOrCreate($segmento);
        }
        // Segmento::insert(Maestro::select('segmento')->distinct('segmento')->get()->toArray());
        if($origen=="SGH"){
            DB::table('furnitures')->delete();
            $furnitures=Maestro::select('furniture_type')->distinct('furniture_type')->get()->toArray();
            // foreach ($furnitures as $furniture){
            //     Furniture::firstOrCreate($furnitures);
            // }
            Furniture::insert(Maestro::select('furniture_type')->distinct('furniture_type')->get()->toArray());
        }
        $storeconcepts=Maestro::select('storeconcept')->distinct('storeconcept')->get()->toArray();
        foreach ($storeconcepts as $storeconcept){
            Storeconcept::firstOrCreate($storeconcept);
        }
        // Storeconcept::insert(Maestro::select('storeconcept')->distinct('storeconcept')->get()->toArray());
        $materials=Maestro::select('material')->distinct('material')->get()->toArray();
        foreach ($materials as $material){
            Material::firstOrCreate($material);
        }

        $tarifafamilias=Maestro::select('material','medida','matmed')
        ->groupBy('material','medida','matmed')
        ->get();
        foreach ($tarifafamilias as $tf){
            TarifaFamilia::firstOrCreate(
                ['matmed'=>$tf->matmed],
                ['material'=>$tf->material,
                'medida'=>$tf->medida]
            );
        }

        Maestro::insertStoresSGH();
        Maestro::insertElementosSGH();
//        Maestro::insertStoreElementos(); 

        dd('finalizado');
        $notification = array(
            'message' => '??Tablas principales actualizadas satisfactoriamente!',
            'alert-type' => 'success'
        );
        
        return view('home')->with($notification);
        
    }

    public function actualizastoreelementos()
    {
        DB::table('store_elementos')->delete();

        // dd(Maestro::get()->count());
        Maestro::chunk(100, function ($maestros) {
            $dataSet = [];
            foreach ($maestros as $elemento) {
                $dataSet[] = [
                    'store_id'=>$elemento['store'],
                    'elemento_id'=>Elemento::where('elementificador',$elemento['elementificador'])->first()['id'],
                    'elementificador'=>$elemento['elementificador'],
                    ];
            }
            DB::table('store_elementos')->insert($dataSet);
        });

        dd('finalizado');

        $notification = array(
            'message' => '??Proceso finalizado satisfactoriamente!',
            'alert-type' => 'success'
        );

        dd('finalizado');
        
        return redirect('/maestro')->with($notification);

    }
    
}