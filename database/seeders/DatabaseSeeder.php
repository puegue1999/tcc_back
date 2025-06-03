<?php

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //$this->seedersDevelop();
        if(App\Models\Tenant::where('id','tcc')->first() == null){
            $tenant1 = App\Models\Tenant::create(['id' => 'tcc']);
            $tenant1->domains()->create(['domain' => 'tcc.localhost']);
        }
    }

    private function seedersDevelop()
    {
        $this->call(PopulateDevDb::class);
    }
}
