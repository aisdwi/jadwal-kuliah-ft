<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $oldToNew = [
            'App\\Infrastructure\\Persistence\\Eloquent\\Model\\UserModel' =>
                'App\\Modules\\Iam\\Infrastructure\\Persistence\\Eloquent\\Model\\UserModel',
            'App\\Infrastructure\\Persistence\\Models\\User' =>
                'App\\Modules\\Iam\\Infrastructure\\Persistence\\Eloquent\\Model\\UserModel',
        ];

        foreach ($oldToNew as $old => $new) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', $old)
                ->update(['tokenable_type' => $new]);
        }
    }

    public function down(): void
    {
        $newToOld = [
            'App\\Modules\\Iam\\Infrastructure\\Persistence\\Eloquent\\Model\\UserModel' =>
                'App\\Infrastructure\\Persistence\\Eloquent\\Model\\UserModel',
        ];

        foreach ($newToOld as $new => $old) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', $new)
                ->update(['tokenable_type' => $old]);
        }
    }
};
