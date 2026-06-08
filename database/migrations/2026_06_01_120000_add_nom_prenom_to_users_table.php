<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nom', 100)->nullable()->after('name');
            $table->string('prenom', 100)->nullable()->after('nom');
        });

        DB::table('users')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->each(function (object $user) {
                $name = trim((string) ($user->name ?? ''));
                if ($name === '') {
                    return;
                }

                $parts = preg_split('/\s+/', $name, 2);
                $nom = $parts[0] ?? '';
                $prenom = $parts[1] ?? '';

                DB::table('users')->where('id', $user->id)->update([
                    'nom' => $nom,
                    'prenom' => $prenom,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nom', 'prenom']);
        });
    }
};
