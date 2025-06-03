<?php

use Illuminate\Database\Seeder;

class PopulateDevDb extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(App\Models\Tenant::where('id','tcc')->first() == null){
            $tenant1 = App\Models\Tenant::create(['id' => 'tcc']);
            $tenant1->domains()->create(['domain' => 'tcc.localhost']);
        }

    }
}
