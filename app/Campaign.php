<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;


class Campaign extends Model
{
    public $timestamps = false;
    protected $fillable=['campaign_name','campaign_initdate','campaign_enddate','campaign_state','slug'];
    protected $dates = ['deleted_at'];

    public static function boot()
    {
        parent::boot();
       // registering a callback to be executed upon the creation of an activity AR
        static::creating(function($campaign) {
        $campaign->slug= \Str::slug($campaign->campaign_name);
        // check to see if any other slugs exist that are the same & count them
        $count = static::whereRaw("slug RLIKE '^{$campaign->slug}(-[0-9]+)?$'")->count();
        // if other slugs exist that are the same, append the count to the slug
            $campaign->slug = $count ? "{$campaign->slug}-{$count}" : $campaign->slug;
         });
    }

    public function scopeSearch($query, $busca)
    {
      return $query->where('campaign_name', 'LIKE', "%$busca%")
      ->orWhere('campaign_initdate', 'LIKE', "%$busca%")
      ->orWhere('campaign_initdate', 'LIKE', "%$busca%")
      ->orWhere('campaign_state', 'LIKE', "%$busca%")
      ;
    }

    static function inserta($tabla,$datos,$campo,$campaignId)
    {
        foreach (array_chunk($datos->toArray(),1000) as $t){
            $dataSet = [];
            foreach ($datos as $dato) {
                $dataSet[] = [
                    'campaign_id'  => $campaignId,
                    $campo  => $dato->$campo,
                ];
            }
            DB::table($tabla)->insert($dataSet);
        }
    }
    public function tiendas(){
        return $this->hasMany(CampaignTienda::class,'campaign_id');
    }

    public function tiendaselementos(){
        return $this->hasMany(CampaignTienda::class,'campaign_id')->with('elementos');
    }

    public function campaignPresupuestos(){
        return $this->hasMany(CampaignPresupuesto::class);
    }

    public function campaignPromedios(){
        return $this->hasMany(VCampaignPromedio::class);
    }

    static function getConteoFamiliaPrecio($campaignId)
    {
        return CampaignElemento::where('campaign_id',$campaignId)
            ->select('familia','precio',DB::raw('count(*) as totales'),DB::raw('SUM(unitxprop) as unidades'))
            ->groupBy('familia','precio')
            ->get();

    }

    static function getConteoZonaFamiliaPrecio($campaignId)
    {
        return CampaignElemento::where('campaign_id',$campaignId)
            ->select('zona','familia','precio',DB::raw('count(*) as totales'),DB::raw('SUM(unitxprop) as unidades'))
            ->groupBy('zona','familia','precio')
            ->get();

    }
}